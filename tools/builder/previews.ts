/**
 * Preview images of the bundled ui8kit catalog: two WebP cards per variant (474 = every variant of every block), a
 * light one and a dark one (class "dark" on the root: the shadcn tokens of tokens.css :root / .dark — colours only through
 * tokens), shown by the editor's block list in its light or dark mode. Rendered on Latte exactly as POST /render-block returns a variant (sample props:
 * tools/builder/preview-html.php), styled like a site of the default skin (themes/latte tokens.css + theme.min.css,
 * light and dark theme) plus the plugin's assets/blocks.css, shot by a
 * headless Chromium at 1280×720 and scaled to 640×360, a 320×180 card at 2×. One 16:9 frame for every card, so the
 * grid rows line up: 720px holds the whole block for about 80% of the variants (median height 488px); a taller block is
 * cut at 720px of its top, where its heading and first row are; a shorter one is centred on the page background.
 *
 *   plugins/wpfasty-builder/sources/ui8kit/previews/<block>/<variant>.webp        light
 *   plugins/wpfasty-builder/sources/ui8kit/previews/<block>/<variant>.dark.webp   dark
 *   plugins/wpfasty-builder/sources/ui8kit/previews/manifest.json   {file, bytes, quality, input, dark: {file, bytes,
 *                                                                    quality, input}} per "<block>/<variant>"
 *
 * The shots are not byte-reproducible (encoder, fonts), so they stay outside the ui8kit source tree that
 * export-ui8kit.ts reproduces and digests (latte/, twig/, styles/); the manifest lists them and records the sha256 of
 * what each one was rendered from (fragment HTML + blocks.css), so a run only reshoots variants whose input changed.
 *
 *   bun tools/builder/previews.ts            shoot new and changed variants (needs playwright-core + Chrome:
 *                                            PLAYWRIGHT_CORE=<path to playwright-core> CHROME=/usr/bin/google-chrome)
 *   bun tools/builder/previews.ts --force    reshoot every variant
 *   bun tools/builder/previews.ts --only hero[/split]
 *   bun tools/builder/previews.ts --check    CI: every variant of both runtimes' catalogs has a light and a dark preview
 *                                            listed in the manifest, each file has the listed size, 1 B … 10 KB; no stray
 *                                            files
 */
import { createHash } from "node:crypto";
import { execFileSync } from "node:child_process";
import { existsSync, mkdirSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";

const ROOT = resolve(import.meta.dir, "../..");
const PLUGIN = join(ROOT, "plugins/wpfasty-builder");
const DIR = join(PLUGIN, "sources/ui8kit/previews");
const MANIFEST = join(DIR, "manifest.json");
/** the default skin (themes/latte: tokens, light theme, base styles) under the plugin's block styles, as on a site */
const CSS = [join(ROOT, "themes/latte/assets/css/tokens.css"), join(ROOT, "themes/latte/assets/css/theme.min.css"), join(PLUGIN, "assets/blocks.css")];
const VIEW = { width: 1280, height: 720 };
/** the file: 2× the 320×180 card it fills (sharp on high-density screens) */
const CARD = { width: 640, height: 360 };
/** bytes per card: the encoder quality is searched to land inside; a flat card stays under MIN at the top lossy quality */
const MIN = 6 * 1024, MAX = 10 * 1024;
/** lossy only: 1.0 switches Chrome to lossless (3–6× larger) */
const Q = { start: 0.7, low: 0.2, high: 0.99 };

interface Shot { file: string; bytes: number; quality: number; input: string }
/** the light shot, and the dark one under `dark` */
interface Entry extends Shot { dark?: Shot }
type Mode = "light" | "dark";
const MODES: Mode[] = ["light", "dark"];
interface Manifest { format: 2; width: number; height: number; viewport: typeof VIEW; limit: number; variants: Record<string, Entry> }

const argv = process.argv.slice(2);
const flag = (n: string) => argv.includes(n);
const opt = (n: string) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : undefined; };

function variantsOf(runtime: "latte" | "twig"): string[] {
  const c = JSON.parse(readFileSync(join(PLUGIN, "sources/ui8kit", runtime, "catalog.json"), "utf8"));
  return c.blocks.flatMap((b: { id: string; variants: { id: string }[] }) => b.variants.map((v) => `${b.id}/${v.id}`));
}
/** the file of a variant's shot: <block>/<variant>.webp (light), <block>/<variant>.dark.webp */
const fileOf = (key: string, mode: Mode) => (mode === "dark" ? `${key}.dark.webp` : `${key}.webp`);
const shotOf = (e: Entry, mode: Mode): Shot | undefined => (mode === "dark" ? e.dark : e);
const kb = (n: number) => (n / 1024).toFixed(1);
/** "min–max KB, median … KB, total … MB" of a list of sizes */
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
  if (!m) bad.push("no previews/manifest.json");
  const want = variantsOf("latte");
  const twig = variantsOf("twig");
  if (want.join() !== twig.join()) bad.push("latte and twig catalogs list different variants");
  const listed = new Set<string>();
  for (const key of want) {
    const e = m?.variants[key];
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
      if (size < 1 || size > (m?.limit ?? MAX)) bad.push(`${key}: ${s.file} ${size} B, over the ${(m?.limit ?? MAX) / 1024} KB limit`);
      const head = readFileSync(p).subarray(0, 12).toString("latin1");
      if (!head.startsWith("RIFF") || head.slice(8) !== "WEBP") bad.push(`${key}: ${s.file} not a WebP file`);
    }
  }
  for (const key of Object.keys(m?.variants ?? {})) if (!want.includes(key)) bad.push(`${key}: listed, not in the catalog`);
  for (const f of files(DIR)) if (f !== "manifest.json" && !listed.has(f)) bad.push(`${f}: not listed in the manifest`);
  if (bad.length) {
    console.error(`✗ block previews (${bad.length}):\n  ${bad.slice(0, 40).join("\n  ")}`);
    process.exit(1);
  }
  console.log(`✓ block previews: ${want.length}/${want.length} variants × light + dark; ${MODES.map((mode) => `${mode} ${stats(want.map((k) => shotOf(m!.variants[k]!, mode)!.bytes))}`).join("; ")}`);
  process.exit(0);
}

// ---- shooting ------------------------------------------------------------------------------------------------------
const html: Record<string, string> = JSON.parse(execFileSync("php", [join(ROOT, "tools/builder/preview-html.php")], { maxBuffer: 256 << 20 }).toString());
const css = CSS.map((f) => readFileSync(f, "utf8")).join("\n");
const cssHash = createHash("sha256").update(css).digest("hex");
const prev = load();
const manifest: Manifest = { format: 2, width: CARD.width, height: CARD.height, viewport: VIEW, limit: MAX, variants: {} };
const only = opt("--only");
const keys = variantsOf("latte");
/** what a shot is rendered from; the light hash is the one of format 1 (light shots made before dark mode stay valid) */
const inputOf = (k: string, mode: Mode) => "sha256:" + createHash("sha256").update(`${VIEW.width}x${VIEW.height}>${CARD.width}x${CARD.height}\0${cssHash}\0${html[k]}${mode === "dark" ? "\0dark" : ""}`).digest("hex");
const stale = (k: string, mode: Mode) => {
  const e = prev?.variants[k];
  const s = e ? shotOf(e, mode) : undefined;
  return flag("--force") || !s || s.input !== inputOf(k, mode) || !existsSync(join(DIR, s.file));
};
const todo = keys.flatMap((k) => (only && k !== only && !k.startsWith(only + "/") ? [] : MODES.filter((mode) => stale(k, mode)).map((mode) => ({ k, mode }))));
for (const k of keys) {
  const e = prev?.variants[k];
  if (!e) continue;
  const keep = (mode: Mode) => !todo.some((t) => t.k === k && t.mode === mode);
  const light = keep("light") ? { file: e.file, bytes: e.bytes, quality: e.quality, input: e.input } : undefined;
  if (light) manifest.variants[k] = { ...light, ...(e.dark && keep("dark") ? { dark: e.dark } : {}) };
  else if (e.dark && keep("dark")) manifest.variants[k] = { file: "", bytes: 0, quality: 0, input: "", dark: e.dark };
}

const pw = await import(process.env.PLAYWRIGHT_CORE ?? "playwright-core");
const browser = await pw.chromium.launch({ executablePath: process.env.CHROME ?? "/usr/bin/google-chrome", args: ["--no-sandbox", "--font-render-hinting=none"] });
const page = await browser.newPage({ viewport: VIEW, deviceScaleFactor: 1, colorScheme: "light", reducedMotion: "reduce" });
const enc = await browser.newPage();
await enc.setContent("<!doctype html><canvas></canvas>");
/** the page of a shot: dark mode = class "dark" on the root (tokens.css .dark), colours only through the tokens */
const shell = (body: string, mode: Mode) => `<!doctype html><html lang="en"${mode === "dark" ? ' class="dark"' : ""}><head><meta charset="utf-8"><style>${css}</style><style>
  html { color-scheme: ${mode}; }
  html, body { margin: 0; background: var(--background); }
  .wpfb-page { min-height: ${VIEW.height}px; display: flex; flex-direction: column; justify-content: center; background: var(--background); color: var(--foreground); }
  .wpfb-page *, .wpfb-page *::before, .wpfb-page *::after { animation: none !important; transition: none !important; caret-color: transparent; }
</style></head><body><div class="wpfb-page">${body}</div></body></html>`;

let n = 0;
for (const { k, mode } of todo) {
  await page.emulateMedia({ colorScheme: mode });
  await page.setContent(shell(html[k]!, mode), { waitUntil: "load" });
  await page.evaluate(() => document.fonts.ready);
  const png: Buffer = await page.screenshot({ clip: { x: 0, y: 0, ...VIEW }, type: "png" });
  // scale with high-quality smoothing, then search the WebP quality that lands in MIN…MAX bytes
  const out: { q: number; b64: string } = await enc.evaluate(async ({ b64, card, min, max, q }) => {
    const img = await createImageBitmap(await (await fetch(`data:image/png;base64,${b64}`)).blob(), { resizeWidth: card.width, resizeHeight: card.height, resizeQuality: "high" });
    const c = document.querySelector("canvas")!;
    c.width = card.width; c.height = card.height;
    c.getContext("2d")!.drawImage(img, 0, 0);
    const at = async (x: number) => new Promise<Blob>((r) => c.toBlob((b) => r(b!), "image/webp", x));
    let lo = q.low, hi = q.high, best = q.start, blob = await at(best);
    if (blob.size > max || blob.size < min) {
      // bisection on quality: the largest quality that stays ≤ max (or the highest one when even it is < min)
      let pick: { x: number; b: Blob } | null = null;
      for (let i = 0; i < 8; i++) {
        const x = (lo + hi) / 2, b = await at(x);
        if (b.size <= max) { pick = { x, b }; lo = x; } else hi = x;
        if (b.size >= min && b.size <= max) break;
      }
      if (!pick) pick = { x: q.low, b: await at(q.low) };
      best = pick.x; blob = pick.b;
    }
    const buf = new Uint8Array(await blob.arrayBuffer());
    let s = ""; for (const x of buf) s += String.fromCharCode(x);
    return { q: Math.round(best * 1000) / 1000, b64: btoa(s) };
  }, { b64: png.toString("base64"), card: CARD, min: MIN, max: MAX, q: Q });
  const file = fileOf(k, mode);
  const bytes = Buffer.from(out.b64, "base64");
  mkdirSync(dirname(join(DIR, file)), { recursive: true });
  writeFileSync(join(DIR, file), bytes);
  const shot: Shot = { file, bytes: bytes.length, quality: out.q, input: inputOf(k, mode) };
  const e = manifest.variants[k];
  if (mode === "dark") manifest.variants[k] = { ...(e ?? { file: "", bytes: 0, quality: 0, input: "" }), dark: shot };
  else manifest.variants[k] = { ...shot, ...(e?.dark ? { dark: e.dark } : {}) };
  if (++n % 50 === 0) console.log(`${n}/${todo.length}`);
}
await browser.close();

// variants gone from the catalog: drop their files
const kept = new Set(Object.values(manifest.variants).flatMap((e) => [e.file, e.dark?.file]).filter(Boolean));
for (const f of files(DIR)) if (f !== "manifest.json" && !kept.has(f)) rmSync(join(DIR, f));
const ordered: Manifest = { ...manifest, variants: Object.fromEntries(keys.filter((k) => manifest.variants[k]).map((k) => [k, manifest.variants[k]!])) };
writeFileSync(MANIFEST, JSON.stringify(ordered, null, 1) + "\n");
const all = Object.values(ordered.variants);
const shots = all.reduce((n, e) => n + MODES.filter((mode) => shotOf(e, mode)?.bytes).length, 0);
console.log(`${todo.length} shots taken, ${shots - todo.length} unchanged; ${MODES.map((mode) => `${mode} ${stats(all.flatMap((e) => { const s = shotOf(e, mode); return s?.bytes ? [s.bytes] : []; }))}`).join("; ")}`);
