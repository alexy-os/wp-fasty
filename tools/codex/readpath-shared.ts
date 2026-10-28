// TS side of the shared vectors (tests/fixtures/codex-shared; PHP side tests/CodexReadPathSharedTest.php and
// tests/CodegenSafetySharedTest.php): the copies of codegen-x packages/compose-gen/runtimes/core/test/{readpath-wpfasty,
// readpath-null,safety-vectors}.json must be byte-equal to the codegen-x files; core.ts readPath must give `want` on
// readpath-wpfasty.json, the wp-fasty cases readpath-wpfasty-extra.json and readpath-null.json; assets/ts/safe.ts must
// give the safety-vectors answers. Needs a codegen-x checkout (private repo; CI with the CODEGEN_X_TOKEN secret):
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
for (const f of ["readpath-wpfasty.json", "readpath-null.json", "safety-vectors.json"]) {
  if (readFileSync(join(dir, f), "utf8") !== readFileSync(join(cgx, "packages/compose-gen/runtimes/core/test", f), "utf8")) {
    bad++;
    console.error(`✗ tests/fixtures/codex-shared/${f} differs from the codegen-x copy`);
  }
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
const nulls = JSON.parse(readFileSync(join(dir, "readpath-null.json"), "utf8"));
for (const c of nulls.cases) {
  const got = core.readPath(c.source, c.path, nulls.opts);
  n++;
  if (JSON.stringify(got === undefined ? null : got) !== JSON.stringify(c.want)) {
    bad++;
    console.error(`✗ null: ${c.label}: ${JSON.stringify(got)} ≠ ${JSON.stringify(c.want)}`);
  }
}
const safe = await import(join(cgx, "packages/compose-gen/runtimes/core/assets/ts/safe.ts"));
const sv = JSON.parse(readFileSync(join(dir, "safety-vectors.json"), "utf8"));
let m = 0;
const same = (label: string, got: unknown, want: unknown) => {
  m++;
  if (JSON.stringify(got) !== JSON.stringify(want)) { bad++; console.error(`✗ safe ${label}: ${JSON.stringify(got)} ≠ ${JSON.stringify(want)}`); }
};
for (const [v, image, ok] of sv.url) same(`url ${JSON.stringify(v)}`, safe.isSafeUrl(v, image), ok);
for (const [v, ok] of sv.srcset) same(`srcset ${JSON.stringify(v)}`, safe.srcsetOf(v), ok ? v : "");
for (const [v, ok] of sv.style) same(`style ${JSON.stringify(v)}`, safe.styleOf(v), ok ? v : "");
for (const [v, ok] of sv.names) same(`name ${v}`, safe.isSafeAttrName(v), ok);
for (const [v, ok] of sv.css) same(`css ${JSON.stringify(v)}`, safe.cssValue(v), ok ? v : "");
for (const [name, v, want] of sv.attrs) same(`attr ${name}`, safe.attrValueOf(name, v), want);
console.log(bad ? `${bad} difference(s)` : `✓ ${n} shared readPath vectors (incl. ${nulls.cases.length} null) and ${m} safety vectors: codegen-x core.ts / safe.ts = want (= ReadPath.php, shipped Runtime.php: tests/CodexReadPathSharedTest.php, tests/CodegenSafetySharedTest.php); copies byte-equal`);
process.exit(bad ? 1 : 0);
