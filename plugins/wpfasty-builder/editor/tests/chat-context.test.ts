import { expect, test } from "bun:test";
import { readFileSync } from "node:fs";
import { builderContext } from "../src/chat/context";
import type { BlockSpec, Item } from "../src/types";

const catalog = [{ id: "hero", variants: [{ id: "centered", template: "blocks/hero-classic.latte" }] }] as BlockSpec[];
const items: Item[] = [{ id: "instance-1", block: "hero", variant: "centered", props: { title: "Actual title <script>" }, bindings: {} }];
test("context preserves structural blockId/variant/props and authoritative template", () => {
  const original = JSON.stringify(items);
  const context = builderContext("ui8kit", items, catalog, "instance-1");
  expect(context.blocks[0]).toEqual({ instanceId: "instance-1", blockId: "hero", variant: "centered", props: items[0]!.props, bindings: {}, template: "blocks/hero-classic.latte", catalogValid: true });
  expect(JSON.stringify(items)).toBe(original);
});
test("unknown variant is flagged, never converted to an invented template", () => {
  const context = builderContext("ui8kit", [{ ...items[0]!, variant: "invented" }], catalog, null);
  expect(context.blocks[0]!.catalogValid).toBe(false);
  expect(context.blocks[0]!.template).toBe(null);
});
test("panel delegates to protected WP API and never injects model HTML", () => {
  const code = readFileSync(new URL("../src/chat/ChatPanel.svelte", import.meta.url), "utf8");
  expect(code).not.toMatch(/fetch\(|@html|\.save\(/);
  expect(code).toContain("disabled={!ready || busy}");
  expect(code).toContain('await apply(requestId)');
  expect(code).not.toMatch(/<(div|button|section|pre|p|h2)\b/);
});

// Configuration is not provider readiness; history must fail closed for sending.
test("chat remains unavailable without explicit safe server configuration metadata", () => {
  const code = readFileSync(new URL("../src/chat/ChatPanel.svelte", import.meta.url), "utf8");
  expect(code).toContain("data.availability?.configured === true");
  expect(code).toContain("disabled={!ready || busy}");
});
