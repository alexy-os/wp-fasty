/**
 * Tall preview images of the template pages of the bundled ui8kit catalog (collections of type template, 62 pages):
 * a light and a dark WebP per page, shown by the editor in the side panel that opens on hover / focus of a "Template
 * pages" button. Sister of previews.ts (same renderer, skin, dark mode and encoder): each page is the blocks the editor
 * puts on a page when the template page is used (php tools/builder/preview-html.php --pages), shot by a headless
 * Chromium at 1280 px wide over its whole height (cut at MAX_HEIGHT css px) and scaled to half: 640 px wide, at most
 * 2400 px tall. The WebP quality is searched to stay ≤ LIMIT bytes.
 *
 *   plugins/wpfasty-builder/sources/ui8kit/page-previews/<collection>/<page>.webp        light
 *   plugins/wpfasty-builder/sources/ui8kit/page-previews/<collection>/<page>.dark.webp   dark
 *   plugins/wpfasty-builder/sources/ui8kit/page-previews/manifest.json  {file, bytes, quality, input, width, height,
 *                                                                        dark: {…}} per "<collection>/<page>"
 *
 * Like the variant cards, the shots are not byte-reproducible and stay outside the digested source tree; the manifest
 * records the sha256 of what each one was rendered from, so a run only reshoots pages whose input changed.
 *
 *   bun tools/builder/page-previews.ts            shoot new and changed pages (PLAYWRIGHT_CORE, CHROME as previews.ts)
 *   bun tools/builder/page-previews.ts --force    reshoot every page
 *   bun tools/builder/page-previews.ts --only commerce-shop[/home]
 *   bun tools/builder/page-previews.ts --check    CI: every template page of both runtimes' catalogs has a light and
 *                                                 a dark preview, listed, of the listed size, ≤ LIMIT, WebP; no stray files
 */
import { createHash } from "node:crypto";
import { execFileSync } from "node:child_process";
import { existsSync, mkdirSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";

const ROOT = resolve(import.meta.dir, "../..");
const PLUGIN = join(ROOT, "plugins/wpfasty-builder");
const DIR = join(PLUGIN, "sources/ui8kit/page-previews");
const MANIFEST = join(DIR, "manifest.json");
const CSS = [join(ROOT, "themes/latte/assets/css/tokens.css"), join(ROOT, "themes/latte/assets/css/theme.min.css"), join(PLUGIN, "assets/blocks.css")];
/** desktop viewport; the page is shot over its whole height up to MAX_HEIGHT and scaled by SCALE */
const VIEW = { width: 1280, height: 800 };
const MAX_HEIGHT = 4800;
const SCALE = 0.5;
/** bytes per image: the largest quality that stays under */
const LIMIT = 40 * 1024;
const Q = { start: 0.6, low: 0.15, high: 0.9 };

interface Shot { file: string; bytes: number; quality: number; input: string; width: number; height: number }
interface Entry extends Shot { dark?: Shot }
type Mode = "light" | "dark";
const MODES: Mode[] = ["light", "dark"];
interface Manifest { format: 1; width: number; maxHeight: number; viewport: { width: number; maxHeight: number }; limit: number; pages: Record<string, Entry> }

const argv = process.argv.slice(2);
const flag = (n: string) => argv.includes(n);
const opt = (n: string) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : undefined; };

/** "<collection>/<page>" of every template page, with the page's blocks (to compare the runtimes) */
function pagesOf(runtime: "latte" | "twig"): Record<string, string> {
  const c = JSON.parse(readFileSync(join(PLUGIN, "sources/ui8kit", runtime, "catalog.json"), "utf8"));
  const out: Record<string, string> = {};
  for (const col of c.collections) for (const p of col.pages ?? []) out[`${col.id}/${p.id}`] = JSON.stringify(p.blocks);
  return out;
}
const fileOf = (key: string, mode: Mode) => (mode === "dark" ? `${key}.dark.webp` : `${key}.webp`);
const shotOf = (e: Entry, mode: Mode): Shot | undefined => (mode === "dark" ? e.dark : e);
const kb = (n: number) => (n / 1024).toFixed(1);
function stats(bytes: number[]): string {
  const s = [...bytes].sort((a, b) => a - b);
  return `${kb(s[0]!)}–${kb(s.at(-1)!)} KB, median ${kb(s[Math.floor(s.length / 2)]!)} KB, ${(s.reduce((a, b) => a + b, 0) / 1024 / 1024).toFixed(2)} MB`;
}
const load = (): Manifest | null => (existsSync(MANIFEST) ? JSON.parse(readFileSync(MANIFEST, "utf8")) : null);
function files(dir: string, rel = ""): string[] {
  if (!existsSync(join(dir, rel))) return [];
  return readdirSync(join(dir, rel)).sort().flatMap((n) => {
    const r = rel ? `${rel}/${n}` : n;
    return statSync(join(dir, r)).isDirectory() ? files(dir, r) : [r];
  });
}

if (flag("--check")) {
  const m = load();
  const bad: string[] = [];
  if (!m) bad.push("no page-previews/manifest.json");
  const latte = pagesOf("latte");
  const want = Object.keys(latte);
  if (JSON.stringify(latte) !== JSON.stringify(pagesOf("twig"))) bad.push("latte and twig catalogs list different template pages");
  const listed = new Set<string>();
  for (const key of want) {
    const e = m?.pages[key];
    if (!e) { bad.push(`${key}: no preview`); continue; }
    for (const mode of MODES) {
      const s = shotOf(e, mode);
      if (!s) { bad.push(`${key}: no ${mode} preview`); continue; }
      if (s.file !== fileOf(key, mode)) bad.push(`${key}: ${mode} preview ${s.file}, expected ${fileOf(key, mode)}`);
      listed.add(s.file);
      const p = join(DIR, s.file);
      if (!existsSync(p)) { bad.push(`${key}: ${s.file} missing`); continue; }
      const size = statSync(p).size;
      if (size !== s.bytes) bad.push(`${key}: ${s.file} ${size} B on disk, ${s.bytes} B in the manifest`);
      if (size < 1 || size > (m?.limit ?? LIMIT)) bad.push(`${key}: ${s.file} ${size} B, over the ${(m?.limit ?? LIMIT) / 1024} KB limit`);
      if (s.width !== (m?.width ?? 0) || s.height < 1 || s.height > (m?.maxHeight ?? 0)) bad.push(`${key}: ${s.file} ${s.width}×${s.height}`);
      const head = readFileSync(p).subarray(0, 12).toString("latin1");
      if (!head.startsWith("RIFF") || head.slice(8) !== "WEBP") bad.push(`${key}: ${s.file} not a WebP file`);
    }
  }
  for (const key of Object.keys(m?.pages ?? {})) if (!want.includes(key)) bad.push(`${key}: listed, not a template page`);
  for (const f of files(DIR)) if (f !== "manifest.json" && !listed.has(f)) bad.push(`${f}: not listed in the manifest`);
  if (bad.length) {
    console.error(`✗ template page previews (${bad.length}):\n  ${bad.slice(0, 40).join("\n  ")}`);
    process.exit(1);
  }
  const hs = want.map((k) => m!.pages[k]!.height).sort((a, b) => a - b);
  console.log(`✓ template page previews: ${want.length}/${want.length} pages × light + dark, ${m!.width} px wide, ${hs[0]}–${hs.at(-1)} px tall (median ${hs[Math.floor(hs.length / 2)]}); ${MODES.map((mode) => `${mode} ${stats(want.map((k) => shotOf(m!.pages[k]!, mode)!.bytes))}`).join("; ")}`);
  process.exit(0);
}

// ---- shooting ------------------------------------------------------------------------------------------------------
const html: Record<string, string> = JSON.parse(execFileSync("php", [join(ROOT, "tools/builder/preview-html.php"), "--pages"], { maxBuffer: 256 << 20 }).toString());
const css = CSS.map((f) => readFileSync(f, "utf8")).join("\n");
const cssHash = createHash("sha256").update(css).digest("hex");
const prev = load();
const OUT_W = Math.round(VIEW.width * SCALE), OUT_MAX = Math.round(MAX_HEIGHT * SCALE);
const manifest: Manifest = { format: 1, width: OUT_W, maxHeight: OUT_MAX, viewport: { width: VIEW.width, maxHeight: MAX_HEIGHT }, limit: LIMIT, pages: {} };
const only = opt("--only");
const keys = Object.keys(html);
const inputOf = (k: string, mode: Mode) => "sha256:" + createHash("sha256").update(`${VIEW.width}x${MAX_HEIGHT}*${SCALE}\0${cssHash}\0${html[k]}${mode === "dark" ? "\0dark" : ""}`).digest("hex");
const stale = (k: string, mode: Mode) => {
  const e = prev?.pages[k];
  const s = e ? shotOf(e, mode) : undefined;
  return flag("--force") || !s || s.input !== inputOf(k, mode) || !existsSync(join(DIR, s.file));
};
const todo = keys.flatMap((k) => (only && k !== only && !k.startsWith(only + "/") ? [] : MODES.filter((mode) => stale(k, mode)).map((mode) => ({ k, mode }))));
for (const k of keys) {
  const e = prev?.pages[k];
  if (!e) continue;
  const keep = (mode: Mode) => !todo.some((t) => t.k === k && t.mode === mode);
  const { dark, ...light } = e;
  if (keep("light")) manifest.pages[k] = { ...light, ...(dark && keep("dark") ? { dark } : {}) };
  else if (dark && keep("dark")) manifest.pages[k] = { file: "", bytes: 0, quality: 0, input: "", width: 0, height: 0, dark };
}

const pw = await import(process.env.PLAYWRIGHT_CORE ?? "playwright-core");
const browser = await pw.chromium.launch({ executablePath: process.env.CHROME ?? "/usr/bin/google-chrome", args: ["--no-sandbox", "--font-render-hinting=none"] });
const page = await browser.newPage({ viewport: VIEW, deviceScaleFactor: 1, colorScheme: "light", reducedMotion: "reduce" });
const enc = await browser.newPage();
await enc.setContent("<!doctype html><canvas></canvas>");
const shell = (body: string, mode: Mode) => `<!doctype html><html lang="en"${mode === "dark" ? ' class="dark"' : ""}><head><meta charset="utf-8"><style>${css}</style><style>
  html { color-scheme: ${mode}; }
  html, body { margin: 0; background: var(--background); }
  .wpfb-page { background: var(--background); color: var(--foreground); }
  .wpfb-page *, .wpfb-page *::before, .wpfb-page *::after { animation: none !important; transition: none !important; caret-color: transparent; }
</style></head><body><div class="wpfb-page">${body}</div></body></html>`;

let n = 0;
for (const { k, mode } of todo) {
  await page.emulateMedia({ colorScheme: mode });
  await page.setContent(shell(html[k]!, mode), { waitUntil: "load" });
  await page.evaluate(() => document.fonts.ready);
  const full = await page.evaluate(() => Math.ceil(document.querySelector(".wpfb-page")!.getBoundingClientRect().height));
  const h = Math.min(full, MAX_HEIGHT);
  const png: Buffer = await page.screenshot({ clip: { x: 0, y: 0, width: VIEW.width, height: h }, type: "png", fullPage: true });
  const size = { width: OUT_W, height: Math.round(h * SCALE) };
  const out: { q: number; b64: string } = await enc.evaluate(async ({ b64, size, max, q }) => {
    const img = await createImageBitmap(await (await fetch(`data:image/png;base64,${b64}`)).blob(), { resizeWidth: size.width, resizeHeight: size.height, resizeQuality: "high" });
    const c = document.querySelector("canvas")!;
    c.width = size.width; c.height = size.height;
    c.getContext("2d")!.drawImage(img, 0, 0);
    const at = async (x: number) => new Promise<Blob>((r) => c.toBlob((b) => r(b!), "image/webp", x));
    let best = q.start, blob = await at(best);
    if (blob.size > max) {
      let lo = q.low, hi = q.start, pick: { x: number; b: Blob } | null = null;
      for (let i = 0; i < 7; i++) {
        const x = (lo + hi) / 2, b = await at(x);
        if (b.size <= max) { pick = { x, b }; lo = x; } else hi = x;
      }
      if (!pick) pick = { x: q.low, b: await at(q.low) };
      best = pick.x; blob = pick.b;
    }
    const buf = new Uint8Array(await blob.arrayBuffer());
    let s = ""; for (const x of buf) s += String.fromCharCode(x);
    return { q: Math.round(best * 1000) / 1000, b64: btoa(s) };
  }, { b64: png.toString("base64"), size, max: LIMIT, q: Q });
  const file = fileOf(k, mode);
  const bytes = Buffer.from(out.b64, "base64");
  mkdirSync(dirname(join(DIR, file)), { recursive: true });
  writeFileSync(join(DIR, file), bytes);
  const shot: Shot = { file, bytes: bytes.length, quality: out.q, input: inputOf(k, mode), ...size };
  const e = manifest.pages[k];
  if (mode === "dark") manifest.pages[k] = { ...(e ?? { file: "", bytes: 0, quality: 0, input: "", width: 0, height: 0 }), dark: shot };
  else manifest.pages[k] = { ...shot, ...(e?.dark ? { dark: e.dark } : {}) };
  if (++n % 20 === 0) console.log(`${n}/${todo.length}`);
}
await browser.close();

const kept = new Set(Object.values(manifest.pages).flatMap((e) => [e.file, e.dark?.file]).filter(Boolean));
for (const f of files(DIR)) if (f !== "manifest.json" && !kept.has(f)) rmSync(join(DIR, f));
const ordered: Manifest = { ...manifest, pages: Object.fromEntries(keys.filter((k) => manifest.pages[k]).map((k) => [k, manifest.pages[k]!])) };
mkdirSync(DIR, { recursive: true });
writeFileSync(MANIFEST, JSON.stringify(ordered, null, 1) + "\n");
const all = Object.values(ordered.pages);
console.log(`${todo.length} shots taken; ${MODES.map((mode) => `${mode} ${stats(all.flatMap((e) => { const s = shotOf(e, mode); return s?.bytes ? [s.bytes] : []; }))}`).join("; ")}`);
