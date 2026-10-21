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
import { dirname, join, relative } from "node:path";
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

/**
 * Parent release zip. engine "twig" adds the Twig runtime: twig/twig in the release composer.json and the codegen
 * Twig primitives under lib/ui8kit-twig/ → dist/<slug>-twig.zip (zip root stays <slug>/). The default (Latte-only)
 * zip is the wordpress.org build.
 */
async function packTheme(id: string, engine: "latte" | "twig" = "latte"): Promise<void> {
  const preset = await loadPreset(id);
  if (preset.kind === "skin" || preset.parent) {
    throw new Error("theme:pack is for parent themes only");
  }

  const themeDir = join(themesRoot, preset.slug);
  if (!existsSync(themeDir)) {
    throw new Error(`Theme not found: themes/${preset.slug}`);
  }
  if (!existsSync(join(primitivesRoot, "ui")) || !existsSync(join(primitivesRoot, "php"))) {
    throw new Error("Missing .workspaces/ui8kit-latte — run bun run ui:primitives first");
  }

  await mkdir(distRoot, { recursive: true });
  const stage = join(distRoot, `.stage-${preset.slug}`);
  await rm(stage, { recursive: true, force: true });
  await mkdir(stage, { recursive: true });

  const stagedTheme = join(stage, preset.slug);
  await cp(themeDir, stagedTheme, {
    recursive: true,
    filter: (src) => {
      const rel = relative(themeDir, src);
      if (rel.includes("node_modules")) return false;
      if (rel.includes("~cache")) return false;
      if (rel.startsWith("vendor" + "/") || rel === "vendor") return false;
      if (rel.startsWith("tests" + "/") || rel === "tests") return false;
      if (rel.endsWith(".theme-build.json")) return false;
      return true;
    },
  });

  // Bundle primitives inside the theme for stock WP hosts (no monorepo path).
  const bundled = join(stagedTheme, "lib", "ui8kit-latte");
  await mkdir(bundled, { recursive: true });
  await cp(join(primitivesRoot, "ui"), join(bundled, "ui"), { recursive: true });
  await cp(join(primitivesRoot, "php", "UI8Kit"), join(bundled, "php", "UI8Kit"), {
    recursive: true,
  });
  await copyFile(join(primitivesRoot, "composer.json"), join(bundled, "composer.json"));
  if (existsSync(join(primitivesRoot, "manifest.json"))) {
    await copyFile(join(primitivesRoot, "manifest.json"), join(bundled, "manifest.json"));
  }

  // Release composer.json: path repo → bundled lib (dev monorepo keeps ../../.workspaces)
  if (engine === "twig") {
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
    // Engine-agnostic UI8Kit runtime used by the Twig bindings — bundled so a Twig site needs no Latte package.
    await copyFile(join(codegen, "runtime", "php", "Rt.php"), join(tw, "runtime", "php", "Rt.php"));
    await copyFile(join(codegen, "generated", "php", "UI8Kit", "Classes.php"), join(tw, "runtime", "php", "Classes.php"));
    // wordpress.org: credit every bundled library (Twig BSD-3-Clause, Symfony polyfills MIT — GPL-compatible).
    const readme = join(stagedTheme, "readme.txt");
    const txt = await readFile(readme, "utf8");
    const credits = [
      "* Twig template engine (bundled in vendor/twig/twig) — BSD-3-Clause — https://twig.symfony.com/",
      "* Symfony polyfills / deprecation-contracts (bundled in vendor/symfony, Twig dependencies) — MIT — https://symfony.com/",
      "* UI8Kit Twig primitives (bundled in lib/ui8kit-twig) — MIT — https://github.com/alexy-os/wp-fasty",
    ].join("\n");
    if (!txt.includes("== Resources ==")) throw new Error("readme.txt has no == Resources == section");
    await writeFile(readme, txt.replace("== Resources ==\n", `== Resources ==\n\n${credits}\n`).replace(/\n\n\n/g, "\n\n"));
  }

  const releaseComposer = {
    name: `alexy-os/${preset.slug}`,
    description: `${preset.name} — WP FastY theme with vendored UI8Kit Latte primitives`,
    type: "wordpress-theme",
    license: "GPL-2.0-or-later",
    require: {
      php: ">=8.2",
      "latte/latte": "^3.0",
      "ui8kit/latte-primitives": "@dev",
      ...(engine === "twig" ? { "twig/twig": "^3.14" } : {}),
    },
    repositories: [
      {
        type: "path",
        url: "lib/ui8kit-latte",
        options: { symlink: false },
      },
    ],
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
  await writeFile(
    join(stagedTheme, "composer.json"),
    JSON.stringify(releaseComposer, null, 2) + "\n"
  );
  // Drop monorepo lock so release path-repo resolves cleanly.
  await rm(join(stagedTheme, "composer.lock"), { force: true });

  const composerCmd = resolveComposer(repoRoot);
  const composer = spawnSync(
    composerCmd[0],
    [
      ...composerCmd.slice(1),
      "update",
      "--no-dev",
      "--optimize-autoloader",
      "--working-dir",
      stagedTheme,
    ],
    { stdio: "inherit", cwd: repoRoot, shell: process.platform === "win32" }
  );
  if (composer.status !== 0) {
    throw new Error(
      "composer update failed in staged theme — install Composer or place composer.phar at repo root"
    );
  }

  const zipPath = join(distRoot, engine === "twig" ? `${preset.slug}-twig.zip` : `${preset.slug}.zip`);
  await rm(zipPath, { force: true });
  await writeZipFromDir(stagedTheme, zipPath, preset.slug);
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
  bun run theme:pack -- latte
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
      await packTheme(arg && !arg.startsWith("--") ? arg : "latte", eng);
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
