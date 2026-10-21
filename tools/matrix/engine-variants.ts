/**
 * Engine matrix: for every configurator fixture (manifests/answers/*.json) and every example archetype, writes the
 * answers of the other engine (latte ↔ twig) to dist/answers-matrix/, so CI packages, Theme-Checks and smoke-renders
 * every site in both engines.
 *   bun tools/matrix/engine-variants.ts   → prints the written files
 */
import { mkdirSync, readdirSync, readFileSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const root = join(import.meta.dir, "../..");
const out = join(root, "dist/answers-matrix");
mkdirSync(out, { recursive: true });
const written: string[] = [];
const put = (name: string, a: Record<string, unknown>) => {
  const f = join(out, name);
  writeFileSync(f, JSON.stringify(a, null, 2) + "\n");
  written.push(f);
};
for (const file of readdirSync(join(root, "manifests/answers")).filter((f) => f.endsWith(".json")).sort()) {
  const a = JSON.parse(readFileSync(join(root, "manifests/answers", file), "utf8"));
  const other = a.engine === "twig" ? "latte" : "twig";
  put(`${file.replace(/\.json$/, "")}-${other}.json`, { ...a, name: `${a.name} ${other === "twig" ? "Twig" : "Latte"}`, engine: other });
}
// Reference archetypes (latte-press/folio/shop are Latte): their Twig twins with the example landings.
for (const file of readdirSync(join(root, "manifests/examples")).filter((f) => f.endsWith(".json")).sort()) {
  const m = JSON.parse(readFileSync(join(root, "manifests/examples", file), "utf8"));
  const arch = file.replace(/\.json$/, "");
  put(`example-${arch}-twig.json`, {
    archetype: arch, name: `Reference ${arch[0].toUpperCase()}${arch.slice(1)} Twig`, engine: "twig",
    landings: (m.builder?.landings ?? []).map((l: { file: string }) => l.file),
  });
}
console.log(written.map((f) => f.slice(root.length + 1)).join("\n"));
