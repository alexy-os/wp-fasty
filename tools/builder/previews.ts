/**
 * Preview images of the bundled ui8kit catalog: one WebP card per variant (474 = every variant of every block), shown
 * by the editor's block list. Rendered on Latte exactly as POST /render-block returns a variant (sample props:
 * tools/builder/preview-html.php), styled like a site of the default skin (themes/latte tokens.css + theme.min.css,
 * light theme) plus the plugin's assets/blocks.css, shot by a
 * headless Chromium at 1280×720 and scaled to 640×360, a 320×180 card at 2×. One 16:9 frame for every card, so the
 * grid rows line up: 720px holds the whole block for about 80% of the variants (median height 488px); a taller block is
 * cut at 720px of its top, where its heading and first row are; a shorter one is centred on the page background.
 *
 *   plugins/wpfasty-builder/sources/ui8kit/previews/<block>/<variant>.webp
 *   plugins/wpfasty-builder/sources/ui8kit/previews/manifest.json   {file, bytes, quality, input} per "<block>/<variant>"
 *
 * The shots are not byte-reproducible (encoder, fonts), so they stay outside the ui8kit source tree that
 * export-ui8kit.ts reproduces and digests (latte/, twig/, styles/); the manifest lists them and records the sha256 of
 * what each one was rendered from (fragment HTML + blocks.css), so a run only reshoots variants whose input changed.
 *
 *   bun tools/builder/previews.ts            shoot new and changed variants (needs playwright-core + Chrome:
 *                                            PLAYWRIGHT_CORE=<path to playwright-core> CHROME=/usr/bin/google-chrome)
 *   bun tools/builder/previews.ts --force    reshoot every variant
 *   bun tools/builder/previews.ts --only hero[/split]
 *   bun tools/builder/previews.ts --check    CI: every variant of both runtimes' catalogs has a preview listed in the
 *                                            manifest, the file has the listed size, 1 B … 10 KB; no stray files
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

interface Entry { file: string; bytes: number; quality: number; input: string }
interface Manifest { format: 1; width: number; height: number; viewport: typeof VIEW; limit: number; variants: Record<string, Entry> }

const argv = process.argv.slice(2);
const flag = (n: string) => argv.includes(n);
const opt = (n: string) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : undefined; };

function variantsOf(runtime: "latte" | "twig"): string[] {
  const c = JSON.parse(readFileSync(join(PLUGIN, "sources/ui8kit", runtime, "catalog.json"), "utf8"));
  return c.blocks.flatMap((b: { id: string; variants: { id: string }[] }) => b.variants.map((v) => `${b.id}/${v.id}`));
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
    listed.add(e.file);
    const p = join(DIR, e.file);
    if (!existsSync(p)) { bad.push(`${key}: ${e.file} missing`); continue; }
    const size = statSync(p).size;
    if (size !== e.bytes) bad.push(`${key}: ${size} B on disk, ${e.bytes} B in the manifest`);
    if (size < 1 || size > (m?.limit ?? MAX)) bad.push(`${key}: ${size} B, over the ${(m?.limit ?? MAX) / 1024} KB limit`);
    const head = readFileSync(p).subarray(0, 12).toString("latin1");
    if (!head.startsWith("RIFF") || head.slice(8) !== "WEBP") bad.push(`${key}: not a WebP file`);
  }
  for (const key of Object.keys(m?.variants ?? {})) if (!want.includes(key)) bad.push(`${key}: listed, not in the catalog`);
  for (const f of files(DIR)) if (f !== "manifest.json" && !listed.has(f)) bad.push(`${f}: not listed in the manifest`);
  if (bad.length) {
    console.error(`✗ block previews (${bad.length}):\n  ${bad.slice(0, 40).join("\n  ")}`);
    process.exit(1);
  }
  const sizes = want.map((k) => m!.variants[k]!.bytes).sort((a, b) => a - b);
  const kb = (n: number) => (n / 1024).toFixed(1);
  console.log(`✓ block previews: ${want.length}/${want.length} variants, ${kb(sizes[0]!)}–${kb(sizes.at(-1)!)} KB (median ${kb(sizes[Math.floor(sizes.length / 2)]!)} KB), ${(sizes.reduce((a, b) => a + b, 0) / 1024 / 1024).toFixed(2)} MB`);
  process.exit(0);
}

// ---- shooting ------------------------------------------------------------------------------------------------------
const html: Record<string, string> = JSON.parse(execFileSync("php", [join(ROOT, "tools/builder/preview-html.php")], { maxBuffer: 256 << 20 }).toString());
const css = CSS.map((f) => readFileSync(f, "utf8")).join("\n");
const cssHash = createHash("sha256").update(css).digest("hex");
const prev = load();
const manifest: Manifest = { format: 1, width: CARD.width, height: CARD.height, viewport: VIEW, limit: MAX, variants: {} };
const only = opt("--only");
const keys = variantsOf("latte");
const inputOf = (k: string) => "sha256:" + createHash("sha256").update(`${VIEW.width}x${VIEW.height}>${CARD.width}x${CARD.height}\0${cssHash}\0${html[k]}`).digest("hex");
const todo = keys.filter((k) => {
  if (only && k !== only && !k.startsWith(only + "/")) return false;
  const e = prev?.variants[k];
  return flag("--force") || !e || e.input !== inputOf(k) || !existsSync(join(DIR, e.file));
});
for (const k of keys) if (prev?.variants[k] && !todo.includes(k)) manifest.variants[k] = prev.variants[k]!;

const pw = await import(process.env.PLAYWRIGHT_CORE ?? "playwright-core");
const browser = await pw.chromium.launch({ executablePath: process.env.CHROME ?? "/usr/bin/google-chrome", args: ["--no-sandbox", "--font-render-hinting=none"] });
const page = await browser.newPage({ viewport: VIEW, deviceScaleFactor: 1, colorScheme: "light", reducedMotion: "reduce" });
const enc = await browser.newPage();
await enc.setContent("<!doctype html><canvas></canvas>");
const shell = (body: string) => `<!doctype html><html lang="en"><head><meta charset="utf-8"><style>${css}</style><style>
  html, body { margin: 0; background: var(--background, #fff); }
  .wpfb-page { min-height: ${VIEW.height}px; display: flex; flex-direction: column; justify-content: center; background: var(--background, #fff); color: var(--foreground, #000); }
  .wpfb-page *, .wpfb-page *::before, .wpfb-page *::after { animation: none !important; transition: none !important; caret-color: transparent; }
</style></head><body><div class="wpfb-page">${body}</div></body></html>`;

let n = 0;
for (const k of todo) {
  await page.setContent(shell(html[k]!), { waitUntil: "load" });
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
  const file = `${k}.webp`;
  const bytes = Buffer.from(out.b64, "base64");
  mkdirSync(dirname(join(DIR, file)), { recursive: true });
  writeFileSync(join(DIR, file), bytes);
  manifest.variants[k] = { file, bytes: bytes.length, quality: out.q, input: inputOf(k) };
  if (++n % 50 === 0) console.log(`${n}/${todo.length}`);
}
await browser.close();

// variants gone from the catalog: drop their files
for (const f of files(DIR)) if (f !== "manifest.json" && !Object.values(manifest.variants).some((e) => e.file === f)) rmSync(join(DIR, f));
const ordered: Manifest = { ...manifest, variants: Object.fromEntries(keys.filter((k) => manifest.variants[k]).map((k) => [k, manifest.variants[k]!])) };
writeFileSync(MANIFEST, JSON.stringify(ordered, null, 1) + "\n");
const sizes = Object.values(ordered.variants).map((e) => e.bytes).sort((a, b) => a - b);
console.log(`${todo.length} shot, ${keys.length - todo.length} unchanged; ${sizes.length} previews ${(sizes[0]! / 1024).toFixed(1)}–${(sizes.at(-1)! / 1024).toFixed(1)} KB, median ${(sizes[Math.floor(sizes.length / 2)]! / 1024).toFixed(1)} KB, ${(sizes.reduce((a, b) => a + b, 0) / 1024).toFixed(0)} KB total`);
