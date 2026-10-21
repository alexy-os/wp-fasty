/**
 * Verifies one runtime registry (docs/agent/builder-registry.md) from a directory or a URL — the same rules the plugin
 * import applies (Catalog::check, RemoteSource), so a registry that passes here imports:
 *   catalog.json is wpfasty-catalog@2 of <runtime> (format 2, runtime = templates = <runtime>, semver version, source),
 *   blocks.zip matches archive {bytes, sha256}, holds only blocks/ and ui/ of that runtime (no other runtime's
 *   templates, no PHP), and contains every variant template.
 * Usage: bun tools/builder/registry-verify.ts <latte|twig> <dir|https://…/<runtime>/wpfasty/v<version>/>
 */
import { createHash } from "node:crypto";
import { spawnSync } from "node:child_process";
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";

const [runtime, where] = process.argv.slice(2);
if ((runtime !== "latte" && runtime !== "twig") || !where) throw new Error("usage: registry-verify.ts <latte|twig> <dir|url>");
const remote = /^https?:\/\//.test(where);
const get = async (f: string): Promise<Buffer> => {
  if (!remote) return readFileSync(join(where, f));
  const url = where.replace(/\/?$/, "/") + f;
  const r = await fetch(url, { redirect: "follow" });
  if (r.status !== 200) throw new Error(`${url}: HTTP ${r.status}`);
  return Buffer.from(await r.arrayBuffer());
};
const errors: string[] = [];
const ok = (c: boolean, m: string) => { if (!c) errors.push(m); };
const cat = JSON.parse((await get("catalog.json")).toString("utf8"));
ok(cat.format === 2, "format must be 2");
ok(cat.runtime === runtime, `runtime ${cat.runtime} ≠ ${runtime}`);
ok(cat.templates === runtime, `templates ${cat.templates} ≠ ${runtime}`);
ok(/^\d+\.\d+\.\d+$/.test(cat.version ?? ""), `version ${cat.version} is not semver`);
ok(!!cat.source?.repo && !!cat.source?.commit, "source {repo, commit} missing");
const m = where.match(/\/v(\d+\.\d+\.\d+)\/?$/);
if (m) ok(m[1] === cat.version, `directory v${m[1]} ≠ catalog version ${cat.version}`);
ok(cat.archive?.file === "blocks.zip", "archive.file must be blocks.zip");
const zip = await get("blocks.zip");
const sha = createHash("sha256").update(zip).digest("hex");
ok(zip.length === cat.archive?.bytes, `blocks.zip ${zip.length} B ≠ archive.bytes ${cat.archive?.bytes}`);
ok(sha === cat.archive?.sha256, `blocks.zip sha256 ${sha} ≠ archive.sha256`);
const tmp = mkdtempSync(join(tmpdir(), "wpfb-verify-"));
writeFileSync(join(tmp, "b.zip"), zip);
const list = spawnSync("unzip", ["-Z1", join(tmp, "b.zip")], { encoding: "utf8" });
rmSync(tmp, { recursive: true, force: true });
ok(list.status === 0, "blocks.zip is not a zip");
const entries = list.stdout.split("\n").filter(Boolean);
const own = runtime === "twig" ? /\.twig$/ : /\.latte$/;
const foreign = runtime === "twig" ? /\.latte$/ : /\.twig$/;
const bad = entries.filter((e) => !/^(blocks|ui)\//.test(e) || foreign.test(e) || /\.php$/.test(e) || !(own.test(e) || /\.(json|md)$/.test(e) || e.endsWith("/")));
ok(!bad.length, `unexpected entries: ${bad.slice(0, 5).join(", ")}`);
const templates: string[] = (cat.blocks ?? []).flatMap((b: any) => (b.variants ?? []).map((v: any) => v.template));
ok(templates.length > 0, "no variants");
const ext = runtime === "twig" ? ".html.twig" : ".latte";
const wrong = templates.filter((t) => !t.endsWith(ext));
ok(!wrong.length, `templates not ${ext}: ${wrong.slice(0, 5).join(", ")}`);
const missing = templates.filter((t) => !entries.includes(t));
ok(!missing.length, `templates missing from blocks.zip: ${missing.slice(0, 5).join(", ")}`);
if (errors.length) {
  console.error(`✗ ${runtime} registry ${where}:\n  ${errors.join("\n  ")}`);
  process.exit(1);
}
console.log(`✓ ${runtime} registry ${where}: v${cat.version}, ${cat.blocks.length} blocks, ${templates.length} variants, blocks.zip ${zip.length} B sha256 ${sha.slice(0, 12)}…`);
