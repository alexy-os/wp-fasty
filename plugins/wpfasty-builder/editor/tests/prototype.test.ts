// Live block prototypes of the cards (ADR-007): a card of a block-contract/v1 block frames the authenticated preview
// URL of the page with the block's static prototype sheet; the variant is the fragment, the scheme request-local.
import { describe, expect, test } from "bun:test";
import { readFileSync } from "node:fs";
import { join } from "node:path";
import { cardsOf } from "../src/cards";
import { prototypeSource } from "../src/prototype";
import type { BlockSpec } from "../src/types";

const base = "http://site.test/home/?wpfb_preview=1&_wpnonce=abc123";
const catalog = JSON.parse(readFileSync(join(import.meta.dir, "../../sources/ui8kit/latte/catalog.json"), "utf8")) as { blocks: BlockSpec[] };

describe("prototype cards", () => {
  test("every Simple block is a live prototype card; its variants keep their contract labels", () => {
    const cards = cardsOf(catalog.blocks);
    expect(cards.length).toBe(52);
    expect(cards.every((c) => c.prototype === true)).toBe(true);
    expect(cards.find((c) => c.key === "hero-simple")?.variants.find((v) => v.id === "mediaEnd")?.label).toBe("Picture at the end");
  });
  test("the sheet URL keeps the preview nonce, adds the block and the scheme; the variant is the fragment", () => {
    const u = new URL(prototypeSource(base, "hero-simple", "mediaEnd", true));
    expect(u.searchParams.get("_wpnonce")).toBe("abc123");
    expect(u.searchParams.get("wpfb_preview")).toBe("1");
    expect(u.searchParams.get("wpfb_prototypes")).toBe("hero-simple");
    expect(u.searchParams.get("wpfb_scheme")).toBe("dark");
    expect(u.hash).toBe("#hero-simple--mediaEnd");
    expect(new URL(prototypeSource(base, "hero-simple", "mediaEnd", false)).searchParams.get("wpfb_scheme")).toBe("light");
  });
  test("no URL without a preview, or for ids outside the contract grammar", () => {
    expect(prototypeSource("", "hero-simple", "mediaEnd", false)).toBe("");
    expect(prototypeSource(base, "../x", "mediaEnd", false)).toBe("");
    expect(prototypeSource(base, "hero-simple", "a\"b", false)).toBe("");
    expect(() => prototypeSource("http://site.test/home/", "hero-simple", "mediaEnd", false)).toThrow();
  });
});
