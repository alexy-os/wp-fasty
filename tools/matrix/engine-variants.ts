/**
 * Engine matrix: for every configurator fixture (manifests/answers/*.json) writes the answers of the other engine
 * (latte ↔ twig), and for every example site its Twig twin manifest (manifest-<slug>-twig.json), to dist/answers-matrix/,
 * so CI packages, Theme-Checks and renders every site in both engines (each on its own engine's parent).
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
// Reference sites (latte-press/folio/shop, Latte): their Twig twins as full site manifests — same archetype, brand,
// prefix, demo and builder landings; only the engine and the theme identity differ (<slug>-twig). CI packages them with
// theme:package and compares every page of the pair live (Latte site on latte.zip vs Twig site on twig.zip).
for (const file of readdirSync(join(root, "manifests/examples")).filter((f) => f.endsWith(".json")).sort()) {
  const m = JSON.parse(readFileSync(join(root, "manifests/examples", file), "utf8"));
  const slug = `${m.theme.slug}-twig`;
  put(`manifest-${slug}.json`, {
    ...m,
    engine: "twig",
    theme: { ...m.theme, name: `${m.theme.name} Twig`, slug, textDomain: slug, namespace: `${m.theme.namespace}Twig` },
  });
}
console.log(written.map((f) => f.slice(root.length + 1)).join("\n"));
