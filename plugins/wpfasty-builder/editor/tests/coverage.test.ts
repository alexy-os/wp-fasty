/**
 * Gate: every block the editor offers (the cards: every variant of the catalog) and every block of the template pages,
 * the starters and the demo landings is recognised by the editor and opens editable fields in the inspector — the
 * block and variant are in the catalog, the block config has fields (or the block is listed in NO_SETTINGS), no field
 * is only raw JSON, and every value the variant sample or the layout sets (texts, lists, nested objects) is reachable
 * through a typed field, never only through "Advanced (JSON)". The live check of the same (a click on every block in
 * the preview opens its fields) is a Playwright run against a WP, see the README.
 */
import { describe, expect, test } from "bun:test";
import { readdirSync, readFileSync } from "node:fs";
import { join } from "node:path";
import type { BlockSpec, Json, RecordField } from "../src/types";
import { blockConfig, NO_SETTINGS, type Field } from "../src/fields/schema";
import { isObj, missingBlocks } from "../src/util";

const plugin = join(import.meta.dir, "../..");
const read = (p: string) => JSON.parse(readFileSync(p, "utf8"));
type Cat = { blocks: BlockSpec[]; records: Record<string, RecordField[]>; collections: { id: string; blocks: string[]; pages?: { id: string; blocks: { block: string; variant: string; props: Json }[] }[] }[] };
type Use = { where: string; collection: string; block: string; variant: string; props: Json };

function usesOf(cat: Cat): Use[] {
  const out: Use[] = [];
  for (const b of cat.blocks) for (const v of b.variants) out.push({ where: "card", collection: "", block: b.id, variant: v.id, props: {} });
  for (const c of cat.collections) for (const p of c.pages ?? []) for (const b of p.blocks) out.push({ where: `template page ${c.id}/${p.id}`, collection: c.id, ...b });
  const layouts: [string, string][] = [
    ...readdirSync(join(plugin, "starters")).map((f) => [`starter ${f}`, join(plugin, "starters", f)] as [string, string]),
    ...readdirSync(join(plugin, "../../tools/builder/landings")).map((f) => [`landing ${f}`, join(plugin, "../../tools/builder/landings", f)] as [string, string]),
  ];
  for (const [where, file] of layouts) {
    const l = read(file).layout;
    for (const b of l.blocks) out.push({ where, collection: l.collection ?? "", block: b.block, variant: b.variant, props: b.props ?? {} });
  }
  return out;
}

/** paths of a value no typed field reaches (or whose value the field cannot show) */
function unreached(f: Field, v: Json | undefined, path: string, out: string[]) {
  if (v === undefined || v === null) return;
  switch (f.type) {
    case "text": case "textarea": if (typeof v !== "string") out.push(`${path}: ${f.type} field, ${typeof v} value`); return;
    case "number": if (typeof v !== "number") out.push(`${path}: number field, ${typeof v} value`); return;
    case "boolean": if (typeof v !== "boolean") out.push(`${path}: boolean field, ${typeof v} value`); return;
    case "select": case "radio": if (!f.options.some((o) => o.value === v)) out.push(`${path}: ${JSON.stringify(v)} is not an option`); return;
    case "map": if (!isObj(v) || Object.values(v).some((x) => typeof x !== "string")) out.push(`${path}: map field, value not a string map`); return;
    case "binding": unreached(f.field, v, path, out); return;
    case "array":
      if (!Array.isArray(v)) out.push(`${path}: list field, ${typeof v} value`);
      else v.forEach((x, i) => unreached(f.item, x, `${path}[${i}]`, out));
      return;
    case "object":
      if (!isObj(v)) { out.push(`${path}: object field, ${typeof v} value`); return; }
      for (const [k, x] of Object.entries(v)) {
        if (f.objectFields[k]) unreached(f.objectFields[k], x, `${path}.${k}`, out);
        else if (k !== "className") out.push(`${path}.${k}: no field`);
      }
      return;
    case "json": out.push(`${path}: only a JSON field`);
  }
}

for (const runtime of ["latte", "twig"]) {
  describe(`${runtime}: every block of the cards, template pages, starters and landings opens fields`, () => {
    const cat = read(join(plugin, `sources/ui8kit/${runtime}/catalog.json`)) as Cat;
    const blocks = new Map(cat.blocks.map((b) => [b.id, b]));
    const configs = new Map(cat.blocks.map((b) => [b.id, blockConfig(b, cat.records)]));
    const uses = usesOf(cat);

    test("the sources are all there", () => {
      expect(uses.filter((u) => u.where === "card").length).toBe(480);
      expect(new Set(uses.filter((u) => u.where.startsWith("template page")).map((u) => u.where)).size).toBe(63);
      expect(uses.filter((u) => u.where.startsWith("starter")).length).toBeGreaterThan(0);
      expect(uses.filter((u) => u.where.startsWith("landing")).length).toBeGreaterThan(0);
    });

    test("every block and variant is in the catalog (outside its collection too)", () => {
      const bad = uses.filter((u) => !blocks.get(u.block)?.variants.some((v) => v.id === u.variant)).map((u) => `${u.block}:${u.variant} (${u.where})`);
      expect(bad).toEqual([]);
      // a starter may use a block its collection does not list: the editor loads it from the whole catalog
      const press = uses.filter((u) => u.where === "starter press.json");
      const col = cat.collections.find((c) => c.id === press[0]?.collection);
      expect(missingBlocks(press, col!.blocks.map((id) => ({ id }))).every((id) => blocks.has(id))).toBe(true);
    });

    test("every block has typed fields (NO_SETTINGS: none, on purpose)", () => {
      const bad: string[] = [];
      for (const [id, cfg] of configs) {
        const n = Object.keys(cfg.fields).length;
        if (!n && !NO_SETTINGS.includes(id)) bad.push(`${id}: no fields`);
        if (n && NO_SETTINGS.includes(id)) bad.push(`${id}: has fields but is listed in NO_SETTINGS`);
        if (n && Object.values(cfg.fields).every((f) => f.type === "json")) bad.push(`${id}: only JSON fields`);
      }
      expect(bad).toEqual([]);
    });

    test("every value of the variant samples and the layouts is reachable through a typed field", () => {
      const bad = new Set<string>();
      for (const u of uses) {
        const b = blocks.get(u.block)!;
        const cfg = configs.get(u.block)!;
        const sample = b.variants.find((v) => v.id === u.variant)!.props;
        for (const props of [sample, u.props]) {
          const out: string[] = [];
          for (const [k, v] of Object.entries(isObj(props) ? props : {})) {
            if (k === "id" || k === "className") continue;
            if (cfg.fields[k]) unreached(cfg.fields[k], v, k, out);
            else out.push(`${k}: no field`);
          }
          for (const o of out) bad.add(`${u.block}:${u.variant} ${o} (${u.where})`);
        }
      }
      expect([...bad]).toEqual([]);
    });
  });
}
