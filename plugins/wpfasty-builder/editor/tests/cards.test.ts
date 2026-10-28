import { describe, expect, test } from "bun:test";
import { cardsOf, filterCards, gridOf, groupsOf, windowOf } from "../src/cards";
import type { BlockSpec } from "../src/types";

const block = (id: string, title: string, group: string, variants: string[], preview = true) => ({
  id, title, group, docs: "", axes: {}, defaults: {}, props: [], data: [],
  variants: variants.map((v) => ({ id: v, set: {}, organism: "", template: "", props: {}, bindings: [],
    ...(preview ? { preview: { src: `p/${id}/${v}.webp`, width: 320, height: 180 } } : {}) })),
}) as BlockSpec;
const blocks = [block("hero", "Hero", "hero", ["split", "centered"]), block("faq", "FAQ", "faq", ["list"]), block("cta", "Call to action", "cta", ["banner"], false)];

describe("cards", () => {
  test("one card per variant, in catalog order, with its preview", () => {
    const cards = cardsOf(blocks);
    expect(cards.map((c) => c.key)).toEqual(["hero/split", "hero/centered", "faq/list", "cta/banner"]);
    expect(cards[0]).toEqual({ key: "hero/split", block: "hero", variant: "split", title: "Hero", label: "Split", group: "hero", preview: { src: "p/hero/split.webp", width: 320, height: 180 } });
    expect(cards[3]!.preview).toBeUndefined();
  });
  test("search (every word) and category", () => {
    const cards = cardsOf(blocks);
    expect(filterCards(cards, "hero cent", "").map((c) => c.key)).toEqual(["hero/centered"]);
    expect(filterCards(cards, "", "faq").map((c) => c.key)).toEqual(["faq/list"]);
    expect(filterCards(cards, "ACTION", "").map((c) => c.key)).toEqual(["cta/banner"]);
    expect(filterCards(cards, "split", "faq")).toEqual([]);
  });
  test("categories labelled like the catalog's group collections", () => {
    expect(groupsOf(blocks, [{ id: "cta", label: "Calls to action", type: "group" }, { id: "hero", label: "Template", type: "template" }]))
      .toEqual([{ id: "cta", label: "Calls to action", count: 1 }, { id: "faq", label: "Faq", count: 1 }, { id: "hero", label: "Hero", count: 2 }]);
  });
});

describe("virtual grid", () => {
  test("columns by width, 16:9 images", () => {
    expect(gridOf(264, 474)).toMatchObject({ cols: 1, cardWidth: 264, imageHeight: 149, rowHeight: 205, rows: 474 });
    expect(gridOf(416, 10)).toMatchObject({ cols: 2, cardWidth: 204, rows: 5 });
    expect(gridOf(100, 3).cols).toBe(1);
  });
  test("only the rows in view (+ overscan) are rendered", () => {
    const g = gridOf(264, 474);
    expect(windowOf(g, 474, 0, 600)).toEqual({ from: 0, to: 5, offset: 0 });
    const w = windowOf(g, 474, 205 * 100, 600);
    expect(w).toEqual({ from: 98, to: 105, offset: 98 * 205 });
    expect(windowOf(g, 474, 1e9, 600).to).toBe(474);
    expect(windowOf(g, 0, 0, 600)).toEqual({ from: 0, to: 0, offset: 0 });
    const g2 = gridOf(416, 474);
    expect(windowOf(g2, 474, 0, 600).to - windowOf(g2, 474, 0, 600).from).toBeLessThanOrEqual(2 * 8);
  });
});
