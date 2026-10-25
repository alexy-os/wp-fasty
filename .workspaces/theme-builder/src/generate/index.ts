// theme:generate / theme:roundtrip — site manifest v0.2 + archetype package → child theme.
//   bun run theme:generate manifests/examples/press.json [--out dir] [--no-pot]
//   bun run theme:roundtrip            # every example whose archetype has a package must regenerate byte-identical
import { spawnSync } from "node:child_process";
import { cpSync, existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { dirname, join, relative, resolve } from "node:path";
import { render } from "./template";
import { extraEntries, type J, styleCss, templateVars, tokensCss, typePhp } from "./emit";
import { codexDelivery, codexManifest, deliveryJson } from "./codex";
import Ajv2020 from "ajv/dist/2020";
import { validateSnapshot } from "@ui8kit/contract";

const root = resolve(dirname(new URL(import.meta.url).pathname), "../../../..");
const json = (p: string): J => JSON.parse(readFileSync(p, "utf8"));

function write(dir: string, rel: string, body: string): void {
  mkdirSync(dirname(join(dir, rel)), { recursive: true });
  writeFileSync(join(dir, rel), body);
}

/** Codex Manifest of the site, validated against the contract schema (@ui8kit/contract, manifest.schema.json). */
function codexFiles(m: J): { manifest: string; delivery: string } {
  const cm = codexManifest(m);
  const schema = json(join(root, "node_modules/@ui8kit/contract/schemas/manifest.schema.json"));
  const check = new Ajv2020({ allErrors: true, strict: false }).compile(schema);
  if (!check(cm)) throw new Error(`codex manifest invalid:\n${(check.errors ?? []).map((e) => `  ${e.instancePath} ${e.message}`).join("\n")}`);
  const issues = validateSnapshot(cm, []);
  if (issues.length) throw new Error(`codex manifest rejected by @ui8kit/contract:\n${issues.map((i) => `  ${i.code} ${i.path}`).join("\n")}`);
  return { manifest: `${JSON.stringify(cm, null, 2)}\n`, delivery: deliveryJson(codexDelivery(m, cm)) };
}

function hasWp(): boolean {
  return spawnSync("wp", ["--version"], { encoding: "utf8" }).status === 0;
}

/**
 * Appended to functions.php of engine-twig children: the site renders with Twig, chosen at site creation (ADR-001,
 * K7: isolated runtimes). On a parent build without Twig the theme stops with an error — it never falls back to Latte.
 */
const TWIG_SWITCH = `
// Site manifest engine: twig — this site renders with Twig only (no Latte fallback).
add_filter('wpfasty_renderer', static fn (): string => 'twig', 5);
`;

export function archetypeDir(id: string): string {
  return join(root, "presets/archetypes", id);
}

export function generate(manifestPath: string, out: string | undefined, opts: { pot: boolean; archetypes?: string }): string {
  const file = resolve(manifestPath);
  const check = spawnSync("bun", [join(root, "tools/manifest/validate.ts"), file], { encoding: "utf8" });
  if (check.status !== 0) throw new Error(`manifest invalid:\n${check.stdout}${check.stderr}`);
  const m = json(file);
  const adir = opts.archetypes ? join(opts.archetypes, m.archetype) : archetypeDir(m.archetype);
  if (!existsSync(join(adir, "archetype.json")))
    throw new Error(`archetype "${m.archetype}" has no package (presets/archetypes/${m.archetype}/archetype.json)`);
  const arch = json(join(adir, "archetype.json"));
  for (const need of arch.requiresTypes ?? [])
    if (!(m.postTypes ?? []).some((p: J) => p.name === need)) throw new Error(`archetype ${arch.id} needs post type "${need}"`);

  const dir = resolve(out ?? join(root, "dist/generated", m.theme.slug));
  rmSync(dir, { recursive: true, force: true });
  mkdirSync(dir, { recursive: true });
  const tpl = (k: string) => readFileSync(join(adir, arch.templates[k]), "utf8");
  const vars = templateVars(m, arch);

  write(dir, "style.css", styleCss(m, tpl("style.body")));
  write(dir, "functions.php", render(tpl("functions.php"), vars));
  write(dir, "readme.txt", render(tpl("readme.txt"), vars));
  write(dir, "assets/css/tokens.css", tokensCss(m, arch.palette));
  for (const pt of m.postTypes ?? []) {
    const extraFile = join(adir, "types", `${pt.name}.extra.php`);
    const extra = existsSync(extraFile) ? render(extraEntries(readFileSync(extraFile, "utf8")), vars) : "";
    write(dir, `types/${pt.name}.php`, typePhp(pt, m, extra));
  }
  const codex = codexFiles(m);
  write(dir, "types/codex.manifest.json", codex.manifest);
  write(dir, "types/codex.delivery.json", codex.delivery);
  if (arch.inc && existsSync(join(adir, arch.inc))) cpSync(join(adir, arch.inc), join(dir, "inc"), { recursive: true });
  if (arch.views && existsSync(join(adir, arch.views))) cpSync(join(adir, arch.views), join(dir, "views"), { recursive: true });
  // engine twig: the site's renderer is Twig (ADR-001: `wpfasty_renderer`); there is no Latte fallback.
  if (m.engine === "twig") {
    writeFileSync(join(dir, "functions.php"), readFileSync(join(dir, "functions.php"), "utf8") + TWIG_SWITCH);
  }
  // Archetype code is copied verbatim but written for its reference theme: retarget its text domain literals.
  if (arch.sourceTextDomain && arch.sourceTextDomain !== m.theme.textDomain)
    for (const sub of ["inc", "views"])
      if (existsSync(join(dir, sub)))
        for (const rel of files(join(dir, sub))) {
          const p = join(dir, sub, rel);
          const src = readFileSync(p, "utf8");
          const out = src.split(`'${arch.sourceTextDomain}'`).join(`'${m.theme.textDomain}'`)
            .split(`"${arch.sourceTextDomain}"`).join(`"${m.theme.textDomain}"`);
          if (out !== src) writeFileSync(p, out);
        }

  // Same for the PHP namespace (namespace LatteShop; LatteShop\\Money::…) across every emitted PHP and Latte file.
  const ns = m.theme.namespace ?? pascalCase(m.theme.slug);
  if (arch.sourceNamespace && arch.sourceNamespace !== ns) {
    const word = new RegExp(`(?<![A-Za-z0-9_])${arch.sourceNamespace}(?![A-Za-z0-9_])`, "g");
    for (const rel of files(dir).filter((r) => r.endsWith(".php") || r.endsWith(".latte"))) {
      const p = join(dir, rel);
      const src = readFileSync(p, "utf8");
      const out = src.replace(word, ns);
      if (out !== src) writeFileSync(p, out);
    }
  }

  if (opts.pot) {
    if (!hasWp()) throw new Error("wp-cli not found (needed for languages/*.pot); pass --no-pot to skip");
    const slug = m.theme.textDomain;
    const r = spawnSync("wp", ["i18n", "make-pot", dir, join(dir, "languages", `${slug}.pot`), `--domain=${slug}`,
      "--exclude=vendor,~cache,node_modules", "--quiet",
      // make-pot derives this from the folder name; pin it so any --out dir yields the same file.
      `--headers=${JSON.stringify({ "Report-Msgid-Bugs-To": `https://wordpress.org/support/theme/${m.theme.slug}` })}`], { encoding: "utf8" });
    if (r.status !== 0) throw new Error(`wp i18n make-pot failed: ${r.stderr}`);
  }
  return dir;
}

export const pascalCase = (slug: string): string =>
  slug.split("-").filter(Boolean).map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join("");

function files(dir: string, base = dir): string[] {
  return readdirSync(dir).flatMap((n) => {
    if (n === "~cache") return [];
    const p = join(dir, n);
    return statSync(p).isDirectory() ? files(p, base) : [relative(base, p)];
  }).sort();
}

/** Run-dependent .pot header lines; everything else must match byte for byte. */
const normalize = (rel: string, s: string) =>
  rel.endsWith(".pot") ? s.replace(/^"POT-Creation-Date: .*"$/m, '"POT-Creation-Date: *"').replace(/^"X-Generator: .*"$/m, '"X-Generator: *"') : s;

/**
 * Codex manifest of a site without one (core post / page, category / post_tag): wpfasty-types serves it when the
 * active theme ships no types/codex.manifest.json. Same generator as the themes; the roundtrip checks it is current.
 */
export const CORE_CODEX_DIR = "plugins/wpfasty-types/codex";
export function coreCodex(): { manifest: string; delivery: string } {
  return codexFiles({ theme: { slug: "wpfasty-core", version: "1.0.0", prefix: "wpfasty" }, postTypes: [] });
}

export function roundtrip(): number {
  const ex = join(root, "manifests/examples");
  let failed = 0;
  const core = coreCodex();
  for (const [name, text] of [["codex.manifest.json", core.manifest], ["codex.delivery.json", core.delivery]] as const) {
    const f = join(root, CORE_CODEX_DIR, name);
    if (process.argv.includes("--write-core")) write(join(root, CORE_CODEX_DIR), name, text);
    else if (!existsSync(f) || readFileSync(f, "utf8") !== text) {
      failed++;
      console.log(`✗ ${CORE_CODEX_DIR}/${name} is stale — bun run theme:roundtrip -- --write-core`);
    }
  }
  let checked = 0;
  for (const f of readdirSync(ex).filter((n) => n.endsWith(".json")).sort()) {
    const m = json(join(ex, f));
    if (!existsSync(join(archetypeDir(m.archetype), "archetype.json"))) {
      console.log(`- ${f}: archetype "${m.archetype}" not packaged yet (skipped)`);
      continue;
    }
    checked++;
    const tmp = mkdtempSync(join(tmpdir(), "wpfasty-rt-"));
    const gen = generate(join(ex, f), join(tmp, m.theme.slug), { pot: true });
    const real = join(root, "themes", m.theme.slug);
    const a = files(real);
    const b = files(gen);
    const problems: string[] = [];
    for (const p of a) if (!b.includes(p)) problems.push(`missing in generated: ${p}`);
    for (const p of b) if (!a.includes(p)) problems.push(`extra in generated: ${p}`);
    for (const p of a.filter((x) => b.includes(x))) {
      const x = normalize(p, readFileSync(join(real, p), "utf8"));
      const y = normalize(p, readFileSync(join(gen, p), "utf8"));
      if (x !== y) {
        problems.push(`differs: ${p}`);
        const d = spawnSync("git", ["diff", "--no-index", "--no-color", join(real, p), join(gen, p)], { encoding: "utf8" });
        problems.push(d.stdout.split("\n").slice(0, 60).join("\n"));
      }
    }
    if (problems.length) {
      failed++;
      console.log(`✗ ${f} → themes/${m.theme.slug}\n  ${problems.join("\n  ")}`);
    } else console.log(`✓ ${f} → themes/${m.theme.slug}: ${a.length} files identical (pot header dates normalized)`);
    rmSync(tmp, { recursive: true, force: true });
  }
  console.log(`${checked} theme(s) round-tripped, ${failed} failed`);
  return failed ? 1 : 0;
}

export function generateCli(args: string[]): void {
  const outAt = args.indexOf("--out");
  const out = outAt >= 0 ? args[outAt + 1] : undefined;
  const manifest = args.find((a, i) => !a.startsWith("--") && (outAt < 0 || i !== outAt + 1));
  if (!manifest) throw new Error("Usage: theme:generate <manifest.json> [--out dir] [--no-pot]");
  const dir = generate(manifest, out, { pot: !args.includes("--no-pot") });
  console.log(`generated ${relative(process.cwd(), dir) || dir}`);
}
