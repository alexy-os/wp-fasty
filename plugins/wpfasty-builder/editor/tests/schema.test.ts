import { describe, expect, test } from "bun:test";
import { readFileSync } from "node:fs";
import { join } from "node:path";
import type { BlockSpec, RecordField } from "../src/types";
import { blockConfig, emptyOf, fieldOf, mergeSamples, parseType, summaryOf, typeOfSample, type Field } from "../src/fields/schema";

const catalog = (runtime: string) =>
  JSON.parse(readFileSync(join(import.meta.dir, `../../sources/ui8kit/${runtime}/catalog.json`), "utf8")) as {
    blocks: BlockSpec[]; records: Record<string, RecordField[]>;
  };

describe("parseType", () => {
  test("unions of literals are enums", () => {
    expect(parseType('"split" | "stacked"')).toEqual({ kind: "enum", options: ["split", "stacked"] });
    expect(parseType("1 | 2")).toEqual({ kind: "enum", options: [1, 2] });
    expect(parseType('("a" | "b") | undefined')).toEqual({ kind: "enum", options: ["a", "b"] });
    expect(parseType('"only"')).toEqual({ kind: "enum", options: ["only"] });
    expect(parseType("true | false")).toEqual({ kind: "boolean" });
  });
  test("scalars, lists, maps, records, inline objects", () => {
    expect(parseType("string")).toEqual({ kind: "string" });
    expect(parseType(" number ")).toEqual({ kind: "number" });
    expect(parseType("boolean")).toEqual({ kind: "boolean" });
    expect(parseType("Stat[]")).toEqual({ kind: "array", of: { kind: "record", name: "Stat" } });
    expect(parseType("string[]")).toEqual({ kind: "array", of: { kind: "string" } });
    expect(parseType("Array<Feature>")).toEqual({ kind: "array", of: { kind: "record", name: "Feature" } });
    expect(parseType('("a" | "b")[]')).toEqual({ kind: "array", of: { kind: "enum", options: ["a", "b"] } });
    expect(parseType("Record<string, string>")).toEqual({ kind: "map", value: { kind: "string" } });
    expect(parseType("LinkActions")).toEqual({ kind: "record", name: "LinkActions" });
    expect(parseType("{ a: string; b?: number }")).toEqual({
      kind: "object",
      fields: [{ name: "a", type: { kind: "string" }, optional: false }, { name: "b", type: { kind: "number" }, optional: true }],
    });
    expect(parseType("Map<K, V>").kind).toBe("unknown");
    expect(parseType("string | number").kind).toBe("unknown");
  });
});

/** generic records of the type grammar (the bundled Simple catalog has flat records only) */
const fixtureRecords: Record<string, RecordField[]> = {
  Stat: [
    { name: "id", type: "string" }, { name: "label", type: "string" }, { name: "value", type: "string" },
    { name: "icon", type: "string" }, { name: "hint", type: "string" },
  ],
  LinkActions: [
    { name: "mode", type: '"one" | "two"', optional: true, default: "one" }, { name: "cta", type: "string" },
    { name: "href", type: "string", optional: true },
  ],
  AboutMeLabels: [{ name: "level", type: "string" }],
};

describe("fields", () => {
  const records = fixtureRecords;
  test("a list of records is an array field with the record's fields", () => {
    const f = fieldOf("stats", parseType("Stat[]"), records) as Extract<Field, { type: "array" }>;
    expect(f.type).toBe("array");
    expect(Object.keys(f.arrayFields)).toEqual(["id", "label", "value", "icon", "hint"]);
    expect(f.arrayFields.label!.type).toBe("text");
    const item = f.defaultItem() as Record<string, unknown>;
    expect(Object.keys(item)).toEqual(["id", "label", "value", "icon", "hint"]);
    expect(String(item.id)).toMatch(/^item-/);
    expect(f.summary({ id: "s", label: "Uptime" }, 0)).toBe("Uptime");
    expect(f.summary({}, 2)).toBe("Stat 3");
  });
  test("a record is an object field; short enums are radios, long ones selects", () => {
    const f = fieldOf("actions", parseType("LinkActions"), records) as Extract<Field, { type: "object" }>;
    expect(f.type).toBe("object");
    expect(f.objectFields.mode).toMatchObject({ type: "radio", options: [{ value: "one" }, { value: "two" }] });
    expect(fieldOf("x", parseType('"a" | "b" | "c" | "d"'), records).type).toBe("select");
    expect(fieldOf("ratio", parseType('"4/3" | "1/1"'), records)).toMatchObject({ options: [{ label: "4/3" }, { label: "1/1" }] });
  });
  test("text or textarea by name and sample length", () => {
    expect(fieldOf("title", { kind: "string" }, records).type).toBe("text");
    expect(fieldOf("lead", { kind: "string" }, records).type).toBe("textarea");
    expect(fieldOf("title", { kind: "string" }, records, "x".repeat(80)).type).toBe("textarea");
  });
  test("optional record fields stay out of a new item; defaults apply", () => {
    const item = emptyOf({ kind: "record", name: "LinkActions" }, records) as Record<string, unknown>;
    expect(item).toEqual({ cta: "" });
    expect(summaryOf("plain")).toBe("plain");
    expect(summaryOf([])).toBeUndefined();
  });
  test("unknown record names fall back to the JSON field", () => {
    expect(fieldOf("x", parseType("NoSuchRecord"), records).type).toBe("json");
  });
});

describe("sample keys without a declared type", () => {
  const records = fixtureRecords;
  test("a sample value has a type: scalars, objects by keys, lists by the union of their items", () => {
    expect(typeOfSample("x")).toEqual({ kind: "string" });
    expect(typeOfSample(2)).toEqual({ kind: "number" });
    expect(typeOfSample([{ a: "x" }, { b: 1 }])).toEqual({
      kind: "array", of: { kind: "object", fields: [{ name: "a", type: { kind: "string" }, optional: false }, { name: "b", type: { kind: "number" }, optional: false }] },
    });
    expect(mergeSamples([{ a: "1", o: { x: "x" } }, { a: "2", b: true, o: { y: "y" } }])).toEqual({ a: "1", o: { x: "x", y: "y" }, b: true });
  });
  test("a record field list missing a sample key still gets a field for it", () => {
    const f = fieldOf("labels", parseType("AboutMeLabels"), records, { level: "{0}%", title: "Hi", lead: "x".repeat(80) }) as Extract<Field, { type: "object" }>;
    expect(Object.keys(f.objectFields)).toEqual(["level", "title", "lead"]);
    expect(f.objectFields.title!.type).toBe("text");
    expect(f.objectFields.lead!.type).toBe("textarea");
  });
  test("a sample prop the block does not declare gets a field", () => {
    const block = { id: "x", title: "X", docs: "", group: "g", axes: {}, defaults: {}, props: [], data: [],
      variants: [{ id: "a", set: {}, organism: "X", template: "blocks/x.latte", props: { nav: { links: [{ label: "A" }] }, badgeMark: "N" }, bindings: [] }] } as BlockSpec;
    const cfg = blockConfig(block, records);
    expect(cfg.fields.nav?.type).toBe("object");
    expect((cfg.fields.nav as Extract<Field, { type: "object" }>).objectFields.links?.type).toBe("array");
    expect(cfg.fields.badgeMark?.type).toBe("text");
  });
});

describe("bundled catalogs (UI8Kit Simple, block-contract/v1)", () => {
  for (const runtime of ["latte", "twig"]) {
    test(`${runtime}: every record a prop names is in catalog.records; no prop falls back to raw JSON`, () => {
      const { blocks, records } = catalog(runtime);
      const json: string[] = [];
      const walk = (path: string, f: Field) => {
        if (f.type === "json") json.push(path);
        if (f.type === "object") for (const [k, x] of Object.entries(f.objectFields)) walk(`${path}.${k}`, x);
        if (f.type === "array") walk(`${path}[]`, f.item);
      };
      for (const b of blocks) for (const [k, f] of Object.entries(blockConfig(b, records).fields)) walk(`${b.id}.${k}`, f);
      expect(json).toEqual([]);
      expect(blocks.length).toBe(52);
      expect(blocks.every((b) => b.contract?.version === 1 && b.data.length === 0)).toBe(true);
    });
    test(`${runtime}: content model fields and settings are fields; the axes are the variant's`, () => {
      const { blocks, records } = catalog(runtime);
      const hero = blocks.find((b) => b.id === "hero-simple")!;
      const cfg = blockConfig(hero, records);
      for (const k of ["badge", "title", "description", "image", "imageAlt", "actionLabel", "actionHref", "padding", "align", "level"]) expect(cfg.fields[k]).toBeDefined();
      for (const k of Object.keys(hero.axes)) expect(cfg.fields[k]).toBeUndefined();
      expect(cfg.fields.padding).toMatchObject({ type: "radio" });
      expect(hero.variants.find((v) => v.id === "mediaEnd")?.label).toBe("Picture at the end");
    });
    test(`${runtime}: a list of the content model is an array of its record`, () => {
      const { blocks, records } = catalog(runtime);
      const stats = blockConfig(blocks.find((b) => b.id === "stats-simple")!, records).fields.items as Extract<Field, { type: "array" }>;
      expect(stats.type).toBe("array");
      expect(Object.keys(stats.arrayFields)).toEqual(["id", "value", "title", "description"]);
    });
  }
});
