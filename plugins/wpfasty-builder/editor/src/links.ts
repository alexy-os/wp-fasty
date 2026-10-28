/**
 * Link tokens of the builder ({home}, {posts}, {page:<slug>}, {category:<slug>}, {archive:<post type>}; the set of
 * src/Links.php): which fields take them, parsing, replacing a token while keeping what follows it (a path, #anchor
 * or ?query) and resolving one client-side from the GET /links index. Pure functions (bun test).
 */
import type { Field } from "./fields/schema";

/** one target of the picker: the token and the URL the server resolves it to */
export interface LinkTarget { token: string; label: string; url: string; slug?: string; kind?: "category" | "archive" }
/** GET wpfasty-builder/v1/links */
export interface LinkIndex { home: string; site: LinkTarget[]; pages: LinkTarget[]; sections: LinkTarget[] }

/** same pattern as Links::TOKEN */
export const MACRO = /\{(home|posts|page|category|archive)(?::([a-z0-9_-]+))?\}/;

export interface Macro { kind: string; arg: string; token: string; prefix: string; suffix: string }

/** the first token of a value with what stands before and after it; null without one */
export function parseMacro(value: string): Macro | null {
  const m = MACRO.exec(value);
  if (!m) return null;
  return { kind: m[1]!, arg: m[2] ?? "", token: m[0], prefix: value.slice(0, m.index), suffix: value.slice(m.index + m[0].length) };
}

/**
 * The value with `token` in place of its token; the text after the old token (path, #anchor, ?query) stays. A value
 * without a token becomes the token, keeping a leading #anchor or ?query ("#team" → "{page:about}#team").
 */
export function applyMacro(value: string, token: string): string {
  const m = parseMacro(value);
  if (m) return m.prefix + token + m.suffix;
  return /^[#?]/.test(value) ? token + value : token;
}

/** media props (an image, video poster, avatar or icon URL): no link tokens there, whatever the name ends with */
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

const NOUN: Record<string, string> = { page: "page", category: "category", archive: "archive" };

export type Resolved = { url: string; shown: string } | { error: string };

/** what a value with a token turns into on the site (the URL, and the same relative to home), or why it cannot */
export function resolveMacro(value: string, index: LinkIndex): Resolved | null {
  const m = parseMacro(value);
  if (!m) return null;
  if (m.kind !== "home" && m.kind !== "posts" && m.arg === "") return { error: `add a slug: {${m.kind}:<slug>}` };
  const target = [...index.site, ...index.pages, ...index.sections].find((t) => t.token === m.token);
  if (!target) return { error: `${NOUN[m.kind] ?? m.kind} ${m.arg} not found` };
  const url = m.prefix + target.url + m.suffix;
  return { url, shown: relative(url, index.home) };
}

/** a URL of the site as a path from its root ("http://x.test/about/#team" → "/about/#team"); others unchanged */
export function relative(url: string, home: string): string {
  const root = home.replace(/\/+$/, "");
  return root && (url === root || url.startsWith(root + "/") || url.startsWith(root + "#") || url.startsWith(root + "?"))
    ? url.slice(root.length) || "/"
    : url;
}

/** the index narrowed to a search (label, slug or token; any case), one list per picker column */
export function filterIndex(index: LinkIndex | null, query: string): { site: LinkTarget[]; pages: LinkTarget[]; sections: LinkTarget[] } {
  if (!index) return { site: [], pages: [], sections: [] };
  const q = query.trim().toLowerCase();
  const hit = (t: LinkTarget) => !q || [t.label, t.slug ?? "", t.token].some((s) => s.toLowerCase().includes(q));
  return { site: index.site.filter(hit), pages: index.pages.filter(hit), sections: index.sections.filter(hit) };
}
