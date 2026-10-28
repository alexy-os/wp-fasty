/**
 * Tokens of the builder (the set of src/Links.php): links {home}, {posts}, {page:<slug>}, {category:<slug>},
 * {archive:<post type>}; site data {site:name|phone|email|address|hours}; contact links {tel}, {mailto}, {whatsapp},
 * {telegram}, {map}; dates {year}, {today}. Which fields offer which, parsing, replacing a link token while keeping
 * what follows it (a path, #anchor or ?query), inserting one into text, and resolving client-side from the one
 * GET /links response. Pure functions (bun test).
 */
import type { Field } from "./fields/schema";

/** one target of the picker: the token and the URL the server resolves it to */
export interface LinkTarget { token: string; label: string; url: string; slug?: string; kind?: "category" | "archive"; source?: string }
/** a site data or date token and its value now ("" = the site has no such value) */
export interface DataToken { token: string; label: string; value: string }
/** GET wpfasty-builder/v1/links */
export interface LinkIndex {
  home: string;
  site: LinkTarget[];
  pages: LinkTarget[];
  sections: LinkTarget[];
  /** contact links built from site data ("#" without it); source = the site data key */
  contact: LinkTarget[];
  data: DataToken[];
}

/** same pattern as Links::TOKEN */
export const MACRO = /\{(home|posts|page|category|archive|site|tel|mailto|whatsapp|telegram|map|year|today)(?::([a-z0-9_-]+))?\}/;
const MACROS = new RegExp(MACRO.source, "g");
/** kinds that need an argument ({page:<slug>}…) */
const NEEDS_ARG = new Set(["page", "category", "archive", "site"]);

export interface Macro { kind: string; arg: string; token: string; prefix: string; suffix: string }

/** the first token of a value with what stands before and after it; null without one */
export function parseMacro(value: string): Macro | null {
  const m = MACRO.exec(value);
  if (!m) return null;
  return { kind: m[1]!, arg: m[2] ?? "", token: m[0], prefix: value.slice(0, m.index), suffix: value.slice(m.index + m[0].length) };
}

/**
 * Link fields: the value with `token` in place of its token; the text after the old token (path, #anchor, ?query)
 * stays. A value without a token becomes the token, keeping a leading #anchor or ?query ("#team" → "{page:about}#team").
 */
export function applyMacro(value: string, token: string): string {
  const m = parseMacro(value);
  if (m) return m.prefix + token + m.suffix;
  return /^[#?]/.test(value) ? token + value : token;
}

/** Text fields: the token inserted in place of the selection [start, end) (the end of the text without one) */
export function insertMacro(value: string, token: string, start?: number | null, end?: number | null): { value: string; caret: number } {
  const a = Math.max(0, Math.min(start ?? value.length, value.length));
  const b = Math.max(a, Math.min(end ?? a, value.length));
  return { value: value.slice(0, a) + token + value.slice(b), caret: a + token.length };
}

/** media props (an image, video poster, avatar or icon URL): no tokens there, whatever the name ends with */
export const MEDIA_FIELDS: readonly RegExp[] = [/^image/i, /^src$/i, /src$/i, /^poster$/i, /^avatar/i, /^icon/i];

/**
 * Fields that take a link: prop names href, url, link or ending in Href / Url (any case), or a field typed url;
 * never the media props of MEDIA_FIELDS (imageUrl, imageSrc, posterUrl…).
 */
export function isLinkField(name: string, field: Pick<Field, "type">): boolean {
  if (MEDIA_FIELDS.some((re) => re.test(name))) return false;
  if ((field.type as string) === "url") return true;
  if (field.type !== "text") return false;
  return /^link$/i.test(name) || /(href|url)$/i.test(name);
}

/** which picker a field gets: "link" (pages, sections, contact links), "text" (site data, dates) or none */
export function tokenMode(name: string, field: Pick<Field, "type">): "link" | "text" | null {
  if (isLinkField(name, field)) return "link";
  if (MEDIA_FIELDS.some((re) => re.test(name))) return null;
  return field.type === "text" || field.type === "textarea" ? "text" : null;
}

const NOUN: Record<string, string> = { page: "page", category: "category", archive: "archive", site: "site data" };

/** a token's value from the index: a URL (links, contact) or text (site data, dates), with a warning when empty */
function lookup(m: Macro, index: LinkIndex): { value: string; warning?: string } | { error: string } {
  if (NEEDS_ARG.has(m.kind) && m.arg === "") return { error: `add ${m.kind === "site" ? "a key" : "a slug"}: {${m.kind}:<${m.kind === "site" ? "key" : "slug"}>}` };
  const data = index.data.find((t) => t.token === m.token);
  if (data) return data.value === "" ? { value: "", warning: `${data.label.toLowerCase()} is not set in the site settings` } : { value: data.value };
  const contact = index.contact.find((t) => t.token === m.token);
  if (contact) return contact.url === "#" ? { value: "#", warning: `no ${contact.source ?? "value"} in the site settings: links to #` } : { value: contact.url };
  const target = [...index.site, ...index.pages, ...index.sections].find((t) => t.token === m.token);
  if (target) return { value: target.url };
  return { error: `${NOUN[m.kind] ?? m.kind} ${m.arg} not found` };
}

export type Resolved = { url: string; shown: string; warning?: string } | { error: string };

/** Link fields: what a value with a token turns into on the site (the URL, and the same relative to home), or why it cannot */
export function resolveMacro(value: string, index: LinkIndex): Resolved | null {
  const m = parseMacro(value);
  if (!m) return null;
  const r = lookup(m, index);
  if ("error" in r) return r;
  const url = m.prefix + r.value + m.suffix;
  return { url, shown: relative(url, index.home), ...(r.warning ? { warning: r.warning } : {}) };
}

/** Text fields: the text with every token replaced as the server does, plus what is missing; null without tokens */
export function expandText(value: string, index: LinkIndex): { text: string; warnings: string[] } | null {
  if (!MACRO.test(value)) return null;
  const warnings: string[] = [];
  const text = value.replace(MACROS, (token: string, kind: string, arg?: string) => {
    const r = lookup({ kind, arg: arg ?? "", token, prefix: "", suffix: "" }, index);
    if ("error" in r) {
      warnings.push(r.error);
      return token;
    }
    if (r.warning && !warnings.includes(r.warning)) warnings.push(r.warning);
    return r.value;
  });
  return { text, warnings };
}

/** a URL of the site as a path from its root ("http://x.test/about/#team" → "/about/#team"); others unchanged */
export function relative(url: string, home: string): string {
  const root = home.replace(/\/+$/, "");
  return root && (url === root || url.startsWith(root + "/") || url.startsWith(root + "#") || url.startsWith(root + "?"))
    ? url.slice(root.length) || "/"
    : url;
}

export interface Filtered { site: LinkTarget[]; pages: LinkTarget[]; sections: LinkTarget[]; contact: LinkTarget[]; data: DataToken[] }

/** the index narrowed to a search (label, slug or token; any case), one list per picker column */
export function filterIndex(index: LinkIndex | null, query: string): Filtered {
  if (!index) return { site: [], pages: [], sections: [], contact: [], data: [] };
  const q = query.trim().toLowerCase();
  const hit = (t: { label: string; token: string; slug?: string }) => !q || [t.label, t.slug ?? "", t.token].some((s) => s.toLowerCase().includes(q));
  return {
    site: index.site.filter(hit), pages: index.pages.filter(hit), sections: index.sections.filter(hit),
    contact: (index.contact ?? []).filter(hit), data: (index.data ?? []).filter(hit),
  };
}
