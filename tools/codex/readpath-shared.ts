// TS side of the shared readPath vectors (tests/fixtures/codex-shared, PHP side tests/CodexReadPathSharedTest.php):
// codegen-x core.ts readPath must give `want` on readpath-wpfasty.json (a copy of codegen-x
// packages/compose-gen/runtimes/core/test/readpath-wpfasty.json, which must be byte-equal) and on the wp-fasty cases
// readpath-wpfasty-extra.json. Needs a codegen-x checkout (private repo; not in CI):
//   bun tools/codex/readpath-shared.ts [--cgx /path/to/codegen-x]
import { readFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";

const root = resolve(dirname(new URL(import.meta.url).pathname), "../..");
const i = process.argv.indexOf("--cgx");
const cgx = resolve(i > 0 ? process.argv[i + 1]! : process.env.CGX_DIR ?? "/workspace/cgx-ui8kit");
const core = await import(join(cgx, "packages/compose-gen/runtimes/core/assets/app/core.ts"));
const dir = join(root, "tests/fixtures/codex-shared");
const mine = readFileSync(join(dir, "readpath-wpfasty.json"), "utf8");
let bad = 0;
if (mine !== readFileSync(join(cgx, "packages/compose-gen/runtimes/core/test/readpath-wpfasty.json"), "utf8")) {
  bad++;
  console.error("✗ tests/fixtures/codex-shared/readpath-wpfasty.json differs from the codegen-x copy");
}
const main = JSON.parse(mine);
const extra = JSON.parse(readFileSync(join(dir, "readpath-wpfasty-extra.json"), "utf8"));
const expand = (s: unknown): unknown => (typeof s === "string" && /^W\d+$/.test(s) ? Array(Number(s.slice(1))).fill("w").join(" ") : s);
let n = 0;
for (const c of [...main.cases, ...extra.cases]) {
  const source = { ...c.source };
  if ("content" in source) source.content = expand(source.content);
  const got = core.readPath(source, c.path, main.opts);
  n++;
  if (JSON.stringify(got === undefined ? null : got) !== JSON.stringify(c.want)) {
    bad++;
    console.error(`✗ ${c.label}: ${JSON.stringify(got)} ≠ ${JSON.stringify(c.want)}`);
  }
}
console.log(bad ? `${bad} difference(s)` : `✓ ${n} shared readPath vectors: core.ts = want (= ReadPath.php, tests/CodexReadPathSharedTest.php)`);
process.exit(bad ? 1 : 0);
