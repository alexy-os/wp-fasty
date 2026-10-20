// theme:generate / theme:roundtrip — site manifest v0.2 + archetype package → child theme.
//   bun run theme:generate manifests/examples/press.json [--out dir] [--no-pot]
//   bun run theme:roundtrip            # every example whose archetype has a package must regenerate byte-identical
import { spawnSync } from "node:child_process";
import { cpSync, existsSync, mkdirSync, mkdtempSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { dirname, join, relative, resolve } from "node:path";
import { render } from "./template";
import { extraEntries, type J, styleCss, templateVars, tokensCss, typePhp } from "./emit";

const root = resolve(dirname(new URL(import.meta.url).pathname), "../../../..");
const json = (p: string): J => JSON.parse(readFileSync(p, "utf8"));

function write(dir: string, rel: string, body: string): void {
  mkdirSync(dirname(join(dir, rel)), { recursive: true });
  writeFileSync(join(dir, rel), body);
}

function hasWp(): boolean {
  return spawnSync("wp", ["--version"], { encoding: "utf8" }).status === 0;
}

export function archetypeDir(id: string): string {
  return join(root, "presets/archetypes", id);
}

export function generate(manifestPath: string, out: string | undefined, opts: { pot: boolean }): string {
  const file = resolve(manifestPath);
  const check = spawnSync("bun", [join(root, "tools/manifest/validate.ts"), file], { encoding: "utf8" });
  if (check.status !== 0) throw new Error(`manifest invalid:\n${check.stdout}${check.stderr}`);
  const m = json(file);
  const adir = archetypeDir(m.archetype);
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
  if (arch.inc && existsSync(join(adir, arch.inc))) cpSync(join(adir, arch.inc), join(dir, "inc"), { recursive: true });
  if (arch.views && existsSync(join(adir, arch.views))) cpSync(join(adir, arch.views), join(dir, "views"), { recursive: true });

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

export function roundtrip(): number {
  const ex = join(root, "manifests/examples");
  let failed = 0;
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
