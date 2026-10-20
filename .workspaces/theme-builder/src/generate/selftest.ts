// bun run generate:test — a site whose slug differs from its archetype's reference theme is fully retargeted:
// no archetype PHP namespace or text domain survives in the emitted theme, and every PHP file still lints.
import { spawnSync } from "node:child_process";
import { mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { join, relative } from "node:path";
import { buildManifest } from "../configure/core";
import { loadData } from "../configure/node";
import { archetypeDir, generate } from "./index";

let fail = 0;
const check = (name: string, ok: boolean, detail = "") => { console.log(`${ok ? "✓" : "✗"} ${name}${detail ? ` — ${detail}` : ""}`); if (!ok) fail++; };
const walk = (d: string): string[] => readdirSync(d).flatMap((n) => statSync(join(d, n)).isDirectory() ? walk(join(d, n)) : [join(d, n)]);
const hasPhp = spawnSync("php", ["-v"]).status === 0;

const data = loadData();
const tmp = mkdtempSync(join(tmpdir(), "wpfasty-gen-"));
try {
  for (const arch of Object.keys(data.examples).sort()) {
    const a = JSON.parse(readFileSync(join(archetypeDir(arch), "archetype.json"), "utf8"));
    const { manifest } = buildManifest({ archetype: arch, name: `Corner ${arch} Studio` }, { ...data, schemaId: data.examples[arch].$schema });
    const mf = join(tmp, `${arch}.json`);
    writeFileSync(mf, JSON.stringify(manifest, null, 2));
    const dir = generate(mf, join(tmp, manifest.theme.slug), { pot: false });
    const php = walk(dir).filter((f) => f.endsWith(".php"));
    const ns = manifest.theme.namespace;
    const leftNs = php.filter((f) => new RegExp(`(?<![A-Za-z0-9_])${a.sourceNamespace}(?![A-Za-z0-9_])`).test(readFileSync(f, "utf8")));
    const leftTd = walk(dir).filter((f) => readFileSync(f, "utf8").includes(`'${a.sourceTextDomain}'`));
    check(`${arch} → ${manifest.theme.slug}: no "${a.sourceNamespace}" left`, !leftNs.length, leftNs.map((f) => relative(dir, f)).join(", "));
    check(`${arch} → ${manifest.theme.slug}: no '${a.sourceTextDomain}' text domain left`, !leftTd.length, leftTd.map((f) => relative(dir, f)).join(", "));
    const declares = php.filter((f) => /^namespace\s/m.test(readFileSync(f, "utf8")));
    check(`${arch}: every namespace declaration is ${ns}`, declares.every((f) => readFileSync(f, "utf8").includes(`namespace ${ns};`)), `${declares.length} file(s)`);
    if (hasPhp) {
      const bad = php.filter((f) => spawnSync("php", ["-l", f]).status !== 0);
      check(`${arch}: php -l on ${php.length} file(s)`, !bad.length, bad.map((f) => relative(dir, f)).join(", "));
    }
  }
} finally {
  rmSync(tmp, { recursive: true, force: true });
}
console.log(fail ? `${fail} failed` : "generate self-test OK");
process.exit(fail ? 1 : 0);
