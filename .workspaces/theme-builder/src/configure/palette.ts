// Dark scheme for a brand whose manifest gives only `light` tokens. Pure (Bun + the browser form), like contrast.ts.
// The dark palette keeps the light one's character: every surface and text token keeps its hue (and, within limits,
// its chroma), the primary keeps hue and chroma and only moves its lightness; then every text/surface pair of
// contrast.ts PAIRS is pushed to WCAG AA by moving the text token's lightness away from its surface.
import { AA, contrast, parseColor, PAIRS } from "./contrast";

type LCH = [number, number, number];

/** linear sRGB → OKLCH (L 0..1, C, H degrees) */
function toOklch([r, g, b]: [number, number, number]): LCH {
  const l = Math.cbrt(0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b);
  const m = Math.cbrt(0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b);
  const s = Math.cbrt(0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b);
  const L = 0.2104542553 * l + 0.793617785 * m - 0.0040720468 * s;
  const A = 1.9779984951 * l - 2.428592205 * m + 0.4505937099 * s;
  const B = 0.0259040371 * l + 0.7827717662 * m - 0.808675766 * s;
  const C = Math.hypot(A, B);
  const H = C < 1e-4 ? 0 : ((Math.atan2(B, A) * 180) / Math.PI + 360) % 360;
  return [L, C, H];
}

const r = (x: number, d: number) => String(Math.round(x * 10 ** d) / 10 ** d);
const fmt = ([l, c, h]: LCH) => (c < 0.0005 ? `oklch(${r(l, 3)} 0 0)` : `oklch(${r(l, 3)} ${r(c, 3)} ${r(h, 1)})`);
const lch = (v: string | undefined, fallback: LCH): LCH => {
  if (!v) return fallback;
  try { return toOklch(parseColor(v)); } catch { return fallback; }
};

/** A dark palette with the same keys as `light`, derived from it; every PAIRS pair present reaches AA. */
export function deriveDark(light: Record<string, string>): Record<string, string> {
  const bg = lch(light.background, [1, 0, 0]);
  const pr = lch(light.primary, [0.205, 0, 0]);
  const hue = bg[2];
  const tint = (c: number, max: number) => Math.min(c, max);
  const surface = (k: string, l: number, max = 0.02): LCH => { const t = lch(light[k], bg); return [l, tint(t[1], max), t[1] < 1e-4 ? hue : t[2]]; };
  const text = (k: string, l: number, max = 0.03): LCH => { const t = lch(light[k], [0, 0, hue]); return [l, tint(t[1], max), t[2]]; };
  const primary: LCH = [Math.max(pr[0], 0.72), pr[1], pr[2]];

  const out: Record<string, LCH> = {
    background: [0.16, tint(bg[1], 0.012), hue],
    foreground: text("foreground", 0.96, 0.01),
    card: [0.2, tint(bg[1], 0.014), hue],
    "card-foreground": text("card-foreground", 0.96, 0.01),
    popover: [0.2, tint(bg[1], 0.014), hue],
    "popover-foreground": text("popover-foreground", 0.96, 0.01),
    primary,
    "primary-foreground": [0.18, tint(pr[1], 0.04), pr[2]],
    secondary: surface("secondary", 0.27, 0.03),
    "secondary-foreground": text("secondary-foreground", 0.94),
    muted: surface("muted", 0.25),
    "muted-foreground": text("muted-foreground", 0.72, 0.02),
    accent: surface("accent", 0.3, 0.04),
    "accent-foreground": text("accent-foreground", 0.96),
    destructive: ((d: LCH): LCH => [Math.max(d[0], 0.68), Math.min(d[1], 0.2), d[2]])(lch(light.destructive, [0.58, 0.22, 27])),
    "destructive-foreground": [0.16, 0.02, lch(light.destructive, [0.58, 0.22, 27])[2]],
    border: [0.32, tint(bg[1], 0.014), hue],
    input: [0.36, tint(bg[1], 0.014), hue],
    ring: primary,
  };
  // text tokens: lighten (on dark surfaces) or darken (on light ones) until the pair reaches AA
  for (const [fg, bgKey] of PAIRS) {
    const f = out[fg], s = out[bgKey];
    if (!f || !s) continue;
    const up = f[0] >= s[0];
    while (contrast(fmt(f), fmt(s)) < AA + 0.05 && f[0] > 0.02 && f[0] < 0.995) f[0] += up ? 0.01 : -0.01;
  }
  const dark: Record<string, string> = {};
  for (const k of Object.keys(light)) if (out[k]) dark[k] = fmt(out[k]);
  return dark;
}

/** The dark tokens a brand ships: its own `dark`, or the one derived from `light`. */
export function brandDark(brand: { light?: Record<string, string>; dark?: Record<string, string> } | undefined): Record<string, string> | undefined {
  if (!brand?.light) return brand?.dark;
  return brand.dark ?? deriveDark(brand.light);
}
