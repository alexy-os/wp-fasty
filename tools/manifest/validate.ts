// Site manifest v0.2 validator: JSON Schema (schema/site.manifest.schema.json, ajv draft 2020-12) + semantic rules
// + optional repo parity (the manifest must describe the real child theme in themes/<slug> exactly).
//   bun run manifest:validate                       # every manifests/examples/*.json, with repo parity
//   bun run manifest:validate my-site.json          # schema + semantic rules only
//   bun run manifest:validate my-site.json --repo   # ... plus parity with themes/<theme.slug>
import Ajv2020 from "ajv/dist/2020";
import addFormats from "ajv-formats";
import { existsSync, readdirSync, readFileSync, statSync } from "node:fs";
import { dirname, join, relative, resolve } from "node:path";
import { spawnSync } from "node:child_process";
import { codexManifest, sourceIds } from "../../.workspaces/theme-builder/src/generate/codex";
import { brandDark } from "../../.workspaces/theme-builder/src/configure/palette";

type J = Record<string, any>;
const root = resolve(dirname(new URL(import.meta.url).pathname), "../..");
const read = (p: string) => readFileSync(p, "utf8");
const json = (p: string) => JSON.parse(read(p));

const schema = json(join(root, "schema/site.manifest.schema.json"));
const ajv = new Ajv2020({ allErrors: true, strict: true, strictTypes: false });
addFormats(ajv);
const validateManifest = ajv.compile(schema);
const validateLanding = ajv.compile({ $ref: `${schema.$id}#/$defs/landing` });

const catalog = json(join(root, "plugins/wpfasty-builder/sources/ui8kit/latte/catalog.json"));
const variants = new Map<string, Set<string>>(
  catalog.blocks.map((b: J) => [b.id, new Set((b.variants ?? []).map((v: J) => v.id))]),
);
const collections = new Set(catalog.collections.map((c: J) => c.id));

/** Archetype code relies on these post types (names + capabilities are hard-wired in the child PHP). */
const ARCHETYPE_TYPES: Record<string, { name: string; capability?: string }[]> = {
  press: [],
  folio: [{ name: "project" }],
  shop: [{ name: "product", capability: "purchasable" }, { name: "shop_order", capability: "orderable" }],
  blank: [],
};


function semantic(m: J, err: (msg: string) => void): void {
  const t = m.theme;
  if (t.textDomain !== t.slug) err(`theme.textDomain "${t.textDomain}" must equal theme.slug "${t.slug}"`);
  const preset = m.parent?.preset ?? "latte";
  if (!existsSync(join(root, `presets/themes/${preset}.json`))) err(`parent.preset: presets/themes/${preset}.json not found`);
  const layout = m.parent?.layout ?? "classic";
  if (!existsSync(join(root, `presets/layouts/${layout}`)) && !existsSync(join(root, `presets/layouts/${layout}.latte`)))
    err(`parent.layout: presets/layouts/${layout} not found`);
  for (const [tpl, blocks] of Object.entries<string[]>(m.templates ?? {}))
    for (const b of blocks)
      if (!existsSync(join(root, `presets/${b}.latte`))) err(`templates.${tpl}: presets/${b}.latte not found`);

  const types: J[] = m.postTypes ?? [];
  const names = new Set<string>();
  const taxes = new Set<string>();
  const views = new Set<string>(m.views ?? []);
  for (const [i, pt] of types.entries()) {
    const at = `postTypes[${i}] (${pt.name})`;
    if (names.has(pt.name)) err(`${at}: duplicate post type name`);
    names.add(pt.name);
    for (const tx of Object.keys(pt.taxonomies ?? {})) {
      if (taxes.has(tx)) err(`${at}: taxonomy "${tx}" declared twice`);
      taxes.add(tx);
    }
    const fields: J = pt.fields ?? {};
    const keys = new Set<string>();
    for (const [k, f] of Object.entries<J>(fields)) {
      if (f.options && f.type !== "string") err(`${at}: fields.${k}.options needs type string, is ${f.type}`);
      const key = f.metaKey ?? `_${pt.metaPrefix ?? m.theme.prefix}_${k}`;
      if (keys.has(key)) err(`${at}: fields.${k} storage key "${key}" used twice`);
      keys.add(key);
    }
    const cats = Object.entries<J>(pt.taxonomies ?? {}).filter(([, t]) => t.role === "category");
    if (cats.length > 1) err(`${at}: ${cats.length} taxonomies have role category (max 1)`);
    const need = (cap: string, key: string, type: string, required: boolean) => {
      const f = pt.capabilities?.[cap]?.[key];
      if (f === undefined) return required && err(`${at}: capabilities.${cap}.${key} is required`);
      if (!fields[f]) err(`${at}: capabilities.${cap}.${key} → unknown field "${f}"`);
      else if (fields[f].type !== type) err(`${at}: capabilities.${cap}.${key} → field "${f}" must be ${type}, is ${fields[f].type}`);
    };
    if (pt.capabilities?.purchasable) {
      need("purchasable", "price", "money", true);
      need("purchasable", "sale", "money", false);
      need("purchasable", "stock", "integer", false);
      if (!m.settings?.currency) err(`${at}: purchasable needs settings.currency (money fields are minor units of it)`);
    }
    for (const c of pt.adminColumns ?? []) if (!fields[c.field]) err(`${at}: adminColumns → unknown field "${c.field}"`);
    for (const [k, v] of Object.entries<string>(pt.views ?? {}))
      if (m.views && !views.has(v)) err(`${at}: views.${k} "${v}" is not listed in top-level views`);
  }
  for (const pt of types)
    if (pt.adminParent && !names.has(pt.adminParent)) err(`postTypes (${pt.name}): adminParent "${pt.adminParent}" is not declared`);
  for (const need of ARCHETYPE_TYPES[m.archetype] ?? []) {
    const pt = types.find((p) => p.name === need.name);
    if (!pt) err(`archetype ${m.archetype} requires post type "${need.name}"`);
    else if (need.capability && !pt.capabilities?.[need.capability])
      err(`archetype ${m.archetype}: post type "${need.name}" must declare capability ${need.capability}`);
  }
  if (types.length && m.output?.typesPlugin === "bundled" && m.output?.wporg) err("output: wporg requires typesPlugin=companion");

  const landings = m.builder?.landings ?? [];
  if (landings.length && !m.builder?.enabled) err("builder.landings given but builder.enabled is not true");
  let fronts = 0;
  const slugs = new Set<string>();
  const sources = sourceIds(codexManifest(m));
  for (const [i, l] of landings.entries()) {
    let spec = l;
    const at = l.file ? `builder.landings[${i}] (${l.file})` : `builder.landings[${i}]`;
    if (l.file) {
      const p = join(root, l.file);
      if (!existsSync(p)) { err(`${at}: file not found`); continue; }
      spec = json(p);
      if (!validateLanding(spec)) {
        for (const e of validateLanding.errors ?? []) err(`${at}${e.instancePath}: ${e.message}`);
        continue;
      }
    }
    if (spec.front) fronts++;
    if (slugs.has(spec.page.slug)) err(`${at}: duplicate page slug "${spec.page.slug}"`);
    slugs.add(spec.page.slug);
    if (spec.layout.collection && !collections.has(spec.layout.collection))
      err(`${at}: unknown catalog collection "${spec.layout.collection}"`);
    const ids = new Set<string>();
    for (const [j, b] of spec.layout.blocks.entries()) {
      if (b.id && ids.has(b.id)) err(`${at}: blocks[${j}] duplicate id "${b.id}"`);
      if (b.id) ids.add(b.id);
      for (const [prop, bind] of Object.entries<J>(b.bindings ?? {})) {
        // the builder's sources are the site's Codex kinds and taxonomies (WPFastyBuilder\\DataSources::all())
        if (!sources.has(bind.source))
          err(`${at}: blocks[${j}].bindings.${prop} unknown source "${bind.source}" (site sources: ${[...sources].join(", ")})`);
      }
      const vs = variants.get(b.block);
      if (!vs) err(`${at}: blocks[${j}] unknown block "${b.block}"`);
      else if (!vs.has(b.variant)) err(`${at}: blocks[${j}] unknown variant "${b.block}:${b.variant}"`);
    }
  }
  if (fronts > 1) err(`builder.landings: ${fronts} landings set front=true (max 1)`);
}

// ---- repo parity -------------------------------------------------------------------------------------------

const strip = (o: any): any =>
  Array.isArray(o) ? o.map(strip)
  : o && typeof o === "object" ? Object.fromEntries(Object.entries(o).filter(([, v]) => v !== undefined).map(([k, v]) => [k, strip(v)]))
  : o;
const canon = (o: any): string => JSON.stringify(o, (_k, v) =>
  v && typeof v === "object" && !Array.isArray(v) ? Object.fromEntries(Object.entries(v).sort(([a], [b]) => a.localeCompare(b))) : v);

/** PHP types/<name>.php module → manifest postType shape. */
function fromPhp(d: J): J {
  const a: J = d.args ?? {};
  const L: J = a.labels ?? {};
  const caps: J = Array.isArray(d.supports) ? Object.fromEntries(d.supports.map((s: string) => [s, {}])) : d.supports ?? {};
  const menu = typeof a.show_in_menu === "string" ? a.show_in_menu.match(/post_type=([a-z0-9_]+)/)?.[1] : undefined;
  return strip({
    name: d.name,
    labels: { singular: L.singular_name, plural: L.name, addNew: L.add_new_item, edit: L.edit_item },
    public: a.public ?? false,
    adminParent: menu,
    createInAdmin: a.capabilities?.create_posts !== "do_not_allow",
    slug: a.rewrite?.slug,
    archiveSlug: a.has_archive === true ? d.name : a.has_archive ?? false,
    icon: typeof a.menu_icon === "string" ? a.menu_icon.replace(/^dashicons-/, "") : undefined,
    rest: a.show_in_rest ?? false,
    supports: (a.supports ?? ["title", "editor"]).map((s: string) => (s === "page-attributes" ? "order" : s)),
    metaPrefix: d.meta_prefix,
    fields: Object.fromEntries(Object.entries<J>(d.fields ?? {}).map(([k, f]) =>
      [k, strip({ type: f.type ?? "string", label: f.label ?? k, rest: f.show_in_rest === false ? false : true, metaKey: f.meta_key,
        options: f.options ? f.options.map((v: string) => ({ value: v, label: f.option_labels?.[v] ?? v })) : undefined })])),
    metabox: d.metabox === false ? false : d.metabox
      ? { title: d.metabox.title, position: d.metabox.context === "side" ? "side" : "main", priority: d.metabox.priority ?? "default" }
      : undefined,
    taxonomies: Object.fromEntries(Object.entries<J>(d.taxonomies ?? {}).map(([k, x]) => [k, {
      labels: { singular: x.labels?.singular_name, plural: x.labels?.name },
      public: x.public ?? true, hierarchical: x.hierarchical ?? false, rest: x.show_in_rest ?? false,
      adminColumn: x.show_admin_column ?? false, slug: x.rewrite?.slug,
    }])),
    capabilities: Object.keys(caps).length ? Object.fromEntries(Object.entries<J>(caps).map(([k, c]) => [k,
      k === "purchasable" ? strip({ price: c.price_field, sale: c.sale_field, stock: c.stock_field }) : {}])) : undefined,
    archive: d.archive ? strip({
      perPage: d.archive.per_page,
      orderBy: d.archive.orderby ? Object.entries<string>(d.archive.orderby).map(([f, dir]) =>
        ({ field: f === "menu_order" ? "order" : f, direction: dir.toLowerCase() })) : undefined,
      title: d.archive.title, titlePrefix: d.archive.prefix,
    }) : undefined,
    views: d.views,
    adminColumns: d.columns ? Object.entries<J>(d.columns).map(([k, c]) => ({ field: k, label: c.label })) : undefined,
  });
}

/** Manifest postType with schema defaults applied (so omitted == default compares equal). */
function withDefaults(pt: J, m: J): J {
  return strip({
    ...pt,
    public: pt.public ?? true,
    createInAdmin: pt.createInAdmin ?? true,
    archiveSlug: pt.archiveSlug ?? false,
    rest: pt.rest ?? true,
    supports: pt.supports ?? ["title", "editor"],
    metaPrefix: pt.metaPrefix ?? (Object.keys(pt.fields ?? {}).length ? m.theme.prefix : undefined),
    // Codex-only keys (required, nullable, private, role) live in types/codex.*.json, checked by theme:roundtrip.
    fields: Object.fromEntries(Object.entries<J>(pt.fields ?? {}).map(([k, { required, nullable, private: _p, ...f }]) => [k, { ...f, rest: f.rest ?? true }])),
    metabox: pt.metabox && { position: "main", priority: "default", ...pt.metabox },
    taxonomies: Object.fromEntries(Object.entries<J>(pt.taxonomies ?? {}).map(([k, { role, ...x }]) =>
      [k, { public: true, hierarchical: true, rest: true, adminColumn: true, ...x }])),
  });
}

function cssVars(css: string, selector: RegExp): J | undefined {
  const body = css.match(selector)?.[1];
  return body === undefined ? undefined : Object.fromEntries([...body.matchAll(/--([a-z-]+):\s*([^;]+);/g)].map((x) => [x[1], x[2].trim()]));
}

function filesUnder(dir: string, base = dir): string[] {
  if (!existsSync(dir)) return [];
  return readdirSync(dir).flatMap((n) => {
    const p = join(dir, n);
    return statSync(p).isDirectory() ? filesUnder(p, base) : [relative(base, p)];
  });
}

function parity(m: J, err: (msg: string) => void): void {
  const t = m.theme;
  const dir = join(root, "themes", t.slug);
  if (!existsSync(dir)) return err(`--repo: themes/${t.slug} does not exist`);
  const style = read(join(dir, "style.css"));
  const h = (k: string) => style.match(new RegExp(`^${k}:\\s*(.+)$`, "m"))?.[1].trim();
  const expect: [string, string | undefined, string | undefined][] = [
    ["Theme Name", t.name, h("Theme Name")],
    ["Description", t.description, h("Description")],
    ["Version", t.version ?? "1.0.0", h("Version")],
    ["Theme URI", t.uri ?? "https://github.com/alexy-os/wp-fasty", h("Theme URI")],
    ["Author", t.author?.name ?? "AlexY OS", h("Author")],
    ["Author URI", t.author?.uri ?? "https://github.com/alexy-os", h("Author URI")],
    ["Requires at least", t.requires?.platform ?? "6.4", h("Requires at least")],
    ["Requires PHP", t.requires?.php ?? "8.2", h("Requires PHP")],
    ["Tested up to", t.testedUpTo ?? "7.1", h("Tested up to")],
    ["Text Domain", t.textDomain, h("Text Domain")],
    ["Template", m.parent?.preset ?? "latte", h("Template")],
    ["Tags", (t.tags ?? []).join(", "), h("Tags")?.split(",").map((s) => s.trim()).join(", ")],
  ];
  for (const [k, want, got] of expect) if (want !== got) err(`style.css ${k}: manifest "${want}" ≠ theme "${got}"`);

  const css = read(join(dir, "assets/css/tokens.css"));
  const light = cssVars(css, /:root\s*\{([^}]*)\}/) ?? {};
  const dark = cssVars(css, /\.dark\s*\{([^}]*)\}/);
  const b = m.brand ?? {};
  const wantLight = strip({ ...b.light, radius: b.radius, "font-body": b.fonts?.body, "font-display": b.fonts?.display });
  if (canon(wantLight) !== canon(light)) err(`tokens.css :root differs from brand (light/radius/fonts)\n    manifest ${canon(wantLight)}\n    theme    ${canon(light)}`);
  if (canon(brandDark(b)) !== canon(dark)) err(`tokens.css .dark differs from brand.dark (or the dark derived from brand.light)`);
  if (dark && canon(Object.keys(dark).sort()) !== canon(Object.keys(b.light ?? {}).sort())) err(`tokens.css .dark keys differ from :root colour keys`);

  const viewFiles = filesUnder(join(dir, "views")).filter((f) => f.endsWith(".latte")).map((f) => f.replace(/\.latte$/, "")).sort();
  const wantViews = [...(m.views ?? [])].sort();
  if (canon(viewFiles) !== canon(wantViews)) err(`views: manifest ${canon(wantViews)} ≠ theme ${canon(viewFiles)}`);

  const php = spawnSync("php", [join(root, "tools/manifest/dump-types.php"), dir], { encoding: "utf8" });
  if (php.status !== 0) return err(`dump-types.php failed: ${php.stderr}`);
  const real = (JSON.parse(php.stdout) as J[]).map(fromPhp).sort((x, y) => x.name.localeCompare(y.name));
  const want = (m.postTypes ?? []).map((p: J) => withDefaults(p, m)).sort((x: J, y: J) => x.name.localeCompare(y.name));
  if (canon(real.map((r) => r.name)) !== canon(want.map((w: J) => w.name)))
    err(`postTypes: manifest ${canon(want.map((w: J) => w.name))} ≠ theme types/ ${canon(real.map((r) => r.name))}`);
  for (const w of want) {
    const r = real.find((x) => x.name === w.name);
    if (r && canon(strip(r)) !== canon(w)) err(`postTypes.${w.name} differs from types/${w.name}.php\n    manifest ${canon(w)}\n    theme    ${canon(strip(r))}`);
  }

  const fn = read(join(dir, "functions.php"));
  const f = m.features ?? {};
  const has = (s: string) => fn.includes(s);
  if (!!f.comments !== has("wpfasty_comments_enabled")) err(`features.comments=${!!f.comments} but functions.php ${has("wpfasty_comments_enabled") ? "enables" : "does not enable"} comments`);
  if (!!f.fullWidth !== has("wpfasty-full-width")) err(`features.fullWidth=${!!f.fullWidth} disagrees with functions.php`);
  const bg = fn.match(/'custom-background',\s*\['default-color'\s*=>\s*'([0-9a-fA-F]{6})'/)?.[1];
  if ((f.background?.slice(1) ?? undefined) !== bg) err(`features.background ${f.background} ≠ functions.php custom-background ${bg}`);
  if (((f.blogIndexTitle ?? "archive") === "site") !== /is_home\(\)[\s\S]{0,200}get_bloginfo\('name'\)/.test(fn))
    err(`features.blogIndexTitle disagrees with functions.php`);
  const rm = fn.match(/\['read_more'\]\s*=\s*__\('([^']+)'/)?.[1];
  if (m.labels?.readMore !== rm) err(`labels.readMore "${m.labels?.readMore}" ≠ functions.php "${rm}"`);
}

// ---- main --------------------------------------------------------------------------------------------------

const args = process.argv.slice(2);
const repoFlag = args.includes("--repo");
let files = args.filter((a) => !a.startsWith("--"));
const examples = files.length === 0;
if (examples) {
  const d = join(root, "manifests/examples");
  files = readdirSync(d).filter((f) => f.endsWith(".json")).sort().map((f) => join(d, f));
}
let failed = 0;
for (const file of files) {
  const errors: string[] = [];
  const err = (msg: string) => { errors.push(msg); };
  let m: J;
  try { m = json(resolve(file)); } catch (e) { console.log(`✗ ${file}: ${(e as Error).message}`); failed++; continue; }
  if (!validateManifest(m)) {
    for (const e of validateManifest.errors ?? []) err(`schema ${e.instancePath || "/"}: ${e.message}${e.params ? " " + JSON.stringify(e.params) : ""}`);
  } else {
    semantic(m, err);
    if (repoFlag || examples) parity(m, err);
  }
  const name = relative(process.cwd(), resolve(file));
  if (errors.length) { failed++; console.log(`✗ ${name}`); for (const e of errors) console.log(`  - ${e}`); }
  else console.log(`✓ ${name}${repoFlag || examples ? ` (schema, rules, parity with themes/${m.theme.slug})` : " (schema, rules)"}`);
}
console.log(`${files.length} manifest(s), ${failed} failed`);
process.exit(failed ? 1 : 0);
