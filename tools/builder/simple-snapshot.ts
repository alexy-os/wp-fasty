/**
 * The bundled block source of the Builder (docs/agent/adr-007-simple-blocks-live-prototypes.md): the UI8Kit Simple
 * blocks (block-contract/v1), a pinned snapshot in plugins/wpfasty-builder/sources/ui8kit, refreshed only by this
 * script from the pins of sources/ui8kit/snapshot.config.json; the plugin never fetches anything at runtime.
 *
 *   bun tools/builder/simple-snapshot.ts            network: fetch the pinned commits (block-contract/v1 contracts,
 *                                                   content models and props schemas from buildytech/chat; the
 *                                                   wireframe DOM fixtures and tokens of ui8kit/prototypes), run the
 *                                                   pinned UI8Kit CLI in clean Latte and Twig projects (blocks,
 *                                                   compositions, bricks, og_* runtime), derive the catalogs and
 *                                                   write snapshot.json (sha256 of every file + digest)
 *   bun tools/builder/simple-snapshot.ts --check    offline: every file matches snapshot.json, the derived catalogs
 *                                                   are fresh, every variant has a structural wireframe
 *   [--chat <dir>] [--prototypes <dir>]            use existing checkouts (they must be at the pinned commits)
 *   [--cli-latte <dir>] [--cli-twig <dir>]          use existing CLI projects (init + add of the pinned blocks)
 *
 * Outputs (all generated, never edited by hand):
 *   contracts.json            the neutral contract DTO: block-contract/v1 + content model of every Simple block
 *   props.json                the published props schema of every Simple block (registry props/v0, narrowed)
 *   prototypes.json           the static wireframe HTML of every variant (the `unset` padding fixture of
 *                             ui8kit/prototypes catalog/snapshots, the DOM its Svelte prototypes are tested against)
 *   prototypes.tokens.css     per-element tokens of the prototypes (radius / shadow / density, read from the theme)
 *   <engine>/catalog.json     wpfasty-catalog@2 of the Simple blocks (derived from contracts.json + props.json)
 *   <engine>/blocks/**        the CLI's block templates (+ presets/, recipes/ JSON of the og_* runtime)
 *   <engine>/compose/*        the CLI's compositions; the Latte ones call the plugin's namespaced copy of the CLI
 *                             compose data (<engine>/php/Compose.php + compose.json), so a theme that ships another
 *                             CodegenX\Compose version never changes the Builder's recipes; Twig registers
 *                             compose_props to the same copy (BlockRenderer)
 *   <engine>/ui/**            the CLI's bricks (they render UI8Kit 3 primitives through ui_prim: primitives/)
 *   <engine>/php/{CnMerge,Runtime,OrganismExtension}.php  the CLI's og_* runtime
 *   <engine>/ui8kit.lock.json the CLI lock (provenance: registry item versions and file sha256)
 *   styles/                   kit base styles (theme.css @theme mapping, kit.css), kept across refreshes, digested
 */
import { execFileSync } from "node:child_process";
import { createHash } from "node:crypto";
import { cpSync, existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { dirname, join, resolve } from "node:path";

const ROOT = resolve(import.meta.dir, "../..");
const PLUGIN = join(ROOT, "plugins/wpfasty-builder");
const OUT = join(PLUGIN, "sources/ui8kit");
const CONFIG = JSON.parse(readFileSync(join(OUT, "snapshot.config.json"), "utf8"));
if (!/^\d+\.\d+\.\d+$/.test(CONFIG.catalogVersion ?? "")) { console.error("✗ snapshot.config.json catalogVersion must be semver x.y.z (registry versions are immutable)"); process.exit(1); }
const ENGINES = { latte: ".latte", twig: ".html.twig" } as const;
type Engine = keyof typeof ENGINES;
const KIT_NS = { latte: "WPFastyBuilder\\Kit\\Latte", twig: "WPFastyBuilder\\Kit\\Twig" } as const;
const ALLOWED_TAGS = new Set(["section", "header", "footer", "nav", "div", "span", "ul", "ol", "li", "article", "aside", "figure", "dl", "dt", "dd", "p"]);
const ALLOWED_ATTR = /^(class|aria-hidden|role|data-[a-z-]+)$/;

const arg = (n: string) => { const i = process.argv.indexOf(n); return i > 0 ? process.argv[i + 1] : undefined; };
const CHECK = process.argv.includes("--check");
const sha = (b: string | Buffer) => createHash("sha256").update(b).digest("hex");
const json = (v: unknown) => JSON.stringify(v, null, 2) + "\n";
const write = (p: string, c: string | Buffer) => { mkdirSync(dirname(p), { recursive: true }); writeFileSync(p, c); };
const fail = (m: string): never => { console.error(`✗ ${m}`); process.exit(1); };

function files(dir: string, rel = ""): string[] {
  if (!existsSync(join(dir, rel))) return [];
  return readdirSync(join(dir, rel)).sort().flatMap((n) => {
    const r = rel ? `${rel}/${n}` : n;
    return statSync(join(dir, r)).isDirectory() ? files(dir, r) : [r];
  });
}

// ---- derivation (offline, shared by refresh and --check) --------------------------------------------------------

type Schema = Record<string, any>;
type PropSpec = { name: string; type: string | { ts: string }; optional: boolean; docs: string };
type RecordSpec = { name: string; type: string; optional: boolean; docs: string };

/** the TS-like type of a props-schema property (text, enum, list of records) and the records it needs */
function propType(s: Schema, defs: Schema, records: Record<string, RecordSpec[]>, at: string): string | { ts: string } {
  if (s.$ref === "#/$defs/text") return "string";
  if (Array.isArray(s.enum)) return { ts: s.enum.map((v: unknown) => JSON.stringify(v)).join(" | ") };
  const list = (s.anyOf ?? []).find((x: Schema) => x.type === "array");
  const ref = list?.items?.anyOf?.find((x: Schema) => typeof x.$ref === "string" && !/\/(uiRef|data)$/.test(x.$ref))?.$ref;
  if (typeof ref === "string") {
    const name = ref.split("/").pop()!;
    const def = defs[name] ?? fail(`${at}: no $defs/${name}`);
    // record fields carry the TS type as a string (editor RecordField.type)
    const fields = Object.entries<Schema>(def.properties ?? {}).map(([k, v]) => {
      const t = propType(v, defs, records, `${at}.${k}`);
      return { name: k, type: typeof t === "string" ? t : t.ts, optional: !(def.required ?? []).includes(k), docs: String(v.description ?? "") };
    });
    if (records[name] && json(records[name]) !== json(fields)) fail(`record ${name} differs between blocks (${at})`);
    records[name] = fields;
    return { ts: `${name}[]` };
  }
  return fail(`${at}: unsupported props schema ${JSON.stringify(s).slice(0, 120)}`);
}

/** a sample value of a content model field: upstream fixture rule (ui8kit/prototypes manifest/fixtures.ts) adapted to WP:
 *  pictures stay empty (the media frame's theme placeholder), links are "#", lists have the wireframe's item count */
function sample(s: Schema, key: string, items: number, index: number): unknown {
  if (s.type === "array") {
    const n = Math.min(s.maxItems ?? Infinity, Math.max(s.minItems ?? 0, items));
    return Array.from({ length: n }, (_, i) => sample(s.items, key, items, i + 1));
  }
  if (s.type === "object") {
    const out: Record<string, unknown> = {};
    for (const [k, v] of Object.entries<Schema>(s.properties ?? {})) out[k] = sample(v, k, items, index);
    return out;
  }
  if (s.type !== "string") return fail(`model field ${key}: unsupported type ${s.type}`);
  const pattern = String(s.pattern ?? "");
  if (pattern.startsWith("^[A-Za-z0-9]")) return `${key}-${index || 1}`;
  if (pattern.includes("mailto")) return "#";
  if (pattern.includes("https?")) return "";
  const text = (s.maxLength ?? 0) >= 240 ? `A short ${key}, one sentence.` : `Sample ${key}`;
  return s.maxLength ? text.slice(0, s.maxLength) : text;
}

function deriveCatalog(contracts: Schema, props: Schema, engine: Engine): Schema {
  const records: Record<string, RecordSpec[]> = {};
  const blocks = contracts.blocks.map((b: Schema) => {
    const c = b.contract;
    const schema = props.items[b.item]?.schema ?? fail(`no props schema for ${b.item}`);
    const defs = schema.$defs ?? {};
    const required: string[] = b.model.required ?? [];
    // the axes are the variant's (its `set`), never a field of the inspector
    const propSpecs: PropSpec[] = Object.entries<Schema>(schema.properties).filter(([name]) => !(name in (c.axes ?? {}))).map(([name, s]) => ({
      name, type: propType(s, defs, records, `${b.item}.${name}`),
      optional: !(name in (b.model.properties ?? {})) || !required.includes(name), docs: String(s.description ?? ""),
    }));
    const content = sample(b.model, b.item, c.wireframe?.items ?? 3, 0) as Record<string, unknown>;
    const settings: Record<string, unknown> = {};
    for (const [k, v] of Object.entries<Schema>(c.settings ?? {})) if ("default" in v) settings[k] = v.default;
    const base = { id: b.item, ...content, ...settings };
    for (const k of Object.keys(base)) if (!(k in schema.properties)) fail(`${b.item}: sample key ${k} is not a prop`);
    return {
      id: b.item, title: b.title, docs: b.description, group: b.category,
      contract: { item: c.item, version: c.version, block: c.block, organism: c.organism, model: c.model },
      axes: c.axes, defaults: c.defaults, props: propSpecs, data: [],
      variants: c.variants.map((v: Schema) => ({
        id: v.id, label: v.label, set: v.axes, organism: c.organism, template: `blocks/${b.item}${ENGINES[engine]}`,
        props: { ...base, ...v.axes, ...(v.settings ?? {}) }, bindings: [],
      })),
    };
  });
  return {
    $schema: "https://github.com/alexy-os/wp-fasty/plugins/wpfasty-builder/catalog@2",
    format: 2, runtime: engine, templates: engine, version: CONFIG.catalogVersion, id: "ui8kit",
    label: "UI8Kit Simple blocks (block-contract/v1)",
    source: { repo: CONFIG.contracts.repo, commit: CONFIG.contracts.commit, cli: CONFIG.cli, prototypes: CONFIG.prototypes },
    contract: "block-contract/v1: flat content of the block's model (contracts.json); no data bindings",
    records, blocks, collections: [],
  };
}

/** the plugin's copy of the CLI's Compose class: namespaced, reading its own compose.json next to it */
function kitCompose(cli: string, engine: Engine): string {
  const from = engine === "twig" ? "namespace CodegenX\\Compose\\Twig;" : "namespace CodegenX\\Compose\\Latte;";
  if (!cli.includes(from)) fail(`${engine} Compose.php: no ${from}`);
  return cli.replace(from, `namespace ${KIT_NS[engine]};`)
    .replace("<?php\n", "<?php\n\n// Derived by tools/builder/simple-snapshot.ts from the pinned UI8Kit CLI's compose/Compose.php: namespace only.\n");
}

/** a Latte composition with its compose call pointed at the plugin's copy (Twig: the compose_props function) */
function kitComposition(cli: string, engine: Engine, rel: string): string {
  if (engine === "twig") return cli;
  const from = "\\CodegenX\\Compose\\Latte\\Compose::props(";
  if (!cli.includes(from)) fail(`latte ${rel}: no ${from}`);
  return cli.replaceAll(from, `\\${KIT_NS.latte}\\Compose::props(`);
}

/** structural wireframe only: allowlisted tags and attributes, no text, no URLs, no inline style */
function checkWireframe(key: string, html: string): void {
  if (/<!--|<script|<style|javascript:|\son[a-z]+=|\sstyle=|\shref=|\ssrc=/i.test(html)) fail(`${key}: wireframe has a forbidden construct`);
  for (const m of html.matchAll(/<([a-z0-9-]+)([^>]*)>/gi)) {
    if (!ALLOWED_TAGS.has(m[1]!.toLowerCase())) fail(`${key}: tag <${m[1]}> not allowed`);
    for (const a of m[2]!.matchAll(/\s([a-z-]+)(?:="[^"]*")?/gi)) if (!ALLOWED_ATTR.test(a[1]!)) fail(`${key}: attribute ${a[1]} not allowed`);
  }
  if (/>[^<\s][^<]*</.test(html)) fail(`${key}: wireframe has text content`);
}

// ---- refresh (network) ------------------------------------------------------------------------------------------

function checkout(repo: string, commit: string, given?: string): string {
  if (given) {
    const head = execFileSync("git", ["-C", given, "rev-parse", "HEAD"], { encoding: "utf8" }).trim();
    if (head !== commit) fail(`${given} is at ${head}, not the pinned ${repo} ${commit}`);
    return given;
  }
  const dir = mkdtempSync(join(tmpdir(), "simple-src-"));
  execFileSync("git", ["init", "-q", dir]);
  execFileSync("git", ["-C", dir, "fetch", "-q", "--depth", "1", `https://github.com/${repo}`, commit]);
  execFileSync("git", ["-C", dir, "checkout", "-q", "FETCH_HEAD"]);
  return dir;
}

function cli(engine: Engine, blocks: string[]): string {
  const given = arg(`--cli-${engine}`); // an existing CLI project of this engine with exactly these blocks added
  if (given) return given;
  const dir = mkdtempSync(join(tmpdir(), `simple-cli-${engine}-`));
  const run = (...a: string[]) => execFileSync("npx", ["-y", CONFIG.cli, ...a], { cwd: dir, stdio: ["ignore", "pipe", "inherit"] });
  run("init", "--yes", "--framework", engine);
  run("add", ...blocks, "--yes");
  return dir;
}

function refresh(): void {
  const chat = checkout(CONFIG.contracts.repo, CONFIG.contracts.commit, arg("--chat"));
  const proto = checkout(CONFIG.prototypes.repo, CONFIG.prototypes.commit, arg("--prototypes"));
  const chatPins = JSON.parse(readFileSync(join(chat, CONFIG.contracts.config), "utf8"));
  if (chatPins.prototypes.commit !== CONFIG.prototypes.commit) fail("the chat pins another ui8kit/prototypes commit");
  if (chatPins.ui8kit.cli !== CONFIG.cli) fail(`the chat pins ${chatPins.ui8kit.cli}, not ${CONFIG.cli}`);
  const blocksFile = JSON.parse(readFileSync(join(chat, CONFIG.contracts.blocks), "utf8"));
  const items: string[] = blocksFile.blocks.map((b: Schema) => b.item);
  if (json(items) !== json(chatPins.ui8kit.blocks)) fail("chat blocks.json and its snapshot config list other blocks");
  const propsFile = JSON.parse(readFileSync(join(chat, CONFIG.contracts.props), "utf8"));

  // styles/ is not from the CLI: kept across refreshes
  const styles = join(tmpdir(), `simple-styles-${process.pid}`);
  if (existsSync(join(OUT, "styles"))) cpSync(join(OUT, "styles"), styles, { recursive: true });
  rmSync(OUT, { recursive: true, force: true });
  mkdirSync(OUT, { recursive: true });
  if (existsSync(styles)) { cpSync(styles, join(OUT, "styles"), { recursive: true }); rmSync(styles, { recursive: true, force: true }); }
  write(join(OUT, "snapshot.config.json"), json(CONFIG));
  write(join(OUT, "contracts.json"), json({
    format: "wpfasty-simple-contracts", version: 1, source: { repo: CONFIG.contracts.repo, commit: CONFIG.contracts.commit, file: CONFIG.contracts.blocks, digest: blocksFile.digest },
    registry: blocksFile.registry, prototypes: blocksFile.prototypes,
    blocks: blocksFile.blocks.map((b: Schema) => ({ item: b.item, title: b.title, description: b.description, category: b.category, contract: b.contract, model: b.model })),
  }));
  write(join(OUT, "props.json"), json({
    format: "wpfasty-simple-props", version: propsFile.version, digest: propsFile.digest, registry: propsFile.registry,
    source: { repo: CONFIG.contracts.repo, commit: CONFIG.contracts.commit, file: CONFIG.contracts.props },
    items: Object.fromEntries(items.map((i) => [i, propsFile.items[i] ?? fail(`props.v0 lacks ${i}`)])),
  }));

  const variants: Record<string, string> = {};
  const fixtures: Record<string, string> = {};
  for (const b of blocksFile.blocks) {
    const file = join(proto, CONFIG.prototypes.snapshots, `${b.item}.snap.html`);
    const raw = readFileSync(file, "utf8");
    fixtures[b.item] = sha(raw);
    const lines = new Map([...raw.matchAll(/^<!-- ([^ ]+) -->\n(.*)$/gm)].map((m) => [m[1]!, m[2]!]));
    for (const v of b.contract.variants) {
      const html = lines.get(`${b.item}/${v.id}/unset`) ?? fail(`${b.item}.snap.html has no ${v.id}/unset`);
      variants[`${b.item}/${v.id}`] = html.trim();
    }
  }
  write(join(OUT, "prototypes.json"), json({
    format: "wpfasty-simple-prototypes", version: 1,
    source: { repo: CONFIG.prototypes.repo, commit: CONFIG.prototypes.commit, dir: CONFIG.prototypes.snapshots, fixtures },
    mode: "wireframe", padding: "unset", variants,
  }));
  write(join(OUT, "prototypes.tokens.css"), readFileSync(join(proto, CONFIG.prototypes.tokens)));

  for (const engine of Object.keys(ENGINES) as Engine[]) {
    const dir = cli(engine, items);
    const src = join(dir, "src");
    for (const rel of files(src)) {
      const bytes = readFileSync(join(src, rel));
      if (/^(blocks|ui)\//.test(rel)) write(join(OUT, engine, rel), bytes);
      else if (rel.startsWith("organisms/")) write(join(OUT, engine, "php", rel.slice("organisms/".length)), bytes);
      else if (rel === "compose/Compose.php") write(join(OUT, engine, "php/Compose.php"), kitCompose(bytes.toString("utf8"), engine));
      else if (rel === "compose/compose.json") write(join(OUT, engine, "php/compose.json"), bytes);
      else if (/^compose\/[A-Za-z]+(\.latte|\.html\.twig)$/.test(rel)) write(join(OUT, engine, rel), kitComposition(bytes.toString("utf8"), engine, rel));
      // runtime/ (the plugin's primitives own the UI8Kit 3 runtime), composer.json, the CLI's Twig ComposeExtension
      else if (!/^(runtime\/|composer\.json$|compose\/ComposeExtension\.php$)/.test(rel)) fail(`${engine} ${rel}: an unexpected CLI file (extend the snapshot deliberately)`);
    }
    write(join(OUT, engine, "ui8kit.lock.json"), readFileSync(join(dir, "ui8kit.lock.json")));
    if (!arg(`--cli-${engine}`)) rmSync(dir, { recursive: true, force: true });
  }
  derive();
  write(join(OUT, "snapshot.json"), json(manifest()));
  verify();
  console.log(`✓ sources/ui8kit: ${items.length} blocks, ${Object.keys(variants).length} variants from ${CONFIG.contracts.repo} ${CONFIG.contracts.commit.slice(0, 7)} + ${CONFIG.prototypes.repo} ${CONFIG.prototypes.commit.slice(0, 7)} + ${CONFIG.cli}`);
}

/** the derived files from the pinned ones */
function derived(): Record<string, string> {
  const contracts = JSON.parse(readFileSync(join(OUT, "contracts.json"), "utf8"));
  const props = JSON.parse(readFileSync(join(OUT, "props.json"), "utf8"));
  const out: Record<string, string> = {};
  for (const engine of Object.keys(ENGINES) as Engine[]) out[`${engine}/catalog.json`] = json(deriveCatalog(contracts, props, engine));
  return out;
}

function derive(): void {
  for (const [rel, content] of Object.entries(derived())) write(join(OUT, rel), content);
}

function manifest(): Schema {
  const all = files(OUT).filter((f) => f !== "snapshot.json");
  const list = Object.fromEntries(all.map((f) => [f, sha(readFileSync(join(OUT, f)))]));
  const h = createHash("sha256");
  for (const [f, s] of Object.entries(list)) h.update(`${f}\0${s}\0`);
  return {
    format: "wpfasty-simple-snapshot", version: 1, cli: CONFIG.cli, registry: CONFIG.registry,
    contracts: CONFIG.contracts, prototypes: CONFIG.prototypes, files: list, digest: `sha256:${h.digest("hex")}`,
  };
}

function verify(): void {
  const snap = JSON.parse(readFileSync(join(OUT, "snapshot.json"), "utf8"));
  const now = manifest();
  if (json(now.files) !== json(snap.files) || now.digest !== snap.digest) {
    const diff = [...new Set([...Object.keys(now.files), ...Object.keys(snap.files)])].filter((f) => now.files[f] !== snap.files[f]);
    fail(`sources/ui8kit does not match snapshot.json: ${diff.slice(0, 10).join(", ")}`);
  }
  if (json(snap.contracts) !== json(CONFIG.contracts) || json(snap.prototypes) !== json(CONFIG.prototypes) || snap.cli !== CONFIG.cli) fail("snapshot.json pins differ from snapshot.config.json");
  for (const [rel, content] of Object.entries(derived())) if (readFileSync(join(OUT, rel), "utf8") !== content) fail(`${rel} is stale (re-derive)`);
  const contracts = JSON.parse(readFileSync(join(OUT, "contracts.json"), "utf8"));
  const protos = JSON.parse(readFileSync(join(OUT, "prototypes.json"), "utf8"));
  for (const b of contracts.blocks) for (const v of b.contract.variants) {
    const html = protos.variants[`${b.item}/${v.id}`] ?? fail(`no prototype for ${b.item}/${v.id}`);
    checkWireframe(`${b.item}/${v.id}`, html);
  }
  for (const engine of Object.keys(ENGINES) as Engine[]) {
    for (const rel of files(join(OUT, engine))) {
      if (engine === "latte" && /^compose\/.*\.latte$/.test(rel) && readFileSync(join(OUT, engine, rel), "utf8").includes("\\CodegenX\\Compose\\")) fail(`${rel} calls the theme's compose class`);
    }
    for (const b of contracts.blocks) if (!existsSync(join(OUT, engine, `blocks/${b.item}${ENGINES[engine]}`))) fail(`${engine}: no template for ${b.item}`);
  }
  console.log(`✓ sources/ui8kit reproduces (${snap.digest.slice(0, 19)}…, ${contracts.blocks.length} blocks)`);
}

if (CHECK) verify();
else if (process.argv.includes("--derive")) { derive(); write(join(OUT, "snapshot.json"), json(manifest())); verify(); }
else refresh();
