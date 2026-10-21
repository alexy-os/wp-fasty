// theme:package — manifest → installable release set in dist/<slug>/:
//   latte.zip (parent, reused from theme:pack), <slug>.zip (generated child), wpfasty-types.zip (typesPlugin=companion),
//   wpfasty-builder.zip (builder.enabled), demo.zip (output.includeDemo), blueprint.json + <slug>-playground.zip
//   (output.playground; a Playground blueprint bundle — every resource is `bundled`, no external URLs).
import { spawnSync } from "node:child_process";
import { copyFileSync, cpSync, existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { tmpdir } from "node:os";
import { basename, dirname, join, relative, resolve } from "node:path";
import { writeZipFromDir } from "../zip";
import { archetypeDir, generate } from "./index";
import type { J } from "./emit";

const root = resolve(dirname(new URL(import.meta.url).pathname), "../../../..");
const json = (p: string): J => JSON.parse(readFileSync(p, "utf8"));

/** Image seeds referenced by a demo seeder (same rules as tools/playground-build.sh). */
export function demoSeeds(file: string): string[] {
  const src = readFileSync(file, "utf8");
  const seeds = [...src.matchAll(/'([a-z0-9-]+)'\]/g), ...src.matchAll(/wpfasty_demo_image\('([a-z0-9-]+)'/g)].map((m) => m[1]);
  const prefix = basename(file) === "shop.php" ? "shop-" : "";
  return [...new Set(seeds.map((s) => (prefix && !s.startsWith(prefix) ? prefix + s : s)))].sort();
}

/** Parent release zip; engine twig uses the Twig build (`theme:pack -- <preset> --engine twig` → <preset>-twig.zip). */
function parentZip(preset: string, engine = "latte"): string {
  const zip = join(root, "dist", engine === "twig" ? `${preset}-twig.zip` : `${preset}.zip`);
  if (!existsSync(zip)) {
    const r = spawnSync("bun", ["run", "theme:pack", "--", preset, ...(engine === "twig" ? ["--engine", "twig"] : [])], { cwd: root, stdio: "inherit" });
    if (r.status !== 0 || !existsSync(zip)) throw new Error(`theme:pack ${preset} failed`);
  }
  return zip;
}

function demoZip(m: J, arch: J, stage: string, out: string): { images: number; missing: string[] } {
  const d = join(stage, "wpfasty-demo");
  mkdirSync(join(d, "images"), { recursive: true });
  mkdirSync(join(d, "landings"), { recursive: true });
  const seeder = join(root, arch.demo);
  copyFileSync(join(root, "tools/demo/common.php"), join(d, "common.php"));
  copyFileSync(seeder, join(d, basename(seeder)));
  for (const l of m.builder?.landings ?? []) {
    if (l.file) copyFileSync(join(root, l.file), join(d, "landings", basename(l.file)));
    else writeFileSync(join(d, "landings", `${l.page.slug}.json`), JSON.stringify(l, null, 2) + "\n");
  }
  // Images: taken from the committed preview bundle (playground/demo.zip) so packaging needs no network.
  // A seed that is not bundled is fetched by common.php at seeding time (wpfasty_demo_image falls back to network).
  const source = join(root, "playground/demo.zip");
  const missing: string[] = [];
  let images = 0;
  for (const seed of demoSeeds(seeder)) {
    const r = existsSync(source) ? spawnSync("unzip", ["-p", source, `wpfasty-demo/images/${seed}.jpg`]) : null;
    if (r && r.status === 0 && r.stdout.length) {
      writeFileSync(join(d, "images", `${seed}.jpg`), r.stdout);
      images++;
    } else missing.push(seed);
  }
  return { images, missing };
}

function blueprint(m: J, arch: J, files: { child: string; types: boolean; builder: boolean; demo: boolean }): J {
  const bundled = (p: string) => ({ resource: "bundled", path: `/${p}` });
  const steps: J[] = [
    { step: "login", username: "admin" },
    { step: "installTheme", themeData: bundled(`${m.parent?.preset ?? "latte"}.zip`), options: { activate: false } },
    { step: "installTheme", themeData: bundled(files.child), options: { activate: true } },
  ];
  if (files.types) steps.push({ step: "installPlugin", pluginData: bundled("wpfasty-types.zip"), options: { activate: true } });
  if (files.builder) steps.push({ step: "installPlugin", pluginData: bundled("wpfasty-builder.zip"), options: { activate: true } });
  steps.push({ step: "setSiteOptions", options: { permalink_structure: "/%postname%/" } });
  if (files.demo) {
    steps.push({ step: "unzip", zipFile: bundled("demo.zip"), extractToPath: "/wordpress/wp-content" });
    steps.push({
      step: "runPHP",
      code: `<?php require_once '/wordpress/wp-load.php'; require '/wordpress/wp-content/wpfasty-demo/${basename(arch.demo)}'; flush_rewrite_rules();`,
    });
    const landings = (m.builder?.landings ?? []).map((l: J) => (l.file ? basename(l.file) : `${l.page.slug}.json`));
    if (files.builder && landings.length)
      steps.push({
        step: "runPHP",
        code: `<?php require_once '/wordpress/wp-load.php'; foreach (${JSON.stringify(landings).replace(/"/g, "'")} as $f) { $r = WPFastyBuilder\\Seeder::landing(json_decode(file_get_contents('/wordpress/wp-content/wpfasty-demo/landings/' . $f), true)); if (is_wp_error($r)) { throw new Exception($f . ': ' . $r->get_error_message()); } }`,
      });
  }
  return {
    $schema: "https://playground.wordpress.net/blueprint-schema.json",
    meta: { title: m.theme.name, description: `${m.theme.name} — generated by theme:package from the site manifest`, author: m.theme.author?.name ?? "alexy-os" },
    landingPage: "/",
    preferredVersions: { php: "8.3", wp: "latest" },
    steps,
  };
}

export async function packageSite(manifestPath: string, outArg?: string): Promise<{ dir: string; files: string[] }> {
  const m = json(resolve(manifestPath));
  const slug = m.theme.slug;
  const out = resolve(outArg ?? join(root, "dist", slug));
  const arch = json(join(archetypeDir(m.archetype), "archetype.json"));
  const o = m.output ?? {};
  const stage = mkdtempSync(join(tmpdir(), "wpfasty-pkg-"));
  rmSync(out, { recursive: true, force: true });
  mkdirSync(out, { recursive: true });
  const files: string[] = [];
  const add = (name: string) => files.push(name);

  const preset = m.parent?.preset ?? "latte";
  copyFileSync(parentZip(preset, m.engine), join(out, `${preset}.zip`));
  add(`${preset}.zip`);

  generate(manifestPath, join(stage, slug), { pot: true });
  await writeZipFromDir(join(stage, slug), join(out, `${slug}.zip`), slug);
  add(`${slug}.zip`);

  const types = (o.typesPlugin ?? "companion") === "companion" && (m.postTypes ?? []).length > 0;
  if (types) {
    await writeZipFromDir(join(root, "plugins/wpfasty-types"), join(out, "wpfasty-types.zip"), "wpfasty-types");
    add("wpfasty-types.zip");
  }
  const builder = m.builder?.enabled === true;
  if (builder) {
    await writeZipFromDir(join(root, "plugins/wpfasty-builder"), join(out, "wpfasty-builder.zip"), "wpfasty-builder", ["wpfasty-builder/editor/*"]);
    add("wpfasty-builder.zip");
  }
  const demo = o.includeDemo === true;
  if (demo) {
    const r = demoZip(m, arch, stage, out);
    await writeZipFromDir(join(stage, "wpfasty-demo"), join(out, "demo.zip"), "wpfasty-demo");
    add("demo.zip");
    console.log(`demo.zip: ${r.images} images bundled${r.missing.length ? `; fetched at seeding time: ${r.missing.join(", ")}` : ""}`);
  }
  if (o.playground === true) {
    writeFileSync(join(out, "blueprint.json"), JSON.stringify(blueprint(m, arch, { child: `${slug}.zip`, types, builder, demo }), null, 2) + "\n");
    add("blueprint.json");
    // Bundle = blueprint.json + every zip inside one top-level folder (Playground accepts one directory deep).
    const bundle = `${slug}-playground`;
    mkdirSync(join(stage, bundle));
    for (const f of files) copyFileSync(join(out, f), join(stage, bundle, f));
    await writeZipFromDir(join(stage, bundle), join(out, `${bundle}.zip`), bundle);
    add(`${bundle}.zip`);
  }
  cpSync(resolve(manifestPath), join(out, "site.manifest.json"));
  add("site.manifest.json");
  rmSync(stage, { recursive: true, force: true });
  return { dir: out, files };
}

export async function packageCli(args: string[]): Promise<void> {
  const outAt = args.indexOf("--out");
  const out = outAt >= 0 ? args[outAt + 1] : undefined;
  const manifest = args.find((a, i) => !a.startsWith("--") && (outAt < 0 || i !== outAt + 1));
  if (!manifest) throw new Error("Usage: theme:package <manifest.json> [--out dist/<slug>/]");
  const { dir, files } = await packageSite(manifest, out);
  console.log(`packaged ${relative(process.cwd(), dir) || dir}`);
  for (const f of files) console.log(`  ${f}  ${statSync(join(dir, f)).size} B`);
}
