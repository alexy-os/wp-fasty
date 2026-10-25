// Site manifest postTypes → Codex Manifest (types/codex.manifest.json, manifest.schema.json of @ui8kit/contract)
// + delivery sidecar (types/codex.delivery.json: taxonomy roles + WordPress storage, outside the manifest digest).
// Draft profile until @ui8kit/contract ships the vectors: every constant that may change lives in PROFILE.
import { createHash } from "node:crypto";
import type { J } from "./emit";

export const PROFILE = {
  codexVersion: "0.4.0",
  entryProfile: "codex-entry/v1",
  digestPrefix: "codex-manifest/v2:sha256:",
  rule: { integer: "fastygo.codex/integer", money: "fastygo.codex/money", uri: "fastygo.codex/uri" },
  scope: "global",
  /** WordPress core types every site has (rest true); their taxonomies and roles. */
  core: [
    { name: "post", label: "Posts", supports: ["title", "editor", "excerpt", "author", "thumbnail"], taxonomies: { category: "category", post_tag: "tag" } },
    { name: "page", label: "Pages", supports: ["title", "editor", "excerpt", "author", "thumbnail", "order"], taxonomies: {} },
  ],
} as const;

/** Core entry keys declared as manifest fields (skipped by the contract validator, read by compat as "path exists"). */
const CHROME: Record<string, J[]> = {
  title: [{ id: "title", label: "Title", type: "text", localized: true }],
  editor: [{ id: "content", label: "Content", type: "richtext", localized: true }],
  excerpt: [{ id: "excerpt", label: "Excerpt", type: "textarea", localized: true }],
  // ids of user / media entries; plain strings: manifest relation ids are global, one per field id (go-codex)
  author: [{ id: "author_id", label: "Author", type: "string", ui_hint: "user" }],
  thumbnail: [{ id: "featured_media_id", label: "Featured image", type: "string", ui_hint: "media" }],
  order: [{ id: "order", label: "Order", type: "number", rules: [{ name: PROFILE.rule.integer }] }],
};
const PUBLISHED = { id: "published_at", label: "Published", type: "datetime" };
const INT = [{ name: PROFILE.rule.integer }];
/** Platform kinds behind |media and |author (same field ids as the codegen-x manifest). Users: public name only. */
const PLATFORM: J[] = [
  { record: { id: "media", label: "Media", scope: PROFILE.scope, fields: [
    { id: "title", label: "Title", type: "text", localized: true },
    { id: "content", label: "Description", type: "richtext", localized: true },
    { id: "author_id", label: "Uploaded by", type: "string", ui_hint: "user" },
    { id: "published_at", label: "Published", type: "datetime" },
    { id: "alt", label: "Alt", type: "string" },
    // proposal (not agreed): the owner as `<kind>-<n>`; contract fromWpMedia has `post` (WP integer id)
    { id: "attached_to", label: "Attached to", type: "string", ui_hint: "entry" },
    { id: "caption", label: "Caption", type: "textarea" },
    { id: "file", label: "File", type: "string", required: true, rules: [{ name: PROFILE.rule.uri }] },
    { id: "height", label: "Height", type: "number", rules: INT },
    { id: "mime", label: "Mime", type: "string", required: true },
    { id: "size", label: "File size", type: "number", rules: INT },
    { id: "width", label: "Width", type: "number", rules: INT },
  ] } },
  { record: { id: "user", label: "Users", scope: PROFILE.scope, fields: [
    { id: "title", label: "Name", type: "text", localized: true },
    { id: "content", label: "Biographical info", type: "textarea", localized: true },
    { id: "login", label: "Username", type: "string", sensitive: true },
    { id: "roles", label: "Roles", type: "collection", items: { id: "item", label: "Role", type: "string" }, sensitive: true },
    { id: "url", label: "Website", type: "string", rules: [{ name: PROFILE.rule.uri }] },
  ] } },
];

/** C.2: site field type → Codex field. */
function field(id: string, f: J): J {
  const out: J = { id, label: f.label };
  switch (f.type) {
    case "text": out.type = "textarea"; break;
    case "url": out.type = "string"; out.rules = [{ name: PROFILE.rule.uri }]; break;
    case "email": out.type = "string"; out.ui_hint = "email"; break; // Q7: no email type
    case "integer": out.type = "number"; out.rules = [{ name: PROFILE.rule.integer }]; break;
    case "number": out.type = "number"; break;
    case "money": out.type = "number"; out.rules = [{ name: PROFILE.rule.money }]; break;
    case "boolean": out.type = "boolean"; break;
    case "date": out.type = "string"; out.ui_hint = "date"; break; // YYYY-MM-DD, not an RFC 3339 instant
    default: out.type = "string";
  }
  if (f.options) { out.type = "select"; out.options = f.options.map((o: J) => ({ value: o.value, label: o.label })); }
  if (f.required) out.required = true;
  if (f.private) out.sensitive = true;
  return out;
}

const restTypes = (m: J): J[] => (m.postTypes ?? []).filter((pt: J) => pt.rest !== false);

export function codexManifest(m: J): J {
  const res: J[] = structuredClone(PLATFORM);
  for (const c of PROFILE.core)
  {
    const fields = [...c.supports.flatMap((s) => CHROME[s] ?? []), PUBLISHED];
    const record: J = { id: c.name, label: c.label, scope: PROFILE.scope, fields };
    const tx = Object.keys(c.taxonomies).sort();
    res.push(tx.length ? { record, taxonomies: tx } : { record });
  }
  for (const pt of restTypes(m)) {
    const fields = [...(pt.supports ?? ["title", "editor"]).flatMap((s: string) => CHROME[s] ?? []), PUBLISHED,
      ...Object.entries<J>(pt.fields ?? {}).filter(([, f]) => f.rest !== false).map(([k, f]) => field(k, f))];
    const record: J = { id: pt.name, label: pt.labels.plural, scope: PROFILE.scope, fields };
    const caps = Object.keys(pt.capabilities ?? {}).sort();
    if (caps.length) record.capabilities = caps;
    const r: J = { record };
    const tx = Object.entries<J>(pt.taxonomies ?? {}).filter(([, t]) => t.rest !== false).map(([k]) => k).sort();
    if (tx.length) r.taxonomies = tx;
    res.push(r);
  }
  res.sort((a, b) => (a.record.id < b.record.id ? -1 : a.record.id > b.record.id ? 1 : 0)); // Canonical(): resources by id
  return { name: m.theme.slug, version: m.theme.version ?? "1.0.0", resources: res };
}

/** RFC 8785 for the value space of a manifest (strings, safe integers, booleans, arrays, objects). */
export function jcs(v: unknown): string {
  if (Array.isArray(v)) return `[${v.map(jcs).join(",")}]`;
  if (v && typeof v === "object")
    return `{${Object.keys(v).sort().filter((k) => (v as J)[k] !== undefined).map((k) => `${JSON.stringify(k)}:${jcs((v as J)[k])}`).join(",")}}`;
  if (typeof v === "number" && !Number.isSafeInteger(v)) throw new Error(`jcs: non-integer number ${v} in manifest`);
  return JSON.stringify(v);
}

export const manifestDigest = (cm: J): string => PROFILE.digestPrefix + createHash("sha256").update(jcs(cm)).digest("hex");

/** Delivery sidecar: taxonomy roles (R2/R7 binding aliases) + WordPress storage of each Codex field. Not in the digest. */
export function codexDelivery(m: J, cm: J): J {
  const taxonomies: J[] = [];
  const kinds: J = {};
  for (const c of PROFILE.core) {
    for (const [id, role] of Object.entries(c.taxonomies)) taxonomies.push({ id, role });
    kinds[c.name] = { post_type: c.name };
  }
  for (const pt of restTypes(m)) {
    for (const [id, t] of Object.entries<J>(pt.taxonomies ?? {})) if (t.rest !== false) taxonomies.push(t.role ? { id, role: t.role } : { id });
    const prefix = pt.metaPrefix ?? m.theme.prefix;
    const k: J = { post_type: pt.name };
    const meta = Object.entries<J>(pt.fields ?? {}).filter(([, f]) => f.rest !== false);
    if (meta.length) k.meta = Object.fromEntries(meta.map(([id, f]) => [id, f.metaKey ?? `_${prefix}_${id}`]));
    const p = pt.capabilities?.purchasable;
    if (p) k.purchasable = { price: p.price, ...(p.sale ? { sale: p.sale } : {}), ...(p.stock ? { stock: p.stock } : {}) };
    kinds[pt.name] = k;
  }
  taxonomies.sort((a, b) => (a.id < b.id ? -1 : 1));
  return {
    manifest_digest: manifestDigest(cm),
    taxonomies,
    // key "wp", not the platform's full name: Theme Check rejects that word in lowercase in any theme text file
    wp: { kinds, settings: { currency: "wpfasty_currency" } },
  };
}
