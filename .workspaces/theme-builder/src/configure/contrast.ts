// WCAG 2.x contrast for brand tokens (oklch / hsl / hex). Pure: runs in Bun and in the browser form.
type RGB = [number, number, number]; // linear sRGB 0..1

const clamp = (x: number) => Math.min(1, Math.max(0, x));
const toLinear = (c: number) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4);

function oklchToLinear(l: number, c: number, hDeg: number): RGB {
  const h = (hDeg * Math.PI) / 180;
  const a = c * Math.cos(h);
  const b = c * Math.sin(h);
  const l_ = (l + 0.3963377774 * a + 0.2158037573 * b) ** 3;
  const m_ = (l - 0.1055613458 * a - 0.0638541728 * b) ** 3;
  const s_ = (l - 0.0894841775 * a - 1.291485548 * b) ** 3;
  return [
    clamp(4.0767416621 * l_ - 3.3077115913 * m_ + 0.2309699292 * s_),
    clamp(-1.2684380046 * l_ + 2.6097574011 * m_ - 0.3413193965 * s_),
    clamp(-0.0041960863 * l_ - 0.7034186147 * m_ + 1.707614701 * s_),
  ];
}

function hslToLinear(h: number, s: number, l: number): RGB {
  const k = (n: number) => (n + h / 30) % 12;
  const a = s * Math.min(l, 1 - l);
  const f = (n: number) => l - a * Math.max(-1, Math.min(k(n) - 3, 9 - k(n), 1));
  return [toLinear(f(0)), toLinear(f(8)), toLinear(f(4))];
}

const num = (s: string, pct = 1) => (s.endsWith("%") ? parseFloat(s) / 100 : parseFloat(s) / pct);

/** Parses #rrggbb[aa], oklch(L C H [/ A]), hsl(H S% L% [/ A]); alpha is ignored (tokens checked are opaque). */
export function parseColor(input: string): RGB {
  const s = input.trim();
  let m = s.match(/^#([0-9a-f]{6})([0-9a-f]{2})?$/i);
  if (m) {
    const n = parseInt(m[1], 16);
    return [toLinear(((n >> 16) & 255) / 255), toLinear(((n >> 8) & 255) / 255), toLinear((n & 255) / 255)];
  }
  m = s.match(/^oklch\(\s*([\d.]+%?)\s+([\d.]+%?)\s+([\d.]+)(?:deg)?\s*(?:\/\s*[\d.]+%?)?\s*\)$/i);
  if (m) return oklchToLinear(num(m[1]), m[2].endsWith("%") ? (parseFloat(m[2]) / 100) * 0.4 : parseFloat(m[2]), parseFloat(m[3]));
  m = s.match(/^hsl\(\s*([\d.]+)(?:deg)?[\s,]+([\d.]+)%[\s,]+([\d.]+)%\s*(?:\/\s*[\d.]+%?)?\s*\)$/i);
  if (m) return hslToLinear(parseFloat(m[1]), parseFloat(m[2]) / 100, parseFloat(m[3]) / 100);
  throw new Error(`unsupported color "${input}"`);
}

export const luminance = ([r, g, b]: RGB) => 0.2126 * r + 0.7152 * g + 0.0722 * b;

export function contrast(fg: string, bg: string): number {
  const a = luminance(parseColor(fg));
  const b = luminance(parseColor(bg));
  return (Math.max(a, b) + 0.05) / (Math.min(a, b) + 0.05);
}

/** Text-on-surface token pairs that must reach WCAG AA for normal text (4.5:1). */
export const PAIRS: [string, string][] = [
  ["foreground", "background"],
  ["card-foreground", "card"],
  ["popover-foreground", "popover"],
  ["primary-foreground", "primary"],
  ["secondary-foreground", "secondary"],
  ["muted-foreground", "muted"],
  ["muted-foreground", "background"],
  ["accent-foreground", "accent"],
  ["destructive-foreground", "destructive"],
];
export const AA = 4.5;

export type ContrastIssue = { scheme: string; fg: string; bg: string; ratio: number };

/** Every present pair below AA, per scheme (light / dark). */
export function checkTokens(schemes: Record<string, Record<string, string> | undefined>): ContrastIssue[] {
  const issues: ContrastIssue[] = [];
  for (const [scheme, t] of Object.entries(schemes)) {
    if (!t) continue;
    for (const [fg, bg] of PAIRS) {
      if (!t[fg] || !t[bg]) continue;
      const ratio = contrast(t[fg], t[bg]);
      if (ratio < AA) issues.push({ scheme, fg, bg, ratio: Math.round(ratio * 100) / 100 });
    }
  }
  return issues;
}
