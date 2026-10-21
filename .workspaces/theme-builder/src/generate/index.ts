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

/** Appended to functions.php of engine-twig children; the parent's Twig build bundles twig/twig + Twig primitives. */
const TWIG_SWITCH = `
// Views ship as Twig (site manifest engine: twig): render them with the parent's TwigRenderer.
add_filter('wpfasty_renderer', static fn (): string => 'twig');
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
  if (arch.inc && existsSync(join(adir, arch.inc))) cpSync(join(adir, arch.inc), join(dir, "inc"), { recursive: true });
  if (arch.views && existsSync(join(adir, arch.views))) cpSync(join(adir, arch.views), join(dir, "views"), { recursive: true });
  // engine twig: the child ships only its Twig views and switches the parent renderer (ADR-001: `wpfasty_renderer`).
  if (m.engine === "twig") {
    if (existsSync(join(dir, "views")))
      for (const rel of files(join(dir, "views")).filter((r) => r.endsWith(".latte"))) rmSync(join(dir, "views", rel));
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
