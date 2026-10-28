import { describe, expect, test } from "bun:test";
import { darkKey, parseDark, previewSrc } from "../src/theme";
import { cardsOf } from "../src/cards";
import type { BlockSpec } from "../src/types";

describe("dark mode", () => {
  test("remembered per user, light unless stored as dark", () => {
    expect(darkKey(7)).toBe("wpfasty-builder:dark:7");
    expect(darkKey(0)).toBe("wpfasty-builder:dark");
    expect(darkKey(undefined)).toBe("wpfasty-builder:dark");
    expect(parseDark("1")).toBe(true);
    for (const raw of [null, "0", "", "true", "dark"]) expect(parseDark(raw)).toBe(false);
  });

  test("cards take the dark shot in dark mode, the light one otherwise or when there is no dark shot", () => {
    const both = { src: "/p/hero/split.webp", dark: "/p/hero/split.dark.webp" };
    expect(previewSrc(both, false)).toBe(both.src);
    expect(previewSrc(both, true)).toBe(both.dark);
    expect(previewSrc({ src: "/p/a/b.webp" }, true)).toBe("/p/a/b.webp");
  });

  test("the catalog's dark URL reaches the card", () => {
    const preview = { src: "/l.webp", dark: "/d.webp", width: 320, height: 180 };
    const b = { id: "hero", title: "Hero", group: "marketing", variants: [{ id: "split", preview }] } as unknown as BlockSpec;
    expect(cardsOf([b])[0]!.preview).toEqual(preview);
  });
});
