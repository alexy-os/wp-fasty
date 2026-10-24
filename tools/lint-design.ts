#!/usr/bin/env bun
/**
 * Design-policy lint for theme views/CSS (not UI8Kit primitives):
 *  - colors only via CSS variable tokens (no hex/rgb/hsl/oklch, no Tailwind palette colors)
 *  - security: raw array values must not be passed as primitive `children`
 *  - layout spacing on an 8px grid: odd spacing steps (1,3,5,7… = 4px multiples) are allowed
 *    only on control lines (button/input/select/label/badge/textarea/option).
 *  - the builder editor chrome (plugins/wpfasty-builder/editor/src): the same colour and grid rules on its CSS.
 */
import { Glob } from "bun";

const root = new URL("..", import.meta.url).pathname;
const palette = /\b(?:bg|text|border|ring|fill|stroke|from|to|via|outline|divide)-(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|black|white)(?:-\d{2,3})?\b/;
const rawColor = /#[0-9a-fA-F]{3,8}\b|\b(?:rgb|rgba|hsl|hsla|oklch)\(/;
const oddSpacing = /(?<![\w-])-?(?:p|px|py|pt|pb|pl|pr|m|mx|my|mt|mb|ml|mr|gap|gap-x|gap-y|space-x|space-y)-(?:1|3|5|7|9|11)(?![\w.\/])/;
const halfStep = /(?<![\w-])-?(?:p|px|py|pt|pb|pl|pr|m|mx|my|mt|mb|ml|mr|gap|gap-x|gap-y|space-x|space-y|w|h|size|top|left|right|bottom|inset)-\d+\.5(?![\w])/;
const arbitraryPx = /\b[a-z-]+-\[-?\d+(?:\.\d+)?px\]/;
const control = /<(?:button|input|select|textarea|label|option)\b|badge|Button\.latte|Input\.latte|Badge\.latte|control/i;

const errors: string[] = [];
for (const pattern of ["themes/*/views/**/*.latte", "themes/*/views/**/*.twig", "themes/*/assets/css/*.css", "themes/*/style.css"]) {
  for await (const rel of new Glob(pattern).scan(root)) {
    const isTokens = rel.endsWith("tokens.css") || rel.endsWith("theme.min.css") || rel.endsWith("assets/css/theme.css") || rel.endsWith("a11y.css");
    const text = await Bun.file(root + rel).text();
    text.split("\n").forEach((line, i) => {
      const where = `${rel}:${i + 1}`;
      if (!isTokens && rawColor.test(line) && !line.includes("var(--")) errors.push(`${where} raw color — use a token var(--…)`);
      if (palette.test(line)) errors.push(`${where} palette color class — use token classes (bg-primary, text-muted-foreground…)`);
      // UI8Kit primitives print `children` with |noescape — pass captured (escaped) HTML, never raw data.
      if (rel.endsWith(".latte") && /children:\s*\(string\)\s*\$\w+\[/.test(line)) errors.push(`${where} raw data passed as children (XSS) — wrap in {capture}`);
      // Latte checks URLs in href/src/action automatically; Twig views must do it explicitly.
      if (rel.endsWith(".twig") && /\b(?:href|src|action)="\{\{(?![^}]*\|safe_url)/.test(line)) errors.push(`${where} URL attribute without |safe_url`);
      if (rel.endsWith(".twig") && /children:\s*[\w]+(?:\.|\[)/.test(line)) errors.push(`${where} raw data passed as children (XSS) — wrap in {% set %}…{% endset %}`);
      if (/\.(latte|twig)$/.test(rel) && halfStep.test(line)) errors.push(`${where} half spacing step — use the 8px grid`);
      if (/\.(latte|twig)$/.test(rel) && arbitraryPx.test(line)) errors.push(`${where} arbitrary px value — use scale/tokens`);
      if (/\.(latte|twig)$/.test(rel) && oddSpacing.test(line) && !control.test(line)) errors.push(`${where} 4px step outside a control — use 8px grid (2,4,6,8…)`);
    });
  }
}
// Builder editor chrome (plugins/wpfasty-builder/editor/src): colours only via var(--wpfb-*) tokens (tokens.css holds
// the raw values), spacing on the 8px grid; 4px steps only in control rules (buttons, inputs, labels, chips, toolbars).
const editorSpacing = /(?:^|[;{\s])(padding|margin|gap|row-gap|column-gap|inset|top|right|bottom|left)(?:-[a-z]+)?\s*:\s*([^;}]+)/g;
const editorControl = /(button|input|select|textarea|label|legend|option|seg|icon|handle|reset|variant|ghost|check|field|toolbar|tool|item-head|item-title|map-row|drop|chip|badge|tab|summary)/i;
for (const pattern of ["plugins/wpfasty-builder/editor/src/**/*.css", "plugins/wpfasty-builder/editor/src/**/*.svelte"]) {
  for await (const rel of new Glob(pattern).scan(root)) {
    if (rel.endsWith("/tokens.css")) continue;
    let text = await Bun.file(root + rel).text();
    if (rel.endsWith(".svelte")) text = (text.match(/<style[^>]*>[\s\S]*?<\/style>/g) ?? []).join("\n");
    text.split("\n").forEach((line, i) => {
      const where = `${rel}:${i + 1}`;
      if (rawColor.test(line.replace(/var\(--[^)]*\)/g, ""))) errors.push(`${where} raw color — use a var(--wpfb-*) token (editor/src/tokens.css)`);
      const selector = line.includes("{") ? line.slice(0, line.indexOf("{")) : "";
      for (const m of line.matchAll(editorSpacing)) {
        for (const px of m[2]!.matchAll(/(-?\d+(?:\.\d+)?)px/g)) {
          const n = Math.abs(Number(px[1]));
          if (n === 0) continue;
          if (n % 4 !== 0) errors.push(`${where} ${m[1]} ${px[0]} — off the 4px grid`);
          else if (n % 8 !== 0 && !editorControl.test(selector)) errors.push(`${where} ${m[1]} ${px[0]} outside a control — use the 8px grid`);
        }
      }
    });
  }
}

if (errors.length) {
  console.error(errors.join("\n"));
  console.error(`\n${errors.length} design-policy violation(s)`);
  process.exit(1);
}
console.log("design lint: OK");
