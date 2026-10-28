import { describe, expect, test } from "bun:test";
import { readFileSync } from "node:fs";
import { join } from "node:path";
import type { BlockSpec, RecordField } from "../src/types";
import { blockConfig, emptyOf, fieldOf, mergeSamples, parseType, summaryOf, typeOfSample, type Field } from "../src/fields/schema";
import { missingBlocks } from "../src/util";

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

describe("fields", () => {
  const records = catalog("latte").records;
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
  const records = catalog("latte").records;
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
  test("a sample prop the block does not declare gets a field (hero prompt: composer, nav, badge mark)", () => {
    const hero = catalog("latte").blocks.find((b) => b.id === "hero")!;
    const cfg = blockConfig(hero, records);
    expect(cfg.fields.composer?.type).toBe("object");
    expect(cfg.fields.nav?.type).toBe("object");
    expect((cfg.fields.nav as Extract<Field, { type: "object" }>).objectFields.links?.type).toBe("array");
    expect(cfg.fields.badgeMark?.type).toBe("text");
  });
  test("blocks of a layout outside the loaded specs", () => {
    expect(missingBlocks([{ block: "a" }, { block: "cta" }, { block: "cta" }], [{ id: "a" }])).toEqual(["cta"]);
  });
});

describe("bundled catalogs", () => {
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
      expect(blocks.length).toBe(93);
    });
    test(`${runtime}: richtext — the fields the current page fills (title, content, cover, meta, side note body) are not editable`, () => {
      const { blocks, records } = catalog(runtime);
      const rt = blocks.find((b) => b.id === "richtext")!;
      const cfg = blockConfig(rt, records);
      for (const k of ["title", "content", "media", "meta"]) expect(cfg.fields[k]).toBeUndefined();
      const aside = cfg.fields.aside as Extract<Field, { type: "object" }>;
      expect(Object.keys(aside.objectFields)).toEqual(["title"]);
      expect(cfg.fields.level?.type).toBeDefined();
      for (const v of rt.variants) expect(v.current?.map((c) => c.path.join("."))).toContain("content");
    });
    test(`${runtime}: posts — the pager the adapter fills (page, pages, texts) is not editable, its mode is`, () => {
      const { blocks, records } = catalog(runtime);
      const posts = blocks.find((b) => b.id === "posts")!;
      expect(posts.variants.map((v) => v.id)).toContain("listPaged");
      const pager = blockConfig(posts, records).fields.pager as Extract<Field, { type: "object" }>;
      expect(Object.keys(pager.objectFields)).toEqual(["mode"]);
      expect(records.PostPager?.map((f) => f.name)).toEqual(["mode", "page", "pages", "label", "summary", "previous", "next", "pageLabel"]);
    });
  }
});
