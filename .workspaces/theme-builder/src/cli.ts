/**
 * theme-builder CLI — assemble WordPress themes from presets.
 *
 *   bun src/cli.ts build <theme-id>
 *   bun src/cli.ts pack <theme-id>
 *   bun src/cli.ts new
 */

import { generateCli, roundtrip } from "./generate/index";
import { packageCli } from "./generate/package";
import { newSiteCli } from "./configure/cli";
import { writeZipFromDir } from "./zip";
import { existsSync } from "node:fs";
import {
  copyFile,
  cp,
  mkdir,
  readFile,
  readdir,
  rm,
  stat,
  writeFile,
} from "node:fs/promises";
import { dirname, join, relative, resolve } from "node:path";
import { createHash } from "node:crypto";

const sha256 = (b: Uint8Array | string): string => createHash("sha256").update(b).digest("hex");
import { fileURLToPath } from "node:url";
import { spawnSync } from "node:child_process";

const builderRoot = join(dirname(fileURLToPath(import.meta.url)), "..");
const repoRoot = join(builderRoot, "..", "..");
const presetsRoot = join(repoRoot, "presets");
const themesRoot = join(repoRoot, "themes");
const primitivesRoot = join(repoRoot, ".workspaces", "ui8kit-latte");
const distRoot = join(repoRoot, "dist");

interface ThemePreset {
  id: string;
  name: string;
  slug: string;
  textDomain: string;
  layout: string;
  style: string;
  templates: Record<string, string[]>;
  features?: string[];
  php?: { namespace?: string; container?: boolean };
  parent?: string;
  kind?: "parent" | "skin";
}

async function loadPreset(id: string): Promise<ThemePreset> {
  const path = join(presetsRoot, "themes", `${id}.yaml`);
  const alt = join(presetsRoot, "themes", `${id}.json`);
  const file = existsSync(path) ? path : alt;
  if (!existsSync(file)) {
    throw new Error(`Preset not found: presets/themes/${id}.{yaml,json}`);
  }
  const raw = await readFile(file, "utf8");
  if (file.endsWith(".json")) return JSON.parse(raw) as ThemePreset;
  throw new Error(`YAML presets require JSON — use presets/themes/${id}.json`);
}

function blockInclude(blockPath: string): string {
  // presets use "blocks/hero-centered" → theme views use blocks/hero-centered.latte
  const name = blockPath.replace(/^blocks\//, "");
  return `{include 'blocks/${name}.latte'}`;
}

function pageViewSource(blocks: string[], comment: string): string {
  const lines = [`{** ${comment} *}`, ...blocks.map(blockInclude)];
  return lines.join("\n") + "\n";
}

/** Twig twin of a generated page view (same blocks, `.html.twig` names; Twig include passes the context like Latte). */
function pageViewSourceTwig(blocks: string[], comment: string): string {
  const lines = [`{# ${comment} #}`, ...blocks.map((b) => `{% include 'blocks/${b.replace(/^blocks\//, "")}.html.twig' %}`)];
  return lines.join("\n") + "\n";
}

async function syncBlocks(themeDir: string): Promise<string[]> {
  const blocksDir = join(presetsRoot, "blocks");
  const outDir = join(themeDir, "views", "blocks");
  await mkdir(outDir, { recursive: true });
  const copied: string[] = [];
  if (!existsSync(blocksDir)) return copied;

  for (const name of await readdir(blocksDir)) {
    if (!name.endsWith(".latte") && !name.endsWith(".html.twig")) continue;
    await copyFile(join(blocksDir, name), join(outDir, name));
    copied.push(name);
  }
  return copied;
}

async function syncStyle(preset: ThemePreset, themeDir: string): Promise<void> {
  const styleRel = preset.style.endsWith(".css")
    ? preset.style
    : `${preset.style}.css`;
  const styleSrc = join(presetsRoot, styleRel);
  if (!existsSync(styleSrc)) {
    console.warn(`Warning: style preset missing: ${styleRel}`);
    return;
  }
  const out = join(themeDir, "assets", "css", "tokens.css");
  await mkdir(dirname(out), { recursive: true });
  await copyFile(styleSrc, out);
}

async function emitPageViews(preset: ThemePreset, themeDir: string): Promise<void> {
  const pagesDir = join(themeDir, "views", "pages");
  await mkdir(pagesDir, { recursive: true });

  for (const [template, blocks] of Object.entries(preset.templates ?? {})) {
    const file = join(pagesDir, `${template}.latte`);
    // Empty block list = hand-authored view; never overwrite it with an empty file.
    if (!blocks || blocks.length === 0) continue;
    const body = pageViewSource(blocks, `${template} — generated from presets/themes/${preset.id}.json`);
    await writeFile(file, body);
    await writeFile(join(pagesDir, `${template}.html.twig`), pageViewSourceTwig(blocks, `${template} — generated from presets/themes/${preset.id}.json`));
  }

  // Keep hand-authored utility pages if present (404/search are not in templates map).
  for (const utility of ["404", "search"]) {
    const utilityPath = join(pagesDir, `${utility}.latte`);
    if (!existsSync(utilityPath)) {
      console.warn(`Note: views/pages/${utility}.latte missing (not generated from templates map)`);
    }
  }
}

async function scaffoldSkin(preset: ThemePreset): Promise<void> {
  const parent = preset.parent ?? "latte";
  const out = join(themesRoot, preset.slug);
  await mkdir(join(out, "assets", "css"), { recursive: true });
  await mkdir(join(out, "views", "blocks"), { recursive: true });

  const stylePath = join(out, "style.css");
  if (!existsSync(stylePath)) {
    const style = `/*
Theme Name: ${preset.name}
Theme URI: https://github.com/alexy-os/wp-fasty
Author: alexy-os
Author URI: https://github.com/alexy-os
Description: Child skin for ${parent} — no Composer.
Version: 1.0.0
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.2
Template: ${parent}
License: GNU General Public License v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ${preset.textDomain}
Tags: blog, custom-menu, featured-images
*/
`;
    await writeFile(stylePath, style);
  }

  if (!existsSync(join(out, "functions.php"))) {
    await writeFile(
      join(out, "functions.php"),
      `<?php\n\ndeclare(strict_types=1);\n\nif (!defined('ABSPATH')) {\n    exit;\n}\n`
    );
  }

  // Do not overwrite existing skin tokens — only seed if missing.
  const tokensPath = join(out, "assets", "css", "tokens.css");
  if (!existsSync(tokensPath)) {
    await syncStyle(preset, out);
  }
  console.log(`Scaffolded skin → themes/${preset.slug}/`);
}

async function buildTheme(id: string): Promise<void> {
  const preset = await loadPreset(id);
  const out = join(themesRoot, preset.slug);

  if (!existsSync(join(primitivesRoot, "manifest.json"))) {
    console.warn(
      "Warning: .workspaces/ui8kit-latte missing — run `bun run ui:primitives` first."
    );
  }

  if (preset.kind === "skin" || preset.parent) {
    await scaffoldSkin(preset);
  } else if (!existsSync(out)) {
    await mkdir(out, { recursive: true });
    console.log(`Created ${out} (empty shell — copy skeleton in a later step)`);
  }

  if (preset.kind !== "skin" && !preset.parent) {
    const blocks = await syncBlocks(out);
    await emitPageViews(preset, out);
    await syncStyle(preset, out);
    console.log(`Synced blocks: ${blocks.join(", ") || "(none)"}`);
    console.log(`Emitted page views from templates map`);
  }

  const stamp = {
    preset: preset.id,
    name: preset.name,
    builtAt: new Date().toISOString(),
    primitives: existsSync(join(primitivesRoot, "manifest.json"))
      ? JSON.parse(await readFile(join(primitivesRoot, "manifest.json"), "utf8"))
      : null,
    layout: preset.layout,
    style: preset.style,
    templates: preset.templates,
    kind: preset.kind ?? (preset.parent ? "skin" : "parent"),
  };

  await writeFile(join(out, ".theme-build.json"), JSON.stringify(stamp, null, 2) + "\n");
  console.log(`Built theme stamp → themes/${preset.slug}/.theme-build.json`);
  console.log(`  layout: ${preset.layout}`);
  console.log(`  style:  ${preset.style}`);
  console.log(`  blocks: ${Object.values(preset.templates ?? {}).flat().join(", ") || "(none)"}`);
}

/** Files of the other engine that a single-engine parent build must not ship (ADR-001, K7: isolated runtimes). */
const OTHER_ENGINE: Record<"latte" | "twig", (rel: string) => boolean> = {
  // latte.zip: no Twig views, no Twig renderer
  latte: (rel) => rel.endsWith(".html.twig") || rel === "classes/Template/TwigRenderer.php",
  // twig.zip: no Latte views, no Latte renderer / loader / extension
  twig: (rel) =>
    rel.endsWith(".latte") ||
    ["LatteRenderer", "LatteEngine", "MultiRootLoader", "WordPressExtension"].some((c) => rel === `classes/Template/${c}.php`),
};

/** walk a directory (relative paths, files only) */
async function filesOf(dir: string, base = dir): Promise<string[]> {
  const out: string[] = [];
  for (const e of await readdir(dir, { withFileTypes: true })) {
    const p = join(dir, e.name);
    if (e.isDirectory()) out.push(...(await filesOf(p, base)));
    else out.push(relative(base, p).split("\\").join("/"));
  }
  return out;
}

/**
 * Parent release zip of ONE engine (ADR-001, K7). engine "latte" → dist/<slug>.zip (Latte only: latte/latte + UI8Kit
 * Latte primitives in lib/ui8kit-latte, .latte views); engine "twig" → dist/twig.zip, theme slug `twig` (Twig only:
 * twig/twig + UI8Kit Twig primitives in lib/ui8kit-twig, .html.twig views, text domain `twig`). Neither build contains
 * the other engine's library, primitives, renderer or views; `WPFasty\Template\Engines` names the build's engine.
 */
async function packTheme(id: string, engine: "latte" | "twig" = "latte", vendorCache?: string): Promise<void> {
  const preset = await loadPreset(id);
  if (preset.kind === "skin" || preset.parent) {
    throw new Error("theme:pack is for parent themes only");
  }

  const themeDir = join(themesRoot, preset.slug);
  if (!existsSync(themeDir)) {
    throw new Error(`Theme not found: themes/${preset.slug}`);
  }
  if (engine === "latte" && (!existsSync(join(primitivesRoot, "ui")) || !existsSync(join(primitivesRoot, "php")))) {
    throw new Error("Missing .workspaces/ui8kit-latte — run bun run ui:primitives first");
  }
  const slug = engine === "twig" ? "twig" : preset.slug;

  await mkdir(distRoot, { recursive: true });
  const stage = join(distRoot, `.stage-${slug}`);
  await rm(stage, { recursive: true, force: true });
  await mkdir(stage, { recursive: true });

  const stagedTheme = join(stage, slug);
  await cp(themeDir, stagedTheme, {
    recursive: true,
    filter: (src) => {
      const rel = relative(themeDir, src).split("\\").join("/");
      if (rel.includes("node_modules")) return false;
      if (rel.includes("~cache")) return false;
      if (rel.startsWith("vendor" + "/") || rel === "vendor") return false;
      if (rel.startsWith("tests" + "/") || rel === "tests") return false;
      if (rel.endsWith(".theme-build.json")) return false;
      if (OTHER_ENGINE[engine](rel)) return false;
      return true;
    },
  });

  // The build's engine (and default renderer): classes/Template/Engines.php.
  const enginesFile = join(stagedTheme, "classes", "Template", "Engines.php");
  const enginesSrc = await readFile(enginesFile, "utf8");
  const enginesOut = enginesSrc
    .replace(/public const BUILT = \[[^\]]*\];/, `public const BUILT = ['${engine}'];`)
    .replace(/public const DEFAULT = '[a-z]+';/, `public const DEFAULT = '${engine}';`);
  if (enginesOut === enginesSrc) throw new Error("Engines.php: BUILT/DEFAULT not found");
  await writeFile(enginesFile, enginesOut);

  const require: Record<string, string> = { php: ">=8.2" };
  let repositories: unknown[] = [];
  if (engine === "latte") {
    // Bundle primitives inside the theme for stock WP hosts (no monorepo path).
    const bundled = join(stagedTheme, "lib", "ui8kit-latte");
    await mkdir(bundled, { recursive: true });
    await cp(join(primitivesRoot, "ui"), join(bundled, "ui"), { recursive: true });
    await cp(join(primitivesRoot, "php", "UI8Kit"), join(bundled, "php", "UI8Kit"), { recursive: true });
    await copyFile(join(primitivesRoot, "composer.json"), join(bundled, "composer.json"));
    if (existsSync(join(primitivesRoot, "manifest.json"))) {
      await copyFile(join(primitivesRoot, "manifest.json"), join(bundled, "manifest.json"));
    }
    require["latte/latte"] = "^3.0";
    require["ui8kit/latte-primitives"] = "@dev";
    repositories = [{ type: "path", url: "lib/ui8kit-latte", options: { symlink: false } }];
  } else {
    const codegen = join(repoRoot, "packages", "ui8kit-codegen");
    if (!existsSync(join(codegen, "generated", "ui"))) {
      throw new Error("Missing packages/ui8kit-codegen/generated — run bun run codegen:verify first");
    }
    const tw = join(stagedTheme, "lib", "ui8kit-twig");
    await cp(join(codegen, "generated", "ui"), join(tw, "generated", "ui"), {
      recursive: true,
      filter: (src) => !/\.[a-z]+$/i.test(src) || src.endsWith(".html.twig"),
    });
    await mkdir(join(tw, "runtime", "php"), { recursive: true });
    await copyFile(join(codegen, "runtime", "php", "TwigExtension.php"), join(tw, "runtime", "php", "TwigExtension.php"));
    // Engine-agnostic UI8Kit runtime used by the Twig bindings (the Latte package is not shipped).
    await copyFile(join(codegen, "runtime", "php", "Rt.php"), join(tw, "runtime", "php", "Rt.php"));
    await copyFile(join(codegen, "generated", "php", "UI8Kit", "Classes.php"), join(tw, "runtime", "php", "Classes.php"));
    require["twig/twig"] = "^3.14";

    // Theme identity `twig`: style.css / readme.txt names, text domain of every translatable literal.
    for (const f of await filesOf(stagedTheme)) {
      const p = join(stagedTheme, f);
      if (f.endsWith(".php")) {
        const src = await readFile(p, "utf8");
        const out = src
          .replace(/'latte'(\s*,?\s*\))/g, "'twig'$1") // last argument of __(), esc_html__(), … and defaults like = 'latte')
          .replace("load_theme_textdomain('latte',", "load_theme_textdomain('twig',")
          .replace("$domain !== 'latte'", "$domain !== 'twig'")
          .replace(/Latte theme:/g, "Twig theme:")
          .replace(/themes\/latte\b/g, "themes/twig");
        // starter content / notices name the theme
        const named = f === "classes/Hooks/ThemeHooks.php" ? out.replace(/\bLatte\b/g, "Twig") : out;
        if (named !== src) await writeFile(p, named);
      }
    }
    const style = join(stagedTheme, "style.css");
    await writeFile(style, (await readFile(style, "utf8"))
      .replace(/^Theme Name: .*$/m, "Theme Name: Twig")
      .replace(/^Text Domain: .*$/m, "Text Domain: twig")
      .replace(/^Description: .*$/m, "Description: Clean WP FastY Twig theme — UI8Kit Codegen primitives, DI context, classic shell."));
    // wordpress.org: credit every bundled library (Twig BSD-3-Clause, Symfony polyfills MIT — GPL-compatible).
    const readme = join(stagedTheme, "readme.txt");
    let txt = (await readFile(readme, "utf8"))
      .split("\n")
      .filter((l) => !/^\* (Latte template engine|UI8Kit Latte primitives)/.test(l))
      .join("\n")
      .replace(/Latte/g, "Twig")
      .replace(/ui8kit-latte/g, "ui8kit-twig")
      .replace(/themes\/latte/g, "themes/twig");
    const credits = [
      "* Twig template engine (bundled in vendor/twig/twig) — BSD-3-Clause — https://twig.symfony.com/",
      "* Symfony polyfills / deprecation-contracts (bundled in vendor/symfony, Twig dependencies) — MIT — https://symfony.com/",
      "* UI8Kit Twig primitives (bundled in lib/ui8kit-twig) — MIT — https://github.com/alexy-os/wp-fasty",
    ].join("\n");
    if (!txt.includes("== Resources ==")) throw new Error("readme.txt has no == Resources == section");
    txt = txt.replace("== Resources ==\n", `== Resources ==\n\n${credits}\n`).replace(/\n\n\n/g, "\n\n");
    await writeFile(readme, txt);
    // Translation template of the `twig` text domain.
    await rm(join(stagedTheme, "languages", "latte.pot"), { force: true });
    const pot = spawnSync("wp", ["i18n", "make-pot", stagedTheme, join(stagedTheme, "languages", "twig.pot"), "--domain=twig",
      "--exclude=vendor,lib,~cache,node_modules", "--quiet",
      `--headers=${JSON.stringify({ "Report-Msgid-Bugs-To": "https://wordpress.org/support/theme/twig" })}`], { encoding: "utf8" });
    if (pot.status !== 0) throw new Error(`wp i18n make-pot failed (wp-cli is needed for the twig build): ${pot.stderr}`);
  }

  const releaseComposer = {
    name: `alexy-os/${slug}`,
    description: `${engine === "twig" ? "Twig" : preset.name} — WP FastY theme with vendored UI8Kit ${engine === "twig" ? "Twig" : "Latte"} primitives`,
    type: "wordpress-theme",
    license: "GPL-2.0-or-later",
    require,
    ...(repositories.length ? { repositories } : {}),
    autoload: {
      "psr-4": {
        "WPFasty\\": "classes/",
      },
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    config: {
      "optimize-autoloader": true,
      "sort-packages": true,
      platform: { php: "8.2" },
    },
  };
  await writeFile(join(stagedTheme, "composer.json"), JSON.stringify(releaseComposer, null, 2) + "\n");
  // Drop monorepo lock so release path-repo resolves cleanly.
  await rm(join(stagedTheme, "composer.lock"), { force: true });

  const composerCmd = resolveComposer(repoRoot);
  const runComposer = (...args: string[]) => {
    const r = spawnSync(composerCmd[0], [...composerCmd.slice(1), ...args, "--no-dev", "--optimize-autoloader", "--working-dir", stagedTheme],
      { stdio: "inherit", cwd: repoRoot, shell: process.platform === "win32" });
    if (r.status !== 0) {
      throw new Error(`composer ${args[0]} failed in staged theme — install Composer or place composer.phar at repo root`);
    }
  };
  if (!vendorCache) {
    runComposer("update");
  } else {
    // --vendor-cache <dir>: composer.lock reused per release composer.json, vendor/ reused per lock + bundled path repo
    // (lib/ui8kit-latte is copied into vendor, symlink: false). Same staged tree as `composer update`; no network on a hit.
    const lockFile = join(vendorCache, "locks", `${slug}-${sha256(await readFile(join(stagedTheme, "composer.json")))}.lock`);
    const lockHit = existsSync(lockFile);
    if (lockHit) await copyFile(lockFile, join(stagedTheme, "composer.lock"));
    const lockKey = async () => {
      const parts = [await readFile(join(stagedTheme, "composer.lock"))];
      const lib = join(stagedTheme, "lib", "ui8kit-latte");
      if (existsSync(lib)) for (const f of (await filesOf(lib)).sort()) parts.push(Buffer.from(f), await readFile(join(lib, f)));
      return sha256(Buffer.concat(parts));
    };
    const vendorDir = lockHit ? join(vendorCache, "vendor", await lockKey()) : "";
    if (lockHit && existsSync(join(vendorDir, "autoload.php"))) {
      await cp(vendorDir, join(stagedTheme, "vendor"), { recursive: true });
      console.log(`vendor cache hit: ${relative(vendorCache, vendorDir)}`);
    } else {
      runComposer(lockHit ? "install" : "update");
      await mkdir(dirname(lockFile), { recursive: true });
      await copyFile(join(stagedTheme, "composer.lock"), lockFile);
      const dest = join(vendorCache, "vendor", await lockKey());
      await rm(dest, { recursive: true, force: true });
      await cp(join(stagedTheme, "vendor"), dest, { recursive: true });
    }
  }

  // Isolation guard: nothing of the other engine may be in the build.
  const leaked = (await filesOf(stagedTheme)).filter((f) =>
    engine === "twig"
      ? f.startsWith("vendor/latte/") || f.startsWith("lib/ui8kit-latte/") || f.startsWith("vendor/ui8kit/") || f.endsWith(".latte")
      : f.startsWith("vendor/twig/") || f.startsWith("lib/ui8kit-twig/") || f.endsWith(".html.twig")
  );
  if (leaked.length) throw new Error(`${slug}.zip would ship the other engine: ${leaked.slice(0, 5).join(", ")}`);

  const zipPath = join(distRoot, `${slug}.zip`);
  await rm(zipPath, { force: true });
  await writeZipFromDir(stagedTheme, zipPath, slug);
  console.log(`Packed → ${zipPath}`);

  await rm(stage, { recursive: true, force: true });
}

/** Prefer `php composer.phar` in repo, else `composer` on PATH. */
function resolveComposer(root: string): string[] {
  for (const rel of ["composer.phar", "tools/composer.phar"]) {
    const phar = join(root, rel);
    if (existsSync(phar)) {
      return ["php", phar];
    }
  }
  return ["composer"];
}

async function newTheme(): Promise<void> {
  console.log(`
theme:new — scaffold instructions

Parent:
  1. Copy presets/themes/latte.json → presets/themes/<id>.json
  2. Edit layout / style / templates
  3. bun run theme:build -- <id>

Skin (no Composer):
  1. Copy presets/themes/latte-skin.json → presets/themes/<id>.json
  2. Set parent: "latte", kind: "skin"
  3. bun run theme:build -- <id>

Pack release zip:
  bun run theme:pack -- latte [--engine twig] [--vendor-cache dir]   (or WPFASTY_VENDOR_CACHE=dir)
`);
}

async function main(): Promise<void> {
  const [cmd, arg] = process.argv.slice(2);
  switch (cmd) {
    case "generate": {
      generateCli(process.argv.slice(3));
      return;
    }
    case "new-site": {
      await newSiteCli(process.argv.slice(3));
      return;
    }
    case "package": {
      await packageCli(process.argv.slice(3));
      return;
    }
    case "roundtrip": {
      process.exitCode = roundtrip();
      return;
    }
    case "build": {
      await buildTheme(arg ?? "latte");
      return;
    }
    case "pack": {
      const ei = process.argv.indexOf("--engine");
      const eng = ei > 0 ? process.argv[ei + 1] : "latte";
      if (eng !== "latte" && eng !== "twig") throw new Error(`--engine must be latte or twig (got ${eng})`);
      const vi = process.argv.indexOf("--vendor-cache");
      const vc = vi > 0 ? process.argv[vi + 1] : process.env.WPFASTY_VENDOR_CACHE;
      await packTheme(arg && !arg.startsWith("--") ? arg : "latte", eng, vc ? resolve(vc) : undefined);
      return;
    }
    case "new":
      await newTheme();
      return;
    default:
      console.error("Usage: theme-builder <build|pack|new> [theme-id] | generate <manifest> [--out dir] [--no-pot] | package <manifest> [--out dir] | new-site [--answers f] [--out f] [--package] | roundtrip");
      process.exitCode = 1;
  }
}

main().catch((err) => {
  console.error(err instanceof Error ? err.message : err);
  process.exitCode = 1;
});
