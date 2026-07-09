/**
 * theme-builder CLI — assemble WordPress themes from presets.
 *
 *   bun src/cli.ts build <theme-id>
 *   bun src/cli.ts pack <theme-id>
 *   bun src/cli.ts new
 */

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

async function syncBlocks(themeDir: string): Promise<string[]> {
  const blocksDir = join(presetsRoot, "blocks");
  const outDir = join(themeDir, "views", "blocks");
  await mkdir(outDir, { recursive: true });
  const copied: string[] = [];
  if (!existsSync(blocksDir)) return copied;

  for (const name of await readdir(blocksDir)) {
    if (!name.endsWith(".latte")) continue;
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
    const body = pageViewSource(blocks, `${template} — generated from presets/themes/${preset.id}.json`);
    await writeFile(file, body);
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
Theme URI: https://github.com/godudy/wpfasty
Author: godudy
Author URI: https://github.com/godudy
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

async function packTheme(id: string): Promise<void> {
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
  const releaseComposer = {
    name: `godudy/${preset.slug}`,
    description: `${preset.name} — WP FastY theme with vendored UI8Kit Latte primitives`,
    type: "wordpress-theme",
    license: "GPL-2.0-or-later",
    require: {
      php: ">=8.2",
      "latte/latte": "^3.0",
      "ui8kit/latte-primitives": "@dev",
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

  const composer = spawnSync(
    "composer",
    ["update", "--no-dev", "--optimize-autoloader", "--working-dir", stagedTheme],
    { stdio: "inherit", cwd: repoRoot }
  );
  if (composer.status !== 0) {
    throw new Error("composer install failed in staged theme");
  }

  const zipPath = join(distRoot, `${preset.slug}.zip`);
  await rm(zipPath, { force: true });
  const zip = spawnSync("zip", ["-r", zipPath, preset.slug, "-x", "*/~cache/*"], {
    cwd: stage,
    stdio: "inherit",
  });
  if (zip.status !== 0) {
    // Fallback without zip binary: tar.gz
    const tarPath = join(distRoot, `${preset.slug}.tar.gz`);
    const tar = spawnSync("tar", ["-czf", tarPath, preset.slug], {
      cwd: stage,
      stdio: "inherit",
    });
    if (tar.status !== 0) {
      throw new Error("Failed to create zip/tar archive");
    }
    console.log(`Packed → ${tarPath}`);
  } else {
    console.log(`Packed → ${zipPath}`);
  }

  await rm(stage, { recursive: true, force: true });
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
    case "build": {
      await buildTheme(arg ?? "latte");
      return;
    }
    case "pack": {
      await packTheme(arg ?? "latte");
      return;
    }
    case "new":
      await newTheme();
      return;
    default:
      console.error("Usage: theme-builder <build|pack|new> [theme-id]");
      process.exitCode = 1;
  }
}

main().catch((err) => {
  console.error(err instanceof Error ? err.message : err);
  process.exitCode = 1;
});
