// bun run tokens:contrast — light + dark tokens of every theme (themes/*/assets/css/tokens.css):
//  1. a theme that has :root colour tokens has a .dark block with the same colour keys (fonts / radius are not
//     per-scheme and stay in :root);
//  2. every text/surface pair (contrast.ts PAIRS) of the palette a page really gets — the child's tokens over the
//     parent's, per scheme — reaches WCAG AA 4.5:1, and so does primary on background (links, text buttons).
// The parent (latte) is the shadcn default; its own pairs are reported, a child's palette fails the run.
import { existsSync, readdirSync, readFileSync } from "node:fs";
import { join, resolve } from "node:path";
import { AA, contrast, PAIRS } from "../../.workspaces/theme-builder/src/configure/contrast";

const root = resolve(import.meta.dir, "../..");
const NOT_COLOUR = new Set(["radius", "font-body", "font-display"]);
type Vars = Record<string, string>;
const block = (css: string, sel: RegExp): Vars | undefined => {
  const m = css.match(sel);
  if (!m) return undefined;
  const out: Vars = {};
  for (const d of m[1].matchAll(/--([a-z0-9-]+)\s*:\s*([^;]+);/g)) if (!NOT_COLOUR.has(d[1])) out[d[1]] = d[2].trim();
  return out;
};
const tokens = (theme: string) => {
  const css = readFileSync(join(root, "themes", theme, "assets/css/tokens.css"), "utf8").replace(/\/\*[\s\S]*?\*\//g, "");
  return { light: block(css, /:root\s*\{([^}]*)\}/) ?? {}, dark: block(css, /\.dark\s*\{([^}]*)\}/) };
};
const parentOf = (theme: string) => readFileSync(join(root, "themes", theme, "style.css"), "utf8").match(/^Template:\s*(\S+)/m)?.[1];
const pairs: [string, string][] = [...PAIRS, ["primary", "background"]];

let fail = 0;
const themes = readdirSync(join(root, "themes")).filter((t) => existsSync(join(root, "themes", t, "assets/css/tokens.css"))).sort();
for (const t of themes) {
  const own = tokens(t);
  const parent = parentOf(t);
  const base = parent ? tokens(parent) : { light: {}, dark: {} };
  const lines: string[] = [];
  let bad = 0;
  const lk = Object.keys(own.light).sort().join(","), dk = Object.keys(own.dark ?? {}).sort().join(",");
  if (lk && lk !== dk) { bad++; lines.push(`  ✗ .dark keys [${dk}] ≠ :root colour keys [${lk}]`); }
  for (const [scheme, pal] of [["light", { ...base.light, ...own.light }], ["dark", { ...base.dark, ...own.dark }]] as const) {
    const got: string[] = [];
    for (const [fg, bg] of pairs) {
      if (!pal[fg] || !pal[bg]) continue;
      const r = contrast(pal[fg], pal[bg]);
      const ok = r >= AA;
      // a parent pair a child does not touch is the parent's, not the child's
      const childs = !!parent && (fg in own.light || bg in own.light || fg in (own.dark ?? {}) || bg in (own.dark ?? {}));
      if (!ok && childs) bad++;
      got.push(`${ok ? "" : childs ? "✗ " : "! "}${fg}/${bg} ${r.toFixed(2)}`);
    }
    lines.push(`  ${scheme}: ${got.join(", ")}`);
  }
  fail += bad;
  console.log(`${bad ? "✗" : "✓"} ${t}${parent ? ` (over ${parent})` : " (parent)"}\n${lines.join("\n")}`);
}
console.log(fail ? `${fail} problem(s)` : `all child palettes: light + dark, same keys, text pairs ≥ ${AA}:1`);
process.exitCode = fail ? 1 : 0;
