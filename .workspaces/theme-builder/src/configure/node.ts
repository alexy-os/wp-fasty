// Filesystem side of the configurator: load examples + palettes, write + validate the manifest, optionally package.
import { spawnSync } from "node:child_process";
import { existsSync, mkdirSync, readdirSync, readFileSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { type Answers, buildManifest, type Data, type J } from "./core";

export const root = resolve(dirname(new URL(import.meta.url).pathname), "../../../..");
const json = (p: string): J => JSON.parse(readFileSync(p, "utf8"));

export function loadData(): Data {
  const examples: Record<string, J> = {};
  for (const f of readdirSync(join(root, "manifests/examples")).filter((n) => n.endsWith(".json"))) {
    const m = json(join(root, "manifests/examples", f));
    // Only archetypes with a package can be generated.
    if (existsSync(join(root, "presets/archetypes", m.archetype, "archetype.json"))) examples[m.archetype] = m;
  }
  const palettes: Record<string, J> = {};
  for (const f of readdirSync(join(root, "presets/palettes")).filter((n) => n.endsWith(".json"))) {
    const p = json(join(root, "presets/palettes", f));
    palettes[p.id] = p;
  }
  const schemaId = json(join(root, "schema/site.manifest.schema.json")).$id as string;
  return { examples, palettes, schemaId };
}

/** Schema + semantic rules via tools/manifest/validate.ts; returns its report. */
export function validateManifestFile(file: string): { ok: boolean; report: string } {
  const r = spawnSync("bun", [join(root, "tools/manifest/validate.ts"), resolve(file)], { encoding: "utf8" });
  return { ok: r.status === 0, report: (r.stdout + r.stderr).trim() };
}

/** Keeps every generated path inside dist/ (CLI default, enforced for the MCP server). */
export function distPath(rel: string): string {
  const dist = join(root, "dist");
  const p = resolve(dist, rel);
  if (p !== dist && !p.startsWith(dist + "/")) throw new Error(`path "${rel}" escapes dist/`);
  return p;
}

export function newSite(answers: Answers, opts: { out?: string } = {}): { file: string; manifest: J; warnings: string[]; report: string } {
  const { manifest, warnings } = buildManifest(answers, loadData());
  const file = resolve(opts.out ?? distPath(`manifests/${manifest.theme.slug}.json`));
  mkdirSync(dirname(file), { recursive: true });
  writeFileSync(file, JSON.stringify(manifest, null, 2) + "\n");
  const v = validateManifestFile(file);
  if (!v.ok) throw new Error(`generated manifest is invalid:\n${v.report}`);
  return { file, manifest, warnings, report: v.report };
}
