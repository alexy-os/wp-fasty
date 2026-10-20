// bun run configure:test — buildManifest invariants:
//  1. reference answers rebuild manifests/examples/<archetype>.json exactly (except $schema);
//  2. low-contrast free tokens, type/taxonomy clashes, reserved names and unknown palettes are rejected.
import { buildManifest, ConfigureError, type J } from "./core";
import { AA, checkTokens, contrast } from "./contrast";
import { loadData } from "./node";

const data = loadData();
let fail = 0;
const check = (name: string, ok: boolean, detail = "") => { console.log(`${ok ? "✓" : "✗"} ${name}${detail ? ` — ${detail}` : ""}`); if (!ok) fail++; };
const rejects = (name: string, answers: J, needle: string) => {
  try { buildManifest(answers as any, data); check(name, false, "accepted"); }
  catch (e) { check(name, e instanceof ConfigureError && e.problems.some((p) => p.includes(needle)), (e as Error).message.split("\n")[1]?.trim()); }
};
const palette: Record<string, string> = { press: "editorial-warm", folio: "midnight-lime", shop: "fresh-retail" };
for (const [arch, ex] of Object.entries(data.examples)) {
  const { manifest } = buildManifest({ archetype: arch, name: ex.theme.name, palette: palette[arch] }, { ...data, schemaId: ex.$schema });
  const same = JSON.stringify(manifest) === JSON.stringify(ex);
  check(`${arch}: reference answers rebuild manifests/examples/${arch}.json`, same);
}
for (const [id, p] of Object.entries(data.palettes)) {
  const issues = checkTokens({ light: p.light, dark: p.dark });
  check(`palette ${id}: every text/surface pair >= ${AA}:1`, !issues.length, issues.map((i) => `${i.scheme} ${i.fg}/${i.bg} ${i.ratio}`).join(", "));
}
// The old neutral muted-foreground (0.556) on muted (0.97) is 4.34:1 and must fail the gate.
check("muted-foreground on muted below AA is caught", contrast("oklch(0.556 0 0)", "oklch(0.97 0 0)") < AA
  && checkTokens({ light: { muted: "oklch(0.97 0 0)", "muted-foreground": "oklch(0.556 0 0)" } }).some((i) => i.fg === "muted-foreground" && i.bg === "muted"));
rejects("low-contrast tokens rejected", { archetype: "press", name: "Grey Site", tokens: { light: { background: "#ffffff", foreground: "#aaaaaa" } } }, "contrast light foreground on background");
rejects("archetype type clash rejected", { archetype: "shop", name: "Clash Shop", postTypes: [{ name: "product", labels: { singular: "P", plural: "Ps" } }] }, "clashes with archetype shop type");
rejects("taxonomy clash rejected", { archetype: "folio", name: "Clash Folio", postTypes: [{ name: "case_study", labels: { singular: "C", plural: "Cs" }, taxonomies: { project_type: { labels: { singular: "T", plural: "Ts" } } } }] }, "taxonomy \"project_type\" clashes");
rejects("reserved name rejected", { archetype: "press", name: "Reserved", postTypes: [{ name: "page", labels: { singular: "P", plural: "Ps" } }] }, "reserved platform post type");
rejects("unknown palette rejected", { archetype: "press", name: "Nope", palette: "rainbow" }, "unknown palette");
rejects("parent slug rejected", { archetype: "press", name: "Latte" }, "is the parent theme");
console.log(fail ? `${fail} failed` : "configure self-test OK");
process.exit(fail ? 1 : 0);
