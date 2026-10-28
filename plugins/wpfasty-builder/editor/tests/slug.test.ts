import { describe, expect, test } from "bun:test";
import { SLUG_MAX, slugOf, slugify } from "../src/slug";

describe("new page slug", () => {
  test("made from the title like sanitize_title", () => {
    expect(slugify("Summer Sale 2026")).toBe("summer-sale-2026");
    expect(slugify("  Hello,   World!  ")).toBe("hello-world");
    expect(slugify("Café & Crème brûlée")).toBe("cafe-creme-brulee");
    expect(slugify("Don't panic")).toBe("dont-panic");
    expect(slugify("a -- b__c")).toBe("a-b-c");
    expect(slugify("!!!")).toBe("");
    expect(slugify("")).toBe("");
  });

  test("letters of any script stay", () => {
    expect(slugify("О нас")).toBe("о-нас");
    expect(slugify("Йога в Ёлках")).toBe("йога-в-ёлках");
  });

  test("at most 200 characters, no trailing dash", () => {
    const s = slugify("word ".repeat(80));
    expect(s.length).toBeLessThanOrEqual(SLUG_MAX);
    expect(s.endsWith("-")).toBe(false);
  });

  test("follows the title until the field is edited", () => {
    expect(slugOf("New Landing", "", false)).toBe("new-landing");
    expect(slugOf("New Landing", "custom", true)).toBe("custom");
    expect(slugOf("New Landing", "", true)).toBe("");
  });
});
