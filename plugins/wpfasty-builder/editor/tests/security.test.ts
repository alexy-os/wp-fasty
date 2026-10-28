// Editor security gates: the preview iframe is sandboxed without scripts, and no {@html} prints user data.
import { describe, expect, test } from "bun:test";
import { readdirSync, readFileSync } from "node:fs";
import { join } from "node:path";

const src = join(import.meta.dir, "..", "src");
const files = (dir: string): string[] =>
  readdirSync(dir, { withFileTypes: true }).flatMap((d) => (d.isDirectory() ? files(join(dir, d.name)) : [join(dir, d.name)]));
const svelte = files(src).filter((f) => f.endsWith(".svelte"));
const code = files(src).filter((f) => /\.(svelte|ts)$/.test(f));

describe("preview iframe", () => {
  const app = readFileSync(join(src, "App.svelte"), "utf8");
  const frames = [...app.matchAll(/<iframe\b[^>]*>/g)].map((m) => m[0]);
  test("every iframe is sandboxed with allow-same-origin only (no allow-scripts)", () => {
    expect(frames.length).toBeGreaterThan(0);
    for (const f of frames) {
      const sb = /\ssandbox="([^"]*)"/.exec(f);
      expect(sb).not.toBeNull();
      const tokens = (sb?.[1] ?? "").split(/\s+/).filter(Boolean);
      expect(tokens).toEqual(["allow-same-origin"]);
    }
  });
  test("no other file creates an iframe", () => {
    for (const f of code) {
      if (f.endsWith("App.svelte")) continue;
      expect(readFileSync(f, "utf8")).not.toMatch(/<iframe\b|createElement\(["']iframe/);
    }
  });
});

describe("no {@html} with user data", () => {
  // the only allowed {@html}: Icon.svelte prints Lucide node data (static, values escaped)
  const allowed = new Map([["Icon.svelte", "{@html inner}"]]);
  test("{@html} only where listed", () => {
    for (const f of svelte) {
      const name = f.slice(src.length + 1);
      const uses = [...readFileSync(f, "utf8").matchAll(/\{@html\s[^}]*\}/g)].map((m) => m[0]);
      for (const u of uses) expect(allowed.get(name)).toBe(u);
    }
  });
  test("no postMessage / message listener without an origin check", () => {
    for (const f of code) {
      const s = readFileSync(f, "utf8");
      if (/addEventListener\(\s*["']message["']/.test(s)) expect(s).toMatch(/\.origin\s*!==?\s*/);
    }
  });
  test("no eval / new Function / document.write", () => {
    for (const f of code) expect(readFileSync(f, "utf8")).not.toMatch(/\beval\(|new Function\(|document\.write\(/);
  });
});
