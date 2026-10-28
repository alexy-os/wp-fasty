// The editor chrome follows dark mode: floating canvas controls sit on surface tokens (never the inverted text colour,
// which turns light in dark mode), and every scrolling area of the editor carries the touchpad scrollbar class.
import { describe, expect, test } from "bun:test";
import { readFileSync } from "node:fs";
import { join } from "node:path";

const src = join(import.meta.dir, "..", "src");
const read = (f: string) => readFileSync(join(src, f), "utf8");
const rule = (css: string, selector: string) => css.match(new RegExp(`^${selector.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")} \\{([^}]*)\\}`, "m"))?.[1] ?? "";

describe("canvas block toolbar", () => {
  const css = read("editor.css");
  test("is a popover on the surface tokens", () => {
    expect(rule(css, ".wpfb-toolbar")).toContain("background: var(--wpfb-surface)");
    expect(rule(css, ".wpfb-toolbar")).toContain("color: var(--wpfb-text)");
    expect(rule(css, ".wpfb-tool")).toContain("color: inherit");
  });
  test("no floating canvas control paints the text colour as its background", () => {
    for (const sel of [".wpfb-toolbar", ".wpfb-tool", ".wpfb-tag", ".wpfb-dropline", ".wpfb-unfocus"]) {
      expect(rule(css, sel)).not.toMatch(/background: var\(--wpfb-text\)/);
    }
  });
});

describe("scrollbars", () => {
  test("every scrolling area of the editor has .wpfb-scroll", () => {
    const css = read("editor.css");
    const markup = ["App.svelte", "BlockCards.svelte", "NewPage.svelte", "TemplatePages.svelte", "fields/LinkPicker.svelte"].map(read).join("\n");
    const scrollers = [...css.matchAll(/^([^{\n]+)\{[^}]*overflow(?:-[xy])?: (?:auto|scroll)/gm)]
      .flatMap((m) => m[1]!.split(",").map((s) => s.trim()))
      .map((s) => s.replace(/^\./, ""));
    expect(scrollers.length).toBeGreaterThan(4);
    for (const cls of scrollers) expect(markup).toMatch(new RegExp(`class="${cls}\\b[^"]*\\bwpfb-scroll\\b`));
  });
  test("the scrollbar class is Tailwind @apply on tokens only", () => {
    const css = read("scrollbar.css");
    expect(css).toContain("@reference \"tailwindcss\"");
    expect(css).not.toMatch(/@import\s+"tailwindcss"/);
    expect(css).not.toMatch(/#[0-9a-f]{3,8}\b|rgb\(|hsl\(|oklch\(/i);
  });
});
