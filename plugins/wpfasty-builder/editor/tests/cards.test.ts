import { describe, expect, test } from "bun:test";
import { readFileSync } from "node:fs";
import { join } from "node:path";
import { cardsOf, columnsOf, filterCards, groupsOf, pick, selectedOf } from "../src/cards";
import type { BlockSpec } from "../src/types";

const block = (id: string, title: string, group: string, axes: Record<string, string[]>, variants: [string, Record<string, string>, string?][]) => ({
  id, title, group, docs: "", axes, defaults: {}, props: [], data: [], contract: { item: id, version: 1, model: `${id}@1` },
  variants: variants.map(([v, set, label]) => ({ id: v, set, ...(label ? { label } : {}), organism: "", template: "", props: {} })),
}) as BlockSpec;
const hero = block("hero", "Hero", "hero", { media: ["end", "start", "top"], ratio: ["landscape", "portrait"] }, [
  ["mediaEnd", { media: "end", ratio: "landscape" }, "Picture at the end"], ["mediaStart", { media: "start", ratio: "landscape" }],
  ["mediaTop", { media: "top", ratio: "landscape" }], ["portraitEnd", { media: "end", ratio: "portrait" }],
]);
const intro = block("intro", "Intro", "hero", { place: ["middle", "bottom"] }, [["a", { place: "middle" }], ["b", { place: "middle" }], ["c", { place: "bottom" }]]);
const faq = block("faq", "FAQ", "faq", {}, [["list", {}]]);
const catalog = JSON.parse(readFileSync(join(import.meta.dir, "../../sources/ui8kit/latte/catalog.json"), "utf8")) as { blocks: BlockSpec[] };

describe("cards: one per block with a variant switcher", () => {
  test("one card per block, in catalog order; axes that tell the variants apart, values in the block's order", () => {
    const [h, i, f] = cardsOf([hero, intro, faq]);
    expect([h!.key, i!.key, f!.key]).toEqual(["hero", "intro", "faq"]);
    expect(h!.axes.map((a) => [a.name, a.values.map((v) => v.value)])).toEqual([["media", ["end", "start", "top"]], ["ratio", ["landscape", "portrait"]]]);
    expect(h!.variants[0]).toEqual({ id: "mediaEnd", label: "Picture at the end", set: { media: "end", ratio: "landscape" } });
    // two variants share the same axis values: the labelled variants are the switcher
    expect(i!.axes).toEqual([]);
    expect(f!.variants.length).toBe(1);
  });
  test("an axis choice keeps the other axis values when a variant has them, else the first match", () => {
    const [h] = cardsOf([hero]);
    expect(pick(h!, "mediaEnd", "media", "start")).toBe("mediaStart");
    expect(pick(h!, "mediaEnd", "ratio", "portrait")).toBe("portraitEnd");
    expect(pick(h!, "portraitEnd", "media", "top")).toBe("mediaTop");
    expect(pick(h!, "mediaEnd", "media", "nowhere")).toBe("mediaEnd");
    expect(selectedOf(h!, undefined).id).toBe("mediaEnd");
    expect(selectedOf(h!, "gone").id).toBe("mediaEnd");
  });
  test("search (every word, variant labels too) and category; categories count blocks", () => {
    const cards = cardsOf([hero, intro, faq]);
    expect(filterCards(cards, "picture end", "").map((c) => c.key)).toEqual(["hero"]);
    expect(filterCards(cards, "", "faq").map((c) => c.key)).toEqual(["faq"]);
    expect(filterCards(cards, "list", "hero")).toEqual([]);
    expect(groupsOf([hero, intro, faq])).toEqual([{ id: "faq", label: "Faq", count: 1 }, { id: "hero", label: "Hero", count: 2 }]);
  });
  test("every Simple block is one live prototype card; every variant is reachable from its switcher", () => {
    const cards = cardsOf(catalog.blocks);
    expect(cards.length).toBe(52);
    expect(cards.reduce((n, c) => n + c.variants.length, 0)).toBe(157);
    for (const c of cards) {
      expect(c.prototype).toBe(true);
      if (!c.axes.length) continue;
      const reached = new Set<string>([c.variants[0]!.id]);
      for (let round = 0; round < 4; round++)
        for (const from of [...reached]) for (const a of c.axes) for (const x of a.values) reached.add(pick(c, from, a.name, x.value));
      expect([...reached].sort()).toEqual(c.variants.map((v) => v.id).sort());
    }
  });
  test("columns by width", () => {
    expect(columnsOf(264)).toEqual({ cols: 1, cardWidth: 264 });
    expect(columnsOf(416)).toEqual({ cols: 2, cardWidth: 204 });
    expect(columnsOf(100).cols).toBe(1);
  });
});
