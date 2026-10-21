/**
 * Packs ONE runtime of a builder source as a registry (wpfasty-catalog@2, docs/agent/builder-registry.md):
 *   <out>/blocks.zip    blocks/ and ui/ of that runtime (templates, .json, .md; no PHP)
 *   <out>/catalog.json  the runtime's catalog + version + archive {file, bytes, sha256}
 * Usage: bun tools/builder/pack-source.ts <latte|twig> <out> [--src plugins/wpfasty-builder/sources/ui8kit] [--version x.y.z]
 * The zip is deterministic (sorted entries, fixed timestamps), so the same source gives the same sha256.
 */
import { createHash } from "node:crypto";
import { spawnSync } from "node:child_process";
import { cpSync, mkdirSync, mkdtempSync, readFileSync, readdirSync, rmSync, statSync, utimesSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { dirname, join, relative, resolve } from "node:path";

const [runtime, outArg] = process.argv.slice(2);
const opt = (n: string, d: string) => { const i = process.argv.indexOf(n); return i > 0 ? process.argv[i + 1]! : d; };
if (runtime !== "latte" && runtime !== "twig") throw new Error("usage: pack-source.ts <latte|twig> <out> [--src dir] [--version x.y.z]");
if (!outArg) throw new Error("missing <out>");
const root = resolve(import.meta.dir, "../..");
const src = resolve(root, opt("--src", "plugins/wpfasty-builder/sources/ui8kit"), runtime);
const out = resolve(outArg);
const catalog = JSON.parse(readFileSync(join(src, "catalog.json"), "utf8"));
if (catalog.format !== 2 || catalog.runtime !== runtime) throw new Error(`${src}/catalog.json is not a ${runtime} catalog@2`);
const version = opt("--version", catalog.version);
if (!/^\d+\.\d+\.\d+$/.test(version)) throw new Error(`version must be semver x.y.z (got ${version})`);

const ext = runtime === "twig" ? ".twig" : ".latte";
const walk = (d: string): string[] => readdirSync(d).sort().flatMap((n) => {
  const p = join(d, n);
  return statSync(p).isDirectory() ? walk(p) : [p];
});
const files: string[] = [];
for (const dir of ["blocks", "ui"]) {
  for (const f of walk(join(src, dir))) {
    const rel = relative(src, f).split("\\").join("/");
    if (rel.endsWith(ext) || rel.endsWith(".json") || rel.endsWith(".md")) files.push(rel);
  }
}
files.sort();
const missing = catalog.blocks.flatMap((b: any) => b.variants.map((v: any) => v.template)).filter((t: string) => !files.includes(t));
if (missing.length) throw new Error(`templates missing from the archive: ${missing.slice(0, 5).join(", ")}`);
// deterministic zip: staged copies with one fixed mtime, sorted entries, no extra attributes (zip -X -D)
const stage = mkdtempSync(join(tmpdir(), "wpfb-pack-"));
const fixed = new Date("2026-01-01T00:00:00Z");
for (const rel of files) {
  mkdirSync(dirname(join(stage, rel)), { recursive: true });
  cpSync(join(src, rel), join(stage, rel));
  utimesSync(join(stage, rel), fixed, fixed);
}
mkdirSync(out, { recursive: true });
rmSync(join(out, "blocks.zip"), { force: true });
const z = spawnSync("zip", ["-X", "-D", "-q", "-9", join(out, "blocks.zip"), "-@"], { cwd: stage, input: files.join("\n") + "\n", env: { ...process.env, TZ: "UTC" } });
rmSync(stage, { recursive: true, force: true });
if (z.status !== 0) throw new Error(`zip failed: ${z.stderr}`);
const zip = readFileSync(join(out, "blocks.zip"));
const sha256 = createHash("sha256").update(zip).digest("hex");
const { archive: _a, ...rest } = catalog;
writeFileSync(join(out, "catalog.json"), JSON.stringify({ ...rest, version, archive: { file: "blocks.zip", bytes: zip.length, sha256 } }) + "\n");
console.log(`${runtime} registry ${version} → ${relative(process.cwd(), out) || out}: ${files.length} files, blocks.zip ${(zip.length / 1024).toFixed(0)} KB, sha256 ${sha256.slice(0, 12)}…`);
