// NON-LIVE: orchestration/lifecycle contracts, not visual browser acceptance or a provider turn.
import { expect, test } from "bun:test";
import { readFileSync } from "node:fs";
import { applyAndRefresh, previewSource, PreviewReload, requireDraftVersion } from "../src/preview";

const url = "https://example.test/?page_id=13&wpfb_preview=1&_wpnonce=fake#hero";
test("real server preview URL retains nonce, page query and hash; revisions and reapply cache keys differ", () => {
  const a = new URL(previewSource(url, 1, 0));
  expect(a.searchParams.get("_wpnonce")).toBe("fake");
  expect(a.searchParams.get("page_id")).toBe("13");
  expect(a.hash).toBe("#hero");
  expect(a.searchParams.get("wpfb_r")).toBe("1-0");
  expect(previewSource(url, 2, 0)).not.toBe(a.href);
  expect(previewSource(url, 1, 1)).not.toBe(a.href);
  expect(new URL(previewSource(a.href, 2, 1)).searchParams.getAll("wpfb_r")).toEqual(["2-1"]);
  expect(previewSource("", 0, 0)).toBe("");
});
test("appearance is server-side, transient and part of navigation identity", () => {
  const dark = new URL(previewSource(url, 1, 0, "dark"));
  const light = new URL(previewSource(dark.href, 1, 0, "light"));
  expect(dark.searchParams.get("wpfb_scheme")).toBe("dark");
  expect(light.searchParams.getAll("wpfb_scheme")).toEqual(["light"]);
  expect(dark.searchParams.get("_wpnonce")).toBe("fake");
  expect(dark.href).not.toBe(light.href);
});
test("stale or malformed persisted revisions fail before preview refresh", () => {
  for (const [actual, expected] of [[0, 1], [1, NaN], [1, -1], ["1", 1], [1.5, 1]]) {
    expect(() => requireDraftVersion(actual, expected as number)).toThrow();
  }
  expect(() => requireDraftVersion(2, 1)).not.toThrow();
});
test("Apply waits for outstanding save, persisted layout and requested real iframe load in order", async () => {
  const order: string[] = [];
  const reload = new PreviewReload();
  const target = previewSource(url, 2, 1);
  let completed = false;
  const result = applyAndRefresh("saved-proposal", {
    flush: async () => { order.push("flush"); },
    apply: async id => { expect(id).toBe("saved-proposal"); order.push("apply-once"); return { version: 2 }; },
    load: async version => { expect(version).toBe(2); requireDraftVersion(2, version); order.push("layout"); },
    refresh: () => { order.push("iframe-navigation"); return reload.start(target); },
  }).then(() => { completed = true; });
  await new Promise(resolve => setTimeout(resolve, 0));
  expect(order).toEqual(["flush", "apply-once", "layout", "iframe-navigation"]);
  expect(completed).toBe(false);
  reload.loaded(previewSource(url, 1, 0), true); // Earlier iframe's load must not signal success.
  expect(completed).toBe(false);
  reload.loaded(target, true);
  await result;
  expect(completed).toBe(true);
});
test("save/apply/layout/iframe errors reject without retry or false success", async () => {
  for (const stage of ["flush", "apply", "load", "refresh"]) {
    let applies = 0; let refreshes = 0;
    const fail = (name: string) => { if (name === stage) throw new Error(stage); };
    await expect(applyAndRefresh("proposal", {
      flush: async () => fail("flush"),
      apply: async () => { applies++; fail("apply"); return { version: 1 }; },
      load: async () => fail("load"),
      refresh: async () => { refreshes++; fail("refresh"); },
    })).rejects.toThrow(stage);
    expect(applies).toBe(stage === "flush" ? 0 : 1);
    expect(refreshes).toBe(stage === "refresh" ? 1 : 0);
  }
});
test("HTTP error/login document, navigation error and timeout never count as rendered Builder", async () => {
  for (const kind of ["no-builder", "error", "timeout"]) {
    const reload = new PreviewReload(5);
    const result = reload.start(url);
    if (kind === "no-builder") reload.loaded(url, false);
    if (kind === "error") reload.fail();
    await expect(result).rejects.toThrow();
  }
});
test("a superseded navigation rejects old waiter and cannot satisfy newer target", async () => {
  const reload = new PreviewReload();
  const first = reload.start(url).catch(error => error);
  const next = previewSource(url, 2, 3);
  const second = reload.start(next);
  expect(await first).toBeInstanceOf(Error);
  reload.loaded(url, true);
  reload.loaded(next, true);
  await second;
});
test("App connects Apply, revision-aware src and iframe lifecycle; keeps sandbox and prevents stale patches", () => {
  const app = readFileSync(new URL("../src/App.svelte", import.meta.url), "utf8");
  expect(app).toContain("apply: applyExternal");
  expect(app).toContain('const frameSrc = $derived(previewSource(previewUrl, previewRevision, reloadKey, previewScheme))');
  expect(app).toContain("requireDraftVersion(Number(layout.layout?.version ?? 0), minimumVersion)");
  expect(app).toContain('previewSource(previewUrl, draftVersion, reloadKey, previewScheme)');
  expect(app).not.toContain('documentElement.classList.toggle(DARK_CLASS');
  expect(app).toContain('if (!applying) theme.toggle()');
  expect(app).toContain('toggle?.toggleAttribute("disabled", applying)');
  expect(app).toContain('toggle?.removeAttribute("disabled")');
  expect(app).toContain("previewReload.loaded(doc.URL, !!doc.querySelector(\".wpfb-page\"))");
  expect(app).toContain("generation !== previewGeneration");
  expect(app).toContain("await saveQueue");
  expect(app).toContain('sandbox="allow-same-origin"');
  expect(app).not.toContain('sandbox="allow-scripts');
  const api = readFileSync(new URL("../src/api.ts", import.meta.url), "utf8");
  expect(api).toContain('{ cache: "no-store" }');
});
