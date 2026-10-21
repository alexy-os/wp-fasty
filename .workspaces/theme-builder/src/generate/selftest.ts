// bun run generate:test — a site whose slug differs from its archetype's reference theme is fully retargeted:
// no archetype PHP namespace or text domain survives in the emitted theme, and every PHP file still lints.
import { spawnSync } from "node:child_process";
import { cpSync, existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { dirname, join, relative } from "node:path";
import { buildManifest } from "../configure/core";
import { loadData } from "../configure/node";
import { archetypeDir, generate } from "./index";

let fail = 0;
const check = (name: string, ok: boolean, detail = "") => { console.log(`${ok ? "✓" : "✗"} ${name}${detail ? ` — ${detail}` : ""}`); if (!ok) fail++; };
const walk = (d: string): string[] => readdirSync(d).flatMap((n) => statSync(join(d, n)).isDirectory() ? walk(join(d, n)) : [join(d, n)]);
const hasPhp = spawnSync("php", ["-v"]).status === 0;

const data = loadData();
const tmp = mkdtempSync(join(tmpdir(), "wpfasty-gen-"));
// Archetype copies with a fixture view carrying reference-theme literals (text domain + namespace) in every views/ dir:
// guards against the views copy running after the retarget pass.
const archRoot = join(tmp, "archetypes");
const FIXTURE = "views/blocks/__retarget-fixture.latte";
try {
  for (const arch of Object.keys(data.examples).sort()) {
    const a = JSON.parse(readFileSync(join(archetypeDir(arch), "archetype.json"), "utf8"));
    cpSync(archetypeDir(arch), join(archRoot, arch), { recursive: true });
    if (a.views) {
      const fx = join(archRoot, arch, a.views, "blocks/__retarget-fixture.latte");
      mkdirSync(dirname(fx), { recursive: true });
      writeFileSync(fx, `{* fixture *}\n<p>{=__('Fixture', '${a.sourceTextDomain}')} {=__("Fixture", "${a.sourceTextDomain}")} {=${a.sourceNamespace}\\Fixture::label()}</p>\n`);
    }
    const { manifest } = buildManifest({ archetype: arch, name: `Corner ${arch} Studio` }, { ...data, schemaId: data.examples[arch].$schema });
    const mf = join(tmp, `${arch}.json`);
    writeFileSync(mf, JSON.stringify(manifest, null, 2));
    const dir = generate(mf, join(tmp, manifest.theme.slug), { pot: false, archetypes: archRoot });
    const php = walk(dir).filter((f) => f.endsWith(".php"));
    const ns = manifest.theme.namespace;
    const leftNs = php.filter((f) => new RegExp(`(?<![A-Za-z0-9_])${a.sourceNamespace}(?![A-Za-z0-9_])`).test(readFileSync(f, "utf8")));
    const leftTd = walk(dir).filter((f) => { const t = readFileSync(f, "utf8"); return t.includes(`'${a.sourceTextDomain}'`) || t.includes(`"${a.sourceTextDomain}"`); });
    check(`${arch} → ${manifest.theme.slug}: no "${a.sourceNamespace}" left`, !leftNs.length, leftNs.map((f) => relative(dir, f)).join(", "));
    check(`${arch} → ${manifest.theme.slug}: no '${a.sourceTextDomain}' text domain left`, !leftTd.length, leftTd.map((f) => relative(dir, f)).join(", "));
    const latte = walk(dir).filter((f) => f.endsWith(".latte"));
    const latteTd = latte.filter((f) => { const t = readFileSync(f, "utf8"); return t.includes(`'${a.sourceTextDomain}'`) || t.includes(`"${a.sourceTextDomain}"`); });
    check(`${arch}: no old text domain in ${latte.length} .latte file(s)`, !latteTd.length, latteTd.map((f) => relative(dir, f)).join(", "));
    if (a.views) {
      const fx = join(dir, FIXTURE);
      const t = existsSync(fx) ? readFileSync(fx, "utf8") : "";
      check(`${arch}: fixture view retargeted`, t.includes(`'${manifest.theme.textDomain}'`) && t.includes(`"${manifest.theme.textDomain}"`) && t.includes(`${ns}\\Fixture`), t ? "" : "fixture missing");
    }
    const declares = php.filter((f) => /^namespace\s/m.test(readFileSync(f, "utf8")));
    check(`${arch}: every namespace declaration is ${ns}`, declares.every((f) => readFileSync(f, "utf8").includes(`namespace ${ns};`)), `${declares.length} file(s)`);
    if (hasPhp) {
      const bad = php.filter((f) => spawnSync("php", ["-l", f]).status !== 0);
      check(`${arch}: php -l on ${php.length} file(s)`, !bad.length, bad.map((f) => relative(dir, f)).join(", "));
    }
  }
  // engine twig = engine latte + the default-renderer switch: same files (both view sets), only functions.php differs.
  for (const arch of Object.keys(data.examples).sort()) {
    const src = join(archetypeDir(arch), "views");
    const latteViews = existsSync(src) ? walk(src).filter((f) => f.endsWith(".latte")).map((f) => relative(src, f)) : [];
    const { manifest } = buildManifest({ archetype: arch, name: `Twig ${arch} Site`, engine: "twig" }, { ...data, schemaId: data.examples[arch].$schema });
    const mf = join(tmp, `${arch}-twig.json`);
    writeFileSync(mf, JSON.stringify(manifest, null, 2));
    const dir = generate(mf, join(tmp, manifest.theme.slug), { pot: false });
    const views = existsSync(join(dir, "views")) ? walk(join(dir, "views")).map((f) => relative(join(dir, "views"), f)) : [];
    const missing = latteViews.filter((v) => !views.includes(v) || !views.includes(v.replace(/\.latte$/, ".html.twig")));
    check(`${arch} twig: Latte view + Twig twin for each of ${latteViews.length} archetype view(s)`, !missing.length, missing.join(", "));
    const fn = readFileSync(join(dir, "functions.php"), "utf8");
    check(`${arch} twig: functions.php switches wpfasty_renderer`, fn.includes("add_filter('wpfasty_renderer', static fn (): string => 'twig', 5);"));
    {
      const lm = join(tmp, `${arch}-latte.json`);
      const latteM = buildManifest({ archetype: arch, name: `Twig ${arch} Site` }, { ...data, schemaId: data.examples[arch].$schema }).manifest;
      writeFileSync(lm, JSON.stringify(latteM, null, 2));
      const ldir = generate(lm, join(tmp, `latte-${manifest.theme.slug}`), { pot: false });
      const list = (d: string) => walk(d).map((f) => relative(d, f)).sort();
      const diff = [...new Set([...list(dir), ...list(ldir)])].filter((r) => r !== "functions.php"
        && (!existsSync(join(dir, r)) || !existsSync(join(ldir, r)) || readFileSync(join(dir, r), "utf8") !== readFileSync(join(ldir, r), "utf8")));
      check(`${arch} twig: same files as the latte child except functions.php`, !diff.length, diff.join(", "));
    }
    if (hasPhp) check(`${arch} twig: php -l functions.php`, spawnSync("php", ["-l", join(dir, "functions.php")]).status === 0);
  }
} finally {
  rmSync(tmp, { recursive: true, force: true });
}
console.log(fail ? `${fail} failed` : "generate self-test OK");
process.exit(fail ? 1 : 0);
