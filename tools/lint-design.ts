#!/usr/bin/env bun
/**
 * Design-policy lint for theme views/CSS (not UI8Kit primitives):
 *  - colors only via CSS variable tokens (no hex/rgb/hsl/oklch, no Tailwind palette colors)
 *  - security: raw array values must not be passed as primitive `children`
 *  - layout spacing on an 8px grid: odd spacing steps (1,3,5,7… = 4px multiples) are allowed
 *    only on control lines (button/input/select/label/badge/textarea/option).
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
      if (rel.endsWith(".twig") && /children:\s*[\w]+(?:\.|\[)/.test(line)) errors.push(`${where} raw data passed as children (XSS) — wrap in {% set %}…{% endset %}`);
      if (/\.(latte|twig)$/.test(rel) && halfStep.test(line)) errors.push(`${where} half spacing step — use the 8px grid`);
      if (/\.(latte|twig)$/.test(rel) && arbitraryPx.test(line)) errors.push(`${where} arbitrary px value — use scale/tokens`);
      if (/\.(latte|twig)$/.test(rel) && oddSpacing.test(line) && !control.test(line)) errors.push(`${where} 4px step outside a control — use 8px grid (2,4,6,8…)`);
    });
  }
}
if (errors.length) {
  console.error(errors.join("\n"));
  console.error(`\n${errors.length} design-policy violation(s)`);
  process.exit(1);
}
console.log("design lint: OK");
