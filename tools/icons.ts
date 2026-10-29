#!/usr/bin/env bun
/**
 * Copies the Lucide icons used by the themes from lucide-static into
 * themes/latte/assets/icons (minified, no size/class attrs). Rendered via {icon('name')}.
 */
import { mkdir, readdir, rm } from "node:fs/promises";

const root = new URL("..", import.meta.url).pathname;
const src = `${root}.workspaces/css-build/node_modules/lucide-static/icons/`;
const out = `${root}themes/latte/assets/icons/`;
const ICONS = [
  "arrow-left", "arrow-right", "arrow-up-right", "calendar", "check", "chevron-down", "chevron-right",
  "circle-check", "clock", "credit-card", "external-link", "folder", "image", "mail", "map-pin",
  "menu", "message-circle", "minus", "package", "plus", "search", "shopping-bag", "shopping-cart",
  "tag", "trash-2", "truck", "user", "x", "briefcase", "layers", "sparkles", "shield-check", "rotate-ccw",
  "send", "star", "eye", "sun", "moon",
];

await rm(out, { recursive: true, force: true });
await mkdir(out, { recursive: true });
const available = new Set(await readdir(src));
let n = 0;
for (const name of ICONS) {
  if (!available.has(`${name}.svg`)) { console.warn(`skip: ${name} not in lucide-static`); continue; }
  const svg = (await Bun.file(src + name + ".svg").text())
    .replace(/<!--[\s\S]*?-->/g, "")
    .replace(/\s*class="[^"]*"/, "")
    .replace(/\s*width="24"\s*height="24"/, "")
    .replace(/\s+/g, " ").replace(/> </g, "><").trim();
  await Bun.write(out + name + ".svg", svg + "\n");
  n++;
}
console.log(`icons: ${n} Lucide SVGs -> themes/latte/assets/icons`);
