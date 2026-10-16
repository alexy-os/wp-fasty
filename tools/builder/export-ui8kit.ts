/**
 * Exports the ui8kit block library of codegen-x (branch contract-v04) as a wpfasty-builder source:
 *   plugins/wpfasty-builder/sources/ui8kit/catalog.json   blocks, variants, props, bindings, collections
 *   plugins/wpfasty-builder/sources/ui8kit/latte/         the generated Latte package (blocks/, ui/, php/)
 * Usage: bun tools/builder/export-ui8kit.ts [--cgx /path/to/codegen-x/ui8kit]
 * The catalog shape is the source contract of the builder: a remote registry serves the same catalog.json + latte.zip.
 */
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { execSync } from "node:child_process";
import { join, resolve } from "node:path";

const arg = (n: string, d: string) => { const i = process.argv.indexOf(n); return i > 0 ? process.argv[i + 1]! : d; };
const CGX = resolve(arg("--cgx", process.env.CGX_DIR ?? "/workspace/cgx-ui8kit"));
const OUT = resolve(import.meta.dir, "../../plugins/wpfasty-builder/sources/ui8kit");
const LATTE = join(CGX, "packages/latte/ui");
if (!existsSync(join(LATTE, "blocks"))) throw new Error(`no generated Latte package at ${LATTE}`);

const { planBuild } = await import(join(CGX, "xgen/generator/src/index.ts"));
const screens = await import(join(CGX, "xgen/screens/src/index.ts"));
const { organismTemplate, latte } = await import(join(CGX, "xgen/runtimes/twig/src/index.ts"));
const { evaluate } = await import(join(CGX, "xgen/check/src/templ/blocks.ts"));
const { readPath } = await import(join(CGX, "xgen/runtimes/core/assets/app/core.ts"));
const { blockKey, blockNode, galleryOf, isOp, isRef, isGroup } = screens;

const plan = planBuild(CGX);
const organisms = Object.fromEntries(plan.inputs.organisms.map((o: any) => [o.value.name, o.value]));
const presets = Object.fromEntries(plan.inputs.presets.map((p: any) => [p.value.name, p.value.values]));
const fixtures = new Map<string, any[]>(plan.inputs.collections.map((c: any) => [c.value.collection, c.value.entries]));
const dict = plan.inputs.dictionary.value;
const git = (c: string) => execSync(`git -C ${CGX} ${c}`).toString().trim();

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
  for (const d of b.data) scope[d.as] = (fixtures.get(d.collection) ?? []).map((e) => Object.fromEntries(Object.entries(d.fields).map(([n, p]) => [n, readPath(e, p as string)])));
  return scope;
};
const propsOf = (b: any, v: any, extra: Record<string, unknown> = {}) => {
  const node: any = blockNode(b, v, { id: blockKey(b), level: 2, ...extra }, organisms);
  const o = organisms[node.organism];
  const scope = scopeOf(b);
  const props: Record<string, unknown> = {};
  for (const [k, x] of Object.entries(node.props ?? {})) {
    if (!o.props.some((p: any) => p.name === k) && k !== "className") continue;
    const val = evaluate(x, scope, presets);
    if (val !== undefined) props[k] = val;
  }
  return { node, props, organism: o };
};

const blocks: any[] = [];
for (const b of galleryOf(plan.inputs.blocks.map((x: any) => x.value)) as any[]) {
  const key = blockKey(b);
  const as = new Set<string>(b.data.map((d: any) => d.as));
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
    data: b.data.map((d: any) => ({ as: d.as, kind: d.kind, record: d.record, fields: d.fields })),
    variants,
  });
}

// collections: block groups + the ten template screens (their pages become presets)
const groups = [...new Set(blocks.map((b) => b.group))].sort();
const collections: any[] = groups.map((g) => ({ id: g, label: g.replace(/^./, (c: string) => c.toUpperCase()), type: "group", blocks: blocks.filter((b) => b.group === g).map((b) => b.id), pages: [] }));
const byKey = new Map(blocks.map((b) => [b.id, b]));
const rawBlocks = new Map((galleryOf(plan.inputs.blocks.map((x: any) => x.value)) as any[]).map((b) => [blockKey(b), b]));
for (const s of plan.inputs.screens.filter((x: any) => x.composed && String(x.value.name).startsWith("Theme")).map((x: any) => x.composed)) {
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

const catalog = {
  $schema: "https://github.com/godudy/wpfasty/plugins/wpfasty-builder/catalog@1",
  id: "ui8kit",
  label: "ui8kit blocks (codegen-x)",
  version: 1,
  source: { repo: "codegen-x/ui8kit", branch: git("rev-parse --abbrev-ref HEAD"), commit: git("rev-parse HEAD") },
  templates: "latte",
  contract: "Codex v0.4: bound data are entries mapped by `fields` (dotted paths, pipes |date |ago |ago-days |ago-short); money = integer minor units + currency",
  blocks,
  collections,
};

rmSync(join(OUT, "latte"), { recursive: true, force: true });
mkdirSync(join(OUT, "latte"), { recursive: true });
for (const d of ["blocks", "ui", "php"]) cpSync(join(LATTE, d), join(OUT, "latte", d), { recursive: true });
cpSync(join(LATTE, "README.md"), join(OUT, "latte", "README.md"));
mkdirSync(join(OUT, "styles"), { recursive: true });
for (const f of ["theme.css", "kit.css"]) cpSync(join(CGX, "packages/react/ui/src/styles", f), join(OUT, "styles", f));
writeFileSync(join(OUT, "catalog.json"), JSON.stringify(catalog) + "\n");
const size = readFileSync(join(OUT, "catalog.json")).length;
console.log(`${blocks.length} blocks, ${blocks.reduce((n, b) => n + b.variants.length, 0)} variants, ${collections.length} collections; catalog ${(size / 1024).toFixed(0)} KB`);
