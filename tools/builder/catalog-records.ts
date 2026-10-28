/**
 * Adds `records` to the bundled builder catalogs (sources/ui8kit/{latte,twig}/catalog.json): the field lists of every
 * record type the block props name (Stat, Feature, LinkActions, HeadLink…, transitively), read from the codegen-x specs
 * of the commit the catalogs were exported from (`source.commit`; packages/compose-gen/specs/{organisms,patterns,parts}/*.json
 * `records`). The exporter (tools/builder/export-ui8kit.ts) writes them too, from the checkout (specRecordsDir). The editor turns them into typed fields (editor/src/fields/schema.ts). Only `records` is written; blocks,
 * variants and templates stay as exported (tools/builder/export-ui8kit.ts).
 *   bun tools/builder/catalog-records.ts [--cgx /path/to/codegen-x/ui8kit] [--check]
 * --check: exit 1 when a catalog's records differ from the specs (needs the codegen-x checkout).
 */
import { execFileSync } from "node:child_process";
import { readdirSync, readFileSync, writeFileSync } from "node:fs";
import { join, resolve } from "node:path";

const arg = (n: string, d: string) => { const i = process.argv.indexOf(n); return i > 0 ? process.argv[i + 1]! : d; };
const CGX = resolve(arg("--cgx", process.env.CGX_DIR ?? "/workspace/cgx-ui8kit"));
const CHECK = process.argv.includes("--check");
const SRC = resolve(import.meta.dir, "../../plugins/wpfasty-builder/sources/ui8kit");

export type RecordField = { name: string; type: string; optional?: boolean; docs?: string; default?: unknown };
/** type names that are not records (TrustedHtml: the codegen-x scalar of trusted markup, `content|html` of a source) */
const BUILTIN = new Set(["Record", "Array", "Partial", "Readonly", "TrustedHtml"]);
/** record names a TS type string refers to */
export const refs = (ts: string): string[] => [...ts.matchAll(/\b([A-Z][A-Za-z0-9]*)\b/g)].map((m) => m[1]!).filter((n) => !BUILTIN.has(n));
const tsOf = (t: unknown): string => (typeof t === "string" ? t : (t as any)?.ts ?? (t as any)?.record ?? "");

const SPEC_DIRS = ["organisms", "patterns", "parts"];

/** records of a list of spec files (path, JSON text), in path order */
function collect(entries: Array<[string, string]>): Map<string, RecordField[]> {
  const out = new Map<string, RecordField[]>();
  for (const [f, text] of entries) {
    const spec = JSON.parse(text);
    for (const [name, fields] of Object.entries(spec.records ?? {}) as [string, any[]][]) {
      const list: RecordField[] = fields.map((x) => {
        const r: RecordField = { name: String(x.name), type: tsOf(x.type) };
        if (x.optional) r.optional = true;
        if (x.docs) r.docs = String(x.docs);
        if (x.default !== undefined) r.default = x.default;
        return r;
      });
      const prev = out.get(name);
      if (prev && JSON.stringify(prev) !== JSON.stringify(list)) throw new Error(`record ${name} differs between specs (${f})`);
      out.set(name, list);
    }
  }
  return out;
}

/** records of the specs of a codegen-x commit (git show, no checkout needed) */
function specRecords(commit: string): Map<string, RecordField[]> {
  const git = (...a: string[]) => execFileSync("git", ["-C", CGX, ...a], { maxBuffer: 1 << 28 }).toString();
  const files = git("ls-tree", "-r", "--name-only", commit, "packages/compose-gen/specs")
    .split("\n").filter((f) => /^packages\/compose-gen\/specs\/(organisms|patterns|parts)\/[^/]+\.json$/.test(f)).sort();
  return collect(files.map((f) => [f, git("show", `${commit}:${f}`)]));
}

/** records of a specs directory (packages/compose-gen/specs of a checkout) */
export function specRecordsDir(specs: string): Map<string, RecordField[]> {
  const files = SPEC_DIRS.flatMap((d) => readdirSync(join(specs, d)).filter((f) => f.endsWith(".json")).map((f) => `${d}/${f}`)).sort();
  return collect(files.map((f) => [f, readFileSync(join(specs, f), "utf8")]));
}

/** the records a catalog's block props need, transitively, sorted by name */
export function recordsFor(catalog: any, all: Map<string, RecordField[]>): Record<string, RecordField[]> {
  const need = new Set<string>();
  const todo: string[] = [];
  const visit = (ts: string) => { for (const n of refs(ts)) if (!need.has(n)) { need.add(n); todo.push(n); } };
  for (const b of catalog.blocks) for (const p of b.props) visit(tsOf(p.type));
  for (const d of catalog.blocks.flatMap((b: any) => b.data ?? [])) if (d.record) visit(d.record);
  while (todo.length) for (const f of all.get(todo.pop()!) ?? []) visit(f.type);
  const missing = [...need].filter((n) => !all.has(n));
  if (missing.length) throw new Error(`records not found in the specs: ${missing.join(", ")}`);
  return Object.fromEntries([...need].sort().map((n) => [n, all.get(n)!]));
}

if (import.meta.main) {
  let stale = 0;
  let cache: Map<string, RecordField[]> | null = null;
  for (const runtime of ["latte", "twig"]) {
    const file = join(SRC, runtime, "catalog.json");
    const text = readFileSync(file, "utf8");
    const cat = JSON.parse(text);
    const commit = cat.source?.commit;
    if (!commit) throw new Error(`${file}: no source.commit`);
    cache ??= specRecords(commit);
    const records = recordsFor(cat, cache);
    const { blocks, collections, records: _old, ...meta } = cat;
    const next = JSON.stringify({ ...meta, records, blocks, collections }) + "\n";
    if (next === text) { console.log(`${runtime}: records up to date (${Object.keys(records).length})`); continue; }
    if (CHECK) { stale++; console.error(`${runtime}: records are stale — run bun tools/builder/catalog-records.ts`); continue; }
    writeFileSync(file, next);
    console.log(`${runtime}: ${Object.keys(records).length} records from codegen-x ${String(commit).slice(0, 7)}`);
  }
  process.exit(stale ? 1 : 0);
}
