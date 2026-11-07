// NON-LIVE: ephemeral preview selection, not browser acceptance or a model turn.
import { expect, test } from "bun:test";
import { readFileSync } from "node:fs";
import { PreviewAppearance, previewChoices } from "../src/preview-appearance";
import { previewSource, PreviewReload } from "../src/preview";

test("follow is default, follows both global transitions and resets on a new mount", () => {
  const state = new PreviewAppearance();
  expect(state.mode).toBe("interface");
  expect(state.scheme(false)).toBe("light");
  expect(state.scheme(true)).toBe("dark");
  expect(state.scheme(false)).toBe("light");
  expect(new PreviewAppearance().mode).toBe("interface");
});
test("temporary override is independent of global mode and returning to follow uses current global", () => {
  for (const mode of ["light", "dark"] as const) {
    const state = new PreviewAppearance(mode);
    expect(state.scheme(false)).toBe(mode);
    expect(state.scheme(true)).toBe(mode);
    expect(new PreviewAppearance().scheme(true)).toBe("dark");
    expect(new PreviewAppearance().scheme(false)).toBe("light");
  }
  expect(() => new PreviewAppearance("injected" as never)).toThrow();
  expect(previewChoices.map(c => c.label)).toEqual(["Как в интерфейсе", "Light", "Dark"]);
});
test("new state has no persistence or global theme dependency", () => {
  const source = readFileSync(new URL("../src/preview-appearance.ts", import.meta.url), "utf8");
  for (const name of ["localStorage", "sessionStorage", "cookie", "window", "document", "fetch", "ThemeMode"]) {
    expect(source).not.toContain(name);
  }
  const first = new PreviewAppearance("dark");
  const second = new PreviewAppearance();
  expect(first.mode).toBe("dark");
  expect(second.mode).toBe("interface");
});
test("mode navigation retains nonce, leaves public URL untouched and rejects old-scheme load identity", async () => {
  const publicUrl = "https://example.test/?page_id=13";
  expect(() => previewSource(publicUrl, 3, 1, "dark")).toThrow();
  const preview = publicUrl + "&wpfb_preview=1&_wpnonce=fake";
  const light = previewSource(preview, 3, 1, new PreviewAppearance("light").scheme(true));
  const dark = previewSource(preview, 3, 1, new PreviewAppearance().scheme(true));
  expect(publicUrl).toBe("https://example.test/?page_id=13");
  expect(new URL(dark).searchParams.get("_wpnonce")).toBe("fake");
  const reload = new PreviewReload();
  let done = false;
  const result = reload.start(dark).then(() => {done = true;});
  reload.loaded(light, true);
  expect(done).toBe(false);
  reload.loaded(dark, true);
  await result;
  expect(done).toBe(true);
});
test("App uses existing navbar source; override is local and guarded during Apply", () => {
  const app = readFileSync(new URL("../src/App.svelte", import.meta.url), "utf8");
  expect(app).toContain("const theme = new ThemeMode(config.user)");
  expect(app).toContain("let appearance = $state.raw(new PreviewAppearance())");
  expect(app).toContain("const previewScheme = $derived(appearance.scheme(theme.dark))");
  expect(app).toContain("if (!applying) appearance = new PreviewAppearance(mode)");
  expect(app).toContain("previewSource(previewUrl, draftVersion, reloadKey, previewScheme)");
  expect(app).toContain('href={config.permalink}');
  expect(app).toContain('sandbox="allow-same-origin"');
  expect(app).not.toContain('sandbox="allow-scripts');
  expect(app).not.toContain("localStorage.setItem");
  expect(app).not.toContain("sessionStorage");
});
