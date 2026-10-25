/**
 * Exports the ui8kit block library of codegen-x (codegen-x/ui8kit main: packages/compose-gen specs + the generated
 * packages/kit-compose server organisms + the UI8Kit 3 kit primitives) as a wpfasty-builder source:
 *   plugins/wpfasty-builder/sources/ui8kit/latte/   catalog.json (wpfasty-catalog@2, runtime latte) + one template root:
 *                                                   blocks/ (organisms), compose/ (kit-compose compositions), icons/,
 *                                                   ui/ (the kit primitives), php/ (og_* runtime: CnMerge, Runtime,
 *                                                   OrganismExtension)
 *   plugins/wpfasty-builder/sources/ui8kit/twig/    the same for Twig (same specs, same DOM: tools/parity)
 *   plugins/wpfasty-builder/sources/ui8kit/styles/  theme.css, kit.css
 * The catalog is a hand-run artifact pinned to a codegen-x commit (`source.commit`, the checkout's HEAD; a dirty checkout
 * is refused). Usage:
 *   bun tools/builder/export-ui8kit.ts [--cgx /path/to/codegen-x] [--out dir]
 *   bun tools/builder/export-ui8kit.ts --check [--cgx dir]   the committed source must equal an export of source.commit
 *     (the checkout must be at that commit); without a checkout (CI without access to the private repo): --offline
 *     checks the recorded file digest (`source.digest`) of the committed tree only.
 * Each runtime folder is self-contained; a registry serves one of them (tools/builder/pack-source.ts).
 */
import { cpSync, existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { execFileSync } from "node:child_process";
import { createHash } from "node:crypto";
import { tmpdir } from "node:os";
import { join, relative, resolve } from "node:path";
import { runtimeCatalog } from "./catalog-v2.ts";
import { recordsFor, specRecordsDir } from "./catalog-records.ts";

const arg = (n: string, d: string) => { const i = process.argv.indexOf(n); return i > 0 ? process.argv[i + 1]! : d; };
const CHECK = process.argv.includes("--check");
const OFFLINE = process.argv.includes("--offline");
const CGX = resolve(arg("--cgx", process.env.CGX_DIR ?? "/workspace/cgx-ui8kit"));
const COMMITTED = resolve(import.meta.dir, "../../plugins/wpfasty-builder/sources/ui8kit");
/** the exported tree (tracked files only: sources/ui8kit/catalog.json at the top is not part of it) */
const PARTS = ["latte", "twig", "styles"];

function files(dir: string, rel = ""): string[] {
  if (!existsSync(join(dir, rel))) return [];
  return readdirSync(join(dir, rel)).sort().flatMap((n) => {
    const r = rel ? `${rel}/${n}` : n;
    return statSync(join(dir, r)).isDirectory() ? files(dir, r) : [r];
  });
}
/** sha256 over path + bytes of every exported file except the catalogs (which carry the digest) */
function treeDigest(out: string): string {
  const h = createHash("sha256");
  for (const p of PARTS) for (const f of files(join(out, p))) {
    if (f === "catalog.json") continue;
    h.update(`${p}/${f}\0`).update(readFileSync(join(out, p, f))).update("\0");
  }
  return `sha256:${h.digest("hex")}`;
}

if (CHECK && OFFLINE) {
  let bad = 0;
  const digest = treeDigest(COMMITTED);
  for (const r of ["latte", "twig"]) {
    const c = JSON.parse(readFileSync(join(COMMITTED, r, "catalog.json"), "utf8"));
    if (c.source?.digest !== digest) { bad++; console.error(`✗ ${r}/catalog.json source.digest ${c.source?.digest} ≠ tree ${digest}`); }
  }
  if (!bad) console.log(`✓ ui8kit source tree matches source.digest (codegen-x ${JSON.parse(readFileSync(join(COMMITTED, "latte/catalog.json"), "utf8")).source.commit.slice(0, 8)}); full reproduction needs a codegen-x checkout`);
  process.exit(bad ? 1 : 0);
}

const git = (...a: string[]) => execFileSync("git", ["-C", CGX, ...a]).toString().trim();
const commit = git("rev-parse", "HEAD");
const dirty = git("status", "--porcelain", "--untracked-files=no", "--", "packages/compose-gen", "packages/kit-compose", "packages/kit");
if (dirty) throw new Error(`${CGX}: tracked changes under packages/ — export from a clean commit:\n${dirty}`);
if (CHECK) {
  const pinned = JSON.parse(readFileSync(join(COMMITTED, "latte/catalog.json"), "utf8")).source?.commit;
  if (pinned !== commit) { console.error(`✗ codegen-x checkout is at ${commit}, the catalog is pinned to ${pinned}: git -C ${CGX} checkout ${pinned}`); process.exit(1); }
}
const OUT = CHECK ? mkdtempSync(join(tmpdir(), "wpfb-ui8kit-")) : resolve(arg("--out", COMMITTED));

const GEN = join(CGX, "packages/compose-gen");
const KC = join(CGX, "packages/kit-compose");
const KIT = join(CGX, "packages/kit");
for (const d of [join(KC, "latte/src/blocks"), join(KC, "twig/src/blocks"), join(KIT, "latte/src/ui"), join(KIT, "twig/src/ui")])
  if (!existsSync(d)) throw new Error(`missing ${d} (codegen-x main with packages/kit-compose server organisms)`);

const S = await import(join(GEN, "screens/src/index.ts"));
const { evaluate } = await import(join(GEN, "check/src/templ/blocks.ts"));
const { readPath } = await import(join(GEN, "runtimes/core/assets/app/core.ts"));
const { organismTemplate, latte } = await import(join(GEN, "runtimes/twig/src/index.ts"));
const { blockKey, blockNode, galleryOf, isOp, isRef, isGroup } = S;

const inputs = S.loadInputs(GEN);
if (inputs.problems.length) throw new Error(`compose-gen inputs:\n  ${inputs.problems.slice(0, 20).join("\n  ")}`);
const organisms = Object.fromEntries(inputs.organisms.map((o: any) => [o.value.name, o.value]));
const presets = Object.fromEntries(inputs.presets.map((p: any) => [p.value.name, p.value.values]));
const fixtures = new Map<string, any[]>(inputs.collections.map((c: any) => [c.value.collection, c.value.entries]));
const dict = inputs.dictionary.value;

type Binding = { path: string[]; data: string; pick?: string };
/** where a block's props read a data source directly (a list) or pick one record of it */
function bindingsOf(v: any, as: Set<string>, path: string[] = [], out: Binding[] = []): Binding[] {
  if (!v || typeof v !== "object") return out;
  if (isRef(v)) { if (as.has(v.$)) out.push({ path, data: v.$ }); return out; }
  if (isOp(v)) {
    if (v.op === "pick" && isRef(v.args[0]) && as.has(v.args[0].$) && typeof v.args[1] === "string") out.push({ path, data: v.args[0].$, pick: v.args[1] });
    return out;
  }
  if (isGroup(v) || !Array.isArray(v)) for (const [k, x] of Object.entries(v)) if (k !== "...") bindingsOf(x, as, [...path, k], out);
  return out;
}

const scopeOf = (b: any) => {
  const scope: Record<string, unknown> = { ui: dict };
  for (const d of b.data ?? []) scope[d.as] = (fixtures.get(d.collection) ?? []).map((e) => Object.fromEntries(Object.entries(d.fields).map(([n, p]) => [n, readPath(e, p as string)])));
  return scope;
};
const propsOf = (b: any, v: any) => {
  const node: any = blockNode(b, v, { id: blockKey(b), level: 2 }, organisms);
  const o = organisms[node.organism];
  const scope = scopeOf(b);
  const props: Record<string, unknown> = {};
  for (const [k, x] of Object.entries(node.props ?? {})) {
    if (!o.props.some((p: any) => p.name === k) && k !== "className") continue;
    const val = evaluate(x, scope, presets);
    if (val !== undefined) props[k] = val;
  }
  return { node, props };
};

const gallery = galleryOf(inputs.blocks.map((x: any) => x.value)) as any[];
const blocks: any[] = [];
for (const b of gallery) {
  const key = blockKey(b);
  const as = new Set<string>((b.data ?? []).map((d: any) => d.as));
  const variants = b.variants.map((v: any) => {
    const { node, props } = propsOf(b, v);
    return { id: v.id, set: v.set ?? {}, organism: node.organism, template: organismTemplate(node.organism, latte), props, bindings: bindingsOf(node.props, as) };
  });
  const o = organisms[variants[0].organism];
  blocks.push({
    id: key,
    title: String(b.name ?? key),
    docs: b.docs ?? "",
    group: b.group ?? "general",
    axes: b.axes ?? {},
    defaults: b.defaults ?? {},
    props: o.props.map((p: any) => ({ name: p.name, type: p.type, optional: !!p.optional, docs: p.docs ?? "" })),
    data: (b.data ?? []).map((d: any) => ({ as: d.as, kind: d.kind, record: d.record, fields: d.fields })),
    variants,
  });
}

// collections: block groups + the template screens (their pages become presets)
const groups = [...new Set(blocks.map((b) => b.group))].sort();
const collections: any[] = groups.map((g) => ({ id: g, label: g.replace(/^./, (c: string) => c.toUpperCase()), type: "group", blocks: blocks.filter((b) => b.group === g).map((b) => b.id), pages: [] }));
const byKey = new Map(blocks.map((b) => [b.id, b]));
const rawBlocks = new Map(gallery.map((b) => [blockKey(b), b]));
for (const s of inputs.screens.filter((x: any) => x.composed && String(x.value.name).startsWith("Theme")).map((x: any) => x.composed)) {
  const id = String(s.id).replace(/^screen\.theme-/, "");
  const pages = Object.entries(s.areas ?? {}).map(([area, items]: [string, any]) => ({
    id: area,
    blocks: (items as any[]).filter((it) => byKey.has(it.block)).map((it) => {
      const b = rawBlocks.get(it.block)!;
      const v = b.variants.find((x: any) => x.id === it.variant) ?? b.variants[0];
      const own = Object.fromEntries(Object.entries(it.props ?? {}).map(([k, x]) => [k, evaluate(x as any, scopeOf(b), presets)]).filter(([, x]) => x !== undefined && x !== null && x !== "" && !(typeof x === "string" && x.startsWith("#/"))));
      return { block: it.block, variant: v.id, props: own };
    }),
  }));
  collections.push({ id, label: id.split("-").map((w) => w[0]!.toUpperCase() + w.slice(1)).join(" "), type: "template", docs: s.docs ?? "", blocks: [...new Set(pages.flatMap((p) => p.blocks.map((x) => x.block)))], pages });
}

// the template roots: one folder per runtime with blocks/, compose/, icons/ (kit-compose), ui/ (kit), php/ (og_* runtime)
for (const r of ["latte", "twig"] as const) {
  const dst = join(OUT, r);
  rmSync(dst, { recursive: true, force: true });
  mkdirSync(join(dst, "php"), { recursive: true });
  for (const d of ["blocks", "compose", "icons"]) cpSync(join(KC, r, "src", d), join(dst, d), { recursive: true });
  cpSync(join(KIT, r, "src/ui"), join(dst, "ui"), { recursive: true });
  for (const f of readdirSync(join(KC, r, "src/organisms")).filter((f) => f.endsWith(".php")).sort()) cpSync(join(KC, r, "src/organisms", f), join(dst, "php", f));
  writeFileSync(join(dst, "README.md"), [
    `# ui8kit ${r === "latte" ? "Latte" : "Twig"} source (generated, do not edit)`,
    "",
    `Exported by tools/builder/export-ui8kit.ts from codegen-x/ui8kit ${commit}:`,
    `- blocks/, compose/, icons/: packages/kit-compose/${r}/src (server organisms on kit-compose)`,
    `- ui/: packages/kit/${r}/src/ui (UI8Kit 3 primitives; their PHP runtime is the plugin's primitives/${r})`,
    `- php/: packages/kit-compose/${r}/src/organisms/*.php (og_* functions)`,
    "",
  ].join("\n"));
}
mkdirSync(join(OUT, "styles"), { recursive: true });
for (const f of ["theme.css", "kit.css"]) cpSync(join(GEN, "runtimes/core/assets/styles", f), join(OUT, "styles", f));

const base = {
  id: "ui8kit",
  label: "ui8kit blocks (codegen-x)",
  source: { repo: "codegen-x/ui8kit", branch: "main", commit, digest: treeDigest(OUT) },
  contract: "Codex v0.4: bound data are entries mapped by `fields` (dotted paths, pipes |date |ago |ago-days |ago-short |number |route |media |term: |author); money = integer minor units + currency",
  blocks,
  collections,
};
const records = recordsFor(base, specRecordsDir(join(GEN, "specs")));
for (const r of ["latte", "twig"] as const) {
  const { blocks: bl, collections: co, ...meta } = runtimeCatalog(base, r);
  writeFileSync(join(OUT, r, "catalog.json"), JSON.stringify({ ...meta, records, blocks: bl, collections: co }) + "\n");
}

if (CHECK) {
  const diffs: string[] = [];
  for (const p of PARTS) {
    const a = new Set(files(join(COMMITTED, p))), b = new Set(files(join(OUT, p)));
    for (const f of new Set([...a, ...b])) {
      if (!a.has(f)) diffs.push(`+ ${p}/${f}`);
      else if (!b.has(f)) diffs.push(`- ${p}/${f}`);
      else if (!readFileSync(join(COMMITTED, p, f)).equals(readFileSync(join(OUT, p, f)))) diffs.push(`~ ${p}/${f}`);
    }
  }
  rmSync(OUT, { recursive: true, force: true });
  if (diffs.length) { console.error(`✗ sources/ui8kit is not the export of codegen-x ${commit.slice(0, 8)} (${diffs.length}):\n  ${diffs.slice(0, 30).join("\n  ")}`); process.exit(1); }
  console.log(`✓ sources/ui8kit reproduces from codegen-x ${commit.slice(0, 8)}`);
} else {
  const size = readFileSync(join(OUT, "latte", "catalog.json")).length;
  console.log(`${blocks.length} blocks, ${blocks.reduce((n, b) => n + b.variants.length, 0)} variants, ${collections.length} collections, ${Object.keys(records).length} records; catalog ${(size / 1024).toFixed(0)} KB; codegen-x ${commit.slice(0, 8)} ${relative(process.cwd(), OUT) || "."}`);
}
