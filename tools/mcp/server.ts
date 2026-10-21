// WP FastY configurator MCP server (stdio). Tools build/validate/generate/package site manifests;
// every file it writes lands under dist/ — nothing is published or uploaded.
//   bun tools/mcp/server.ts
import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { join, relative } from "node:path";
import { z } from "zod";
import { type Answers, buildManifest, ConfigureError } from "../../.workspaces/theme-builder/src/configure/core";
import { distPath, loadData, root, validateManifestFile } from "../../.workspaces/theme-builder/src/configure/node";
import { generate } from "../../.workspaces/theme-builder/src/generate/index";
import { packageSite } from "../../.workspaces/theme-builder/src/generate/package";

// stdout carries the protocol: route library logging to stderr.
console.log = (...a: unknown[]) => console.error(...a);

type J = Record<string, any>;
const text = (v: unknown) => ({ content: [{ type: "text" as const, text: typeof v === "string" ? v : JSON.stringify(v, null, 2) }] });
const fail = (msg: string) => ({ ...text(msg), isError: true });
const rel = (p: string) => relative(root, p);

/** answers → manifest, or a manifest as given; written to dist/manifests/<slug>.json and validated. */
function resolveManifest(input: { answers?: J; manifest?: J }): { file: string; manifest: J; warnings: string[] } {
  let manifest: J;
  let warnings: string[] = [];
  if (input.answers && input.manifest) throw new Error("pass either answers or manifest");
  if (input.answers) ({ manifest, warnings } = buildManifest(input.answers as Answers, loadData()));
  else if (input.manifest) manifest = input.manifest;
  else throw new Error("answers or manifest is required");
  const slug = String(manifest?.theme?.slug ?? "");
  if (!/^[a-z][a-z0-9-]*$/.test(slug)) throw new Error("manifest.theme.slug is missing or invalid");
  const file = distPath(`manifests/${slug}.json`);
  mkdirSync(join(file, ".."), { recursive: true });
  writeFileSync(file, JSON.stringify(manifest, null, 2) + "\n");
  const v = validateManifestFile(file);
  if (!v.ok) throw new Error(`manifest invalid:\n${v.report}`);
  return { file, manifest, warnings };
}

const guard = async (fn: () => Promise<unknown> | unknown) => {
  try { return text(await fn()); }
  catch (e) { return fail(e instanceof ConfigureError ? e.message : (e as Error).message); }
};

const server = new McpServer({ name: "wpfasty-configurator", version: "0.1.0" });
const source = { answers: z.record(z.string(), z.any()).optional().describe("Configurator answers (see list_archetypes / README)"),
  manifest: z.record(z.string(), z.any()).optional().describe("A full site manifest v0.2") };

server.registerTool("list_archetypes", {
  description: "Archetypes (child-theme code families), their required post types, landings and palette presets.",
  inputSchema: {},
}, async () => guard(() => {
  const d = loadData();
  return {
    archetypes: Object.entries(d.examples).map(([id, m]) => ({
      id, reference: m.theme.name, description: m.theme.description,
      requiredPostTypes: (m.postTypes ?? []).map((p: J) => p.name),
      landings: (m.builder?.landings ?? []).map((l: J) => l.file),
    })),
    palettes: Object.values(d.palettes).map((p: J) => ({ id: p.id, label: p.label, description: p.description })),
    fieldTypes: ["string", "text", "url", "email", "integer", "number", "money", "boolean", "date"],
    // Equal options: same design, builder landings and wordpress.org output; twig packages the twig parent (twig.zip), latte the latte parent — isolated (ADR-001, K7).
    engines: ["latte", "twig"],
  };
}));

server.registerTool("describe_schema", {
  description: "The site manifest v0.2 JSON Schema, whole or one $defs entry / top-level property.",
  inputSchema: { section: z.string().optional().describe("e.g. postType, brand, output") },
}, async ({ section }) => guard(() => {
  const s = JSON.parse(readFileSync(join(root, "schema/site.manifest.schema.json"), "utf8"));
  if (!section) return s;
  const hit = s.$defs?.[section] ?? s.properties?.[section];
  if (!hit) throw new Error(`no section "${section}" (have: ${[...Object.keys(s.properties), ...Object.keys(s.$defs)].join(", ")})`);
  return hit;
}));

server.registerTool("validate_manifest", {
  description: "Validate a manifest (or answers → manifest): schema, semantic rules. Writes dist/manifests/<slug>.json.",
  inputSchema: source,
}, async (input) => guard(() => {
  const r = resolveManifest(input);
  return { valid: true, file: rel(r.file), warnings: r.warnings };
}));

server.registerTool("generate_site", {
  description: "Generate the child theme from answers or a manifest into dist/generated/<slug>/ (no zip).",
  inputSchema: { ...source, pot: z.boolean().optional().describe("build languages/*.pot with wp-cli (default true)") },
}, async (input) => guard(() => {
  const r = resolveManifest(input);
  const dir = generate(r.file, distPath(`generated/${r.manifest.theme.slug}`), { pot: input.pot !== false });
  return { manifest: rel(r.file), theme: rel(dir), warnings: r.warnings };
}));

server.registerTool("package_site", {
  description: "Package answers or a manifest into dist/<slug>/: parent + child zips, plugin zips, demo.zip, Playground bundle.",
  inputSchema: source,
}, async (input) => guard(async () => {
  const r = resolveManifest(input);
  const p = await packageSite(r.file, distPath(r.manifest.theme.slug));
  return { manifest: rel(r.file), dir: rel(p.dir), files: p.files, warnings: r.warnings };
}));

await server.connect(new StdioServerTransport());
