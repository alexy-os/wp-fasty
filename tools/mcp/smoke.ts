// bun run mcp:smoke — starts the server over stdio and exercises every tool.
import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StdioClientTransport } from "@modelcontextprotocol/sdk/client/stdio.js";
import { existsSync, readFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";

const root = resolve(dirname(new URL(import.meta.url).pathname), "../..");
const client = new Client({ name: "wpfasty-smoke", version: "0.0.0" });
await client.connect(new StdioClientTransport({ command: "bun", args: [join(root, "tools/mcp/server.ts")], cwd: root }));
let fail = 0;
const call = async (name: string, args: Record<string, unknown>, expectError = false) => {
  const r: any = await client.callTool({ name, arguments: args });
  const body = r.content?.[0]?.text ?? "";
  const ok = Boolean(r.isError) === expectError;
  console.log(`${ok ? "✓" : "✗"} ${name}${expectError ? " (expected error)" : ""}: ${body.split("\n").slice(0, 3).join(" ").slice(0, 160)}`);
  if (!ok) fail++;
  return body;
};
const tools = (await client.listTools()).tools.map((t) => t.name).sort();
const want = ["describe_schema", "generate_site", "list_archetypes", "package_site", "validate_manifest"];
console.log(`${JSON.stringify(tools) === JSON.stringify(want) ? "✓" : "✗"} tools: ${tools.join(", ")}`);
if (JSON.stringify(tools) !== JSON.stringify(want)) fail++;
await call("list_archetypes", {});
await call("describe_schema", { section: "output" });
await call("validate_manifest", { manifest: JSON.parse(readFileSync(join(root, "manifests/examples/press.json"), "utf8")) });
await call("validate_manifest", { answers: { archetype: "press", name: "Bad", tokens: { light: { background: "#fff000", foreground: "#ffffff" } } } }, true);
await call("validate_manifest", { manifest: { theme: { slug: "../../etc" } } }, true);
await call("generate_site", { answers: { archetype: "press", name: "MCP Smoke" }, pot: false });
if (existsSync(join(root, "dist/latte.zip"))) await call("package_site", { answers: { archetype: "folio", name: "MCP Folio" } });
else console.log("- package_site skipped (no dist/latte.zip; run theme:pack first)");
await client.close();
console.log(fail ? `${fail} failed` : "MCP smoke OK");
process.exit(fail ? 1 : 0);
