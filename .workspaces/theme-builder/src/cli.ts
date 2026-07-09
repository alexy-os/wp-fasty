/**
 * theme-builder CLI — assemble WordPress themes from presets.
 *
 *   bun src/cli.ts build <theme-id>
 *   bun src/cli.ts new
 */

import { existsSync } from "node:fs";
import { mkdir, readFile, writeFile } from "node:fs/promises";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";

const builderRoot = join(dirname(fileURLToPath(import.meta.url)), "..");
const repoRoot = join(builderRoot, "..", "..");
const presetsRoot = join(repoRoot, "presets");
const themesRoot = join(repoRoot, "themes");
const primitivesRoot = join(repoRoot, ".workspaces", "ui8kit-latte");

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
  // Minimal YAML subset: key: value and nested lists — prefer JSON for now.
  throw new Error(`YAML presets require JSON for v0.1 — use presets/themes/${id}.json`);
}

async function buildTheme(id: string): Promise<void> {
  const preset = await loadPreset(id);
  const out = join(themesRoot, preset.slug);

  if (!existsSync(join(primitivesRoot, "manifest.json"))) {
    console.warn(
      "Warning: .workspaces/ui8kit-latte missing — run `bun run ui:primitives` first."
    );
  }

  // Reference theme already lives under themes/; build validates + writes stamp.
  if (!existsSync(out)) {
    await mkdir(out, { recursive: true });
    console.log(`Created ${out} (empty shell — copy skeleton in a later step)`);
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
  };

  await writeFile(join(out, ".theme-build.json"), JSON.stringify(stamp, null, 2) + "\n");
  console.log(`Built theme stamp → themes/${preset.slug}/.theme-build.json`);
  console.log(`  layout: ${preset.layout}`);
  console.log(`  style:  ${preset.style}`);
  console.log(`  blocks: ${Object.values(preset.templates).flat().join(", ") || "(none)"}`);
}

async function newTheme(): Promise<void> {
  console.log(`
theme:new — interactive scaffold (v0.1 stub)

Next steps:
  1. Copy presets/themes/latte.json → presets/themes/<your-id>.json
  2. Edit layout / style / templates
  3. bun run theme:build -- <your-id>

Available layouts:  presets/layouts/
Available blocks:   presets/blocks/
Available styles:   presets/styles/
`);
}

async function main(): Promise<void> {
  const [cmd, arg] = process.argv.slice(2);
  switch (cmd) {
    case "build": {
      const id = arg ?? "latte";
      await buildTheme(id);
      return;
    }
    case "new":
      await newTheme();
      return;
    default:
      console.error("Usage: theme-builder <build|new> [theme-id]");
      process.exitCode = 1;
  }
}

main().catch((err) => {
  console.error(err instanceof Error ? err.message : err);
  process.exitCode = 1;
});
