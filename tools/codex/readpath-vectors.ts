// Draft readPath vectors for the PHP port (plugins/wpfasty-builder/src/Codex/ReadPath.php), generated from the TS
// reference: codegen-x packages/compose-gen/runtimes/core/assets/app/core.ts (readPath, fromCodex).
// DRAFT — replace with the vectors of @ui8kit/contract when they exist (Q4: data pipes normative, format pipes en only).
//   bun tools/codex/readpath-vectors.ts [path/to/core.ts]  → tests/fixtures/codex-draft/readpath.json
// The Q4 pipes (excerpt-fallback, reading-minutes, term-names:, settings:, in-stock) come from core.ts itself
// (codegen-x 68004e62+); their shared TS↔PHP vectors: tests/fixtures/codex-shared (tools/codex/readpath-shared.ts).
import { writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { spawnSync } from "node:child_process";

const root = resolve(dirname(new URL(import.meta.url).pathname), "../..");
const coreTs = resolve(process.argv[2] ?? "/workspace/survey/codegen-x_ui8kit/packages/compose-gen/runtimes/core/assets/app/core.ts");
const core = await import(coreTs);
const rev = spawnSync("git", ["-C", dirname(coreTs), "log", "-1", "--format=%h", "--", coreTs], { encoding: "utf8" }).stdout.trim();

type J = Record<string, any>;
// the Q4 pipes (excerpt-fallback, reading-minutes, term-names:, settings:, in-stock) are in core.ts since codegen-x
// 68004e62; older checkouts are refused (the draft copies that stood here are gone)
if (typeof core.EXCERPT_WORDS !== "number") throw new Error(`${coreTs}: core.ts without the wp-fasty pipes (codegen-x >= 68004e62)`);
const readPath = (source: unknown, path: string, opts: J = {}): unknown => core.readPath(source, path, opts);

const T = "2026-03-30T12:00:00Z";
const minus = (m: number) => new Date(Date.parse(T) - m * 60000).toISOString().replace(/\.000Z$/, "Z");
const entry: J = {
  id: "product-7", locale: "en-US", kind: "product", status: "published", visibility: "public", slug: "linen apron/ä",
  title: "Linen apron", content: "<p>One <b>two</b>\nthree</p>", excerpt: "", author_id: "user-2", featured_media_id: "media-11",
  terms: [{ taxonomy: "product_cat", term_id: "product_cat-2" }, { taxonomy: "product_cat", term_id: "product_cat-3" }, { taxonomy: "post_tag", term_id: "post_tag-9" }],
  metadata: { price: { value: 3600 }, stock: { value: 0 }, sku: { value: "APR-03" }, nested: { value: { a: { b: 5 } } }, published: { value: minus(90) } },
  published_at: "2026-03-09T08:00:00Z", version: 1, created_at: "2026-03-09T08:00:00Z", updated_at: "2026-03-09T08:00:00Z",
};
const long = { ...entry, id: "post-1", kind: "post", content: Array.from({ length: 450 }, (_, i) => `w${i}`).join(" "), excerpt: "Own excerpt", metadata: {} };
const opts: J = {
  media: [{ id: "media-11", kind: "media", status: "published", metadata: { file: { value: "https://x.test/a.jpg" } } },
    { id: "media-12", kind: "media", status: "draft", metadata: { file: { value: "https://x.test/b.jpg" } } }],
  users: [{ id: "user-2", locale: "en-US", title: "Demo Author" }, { id: "user-3", locale: "en-US", title: "T", metadata: { display_name: { value: "Shown" } } }],
  terms: [{ id: "product_cat-2", locale: "en-US", taxonomy_id: "product_cat", name: "Kitchen", slug: "kitchen" },
    { id: "product_cat-3", locale: "en-US", taxonomy_id: "product_cat", name: "Home", slug: "home" },
    { id: "product_cat-2", locale: "ru", taxonomy_id: "product_cat", name: "Кухня", slug: "kuhnya" }],
  routes: { product: "/shop/{slug}/" },
  settings: { currency: "USD" },
};
const cases: J[] = [];
const add = (name: string, source: unknown, path: string, o: J = opts) => {
  const v = readPath(source, path, o);
  cases.push({ name, source, path, opts: o === opts ? "default" : o, expect: v === undefined ? { undefined: true } : v });
};
add("plain chrome", entry, "title");
add("dotted metadata", entry, "metadata.price.value");
add("deep", entry, "metadata.nested.value.a.b");
add("missing path", entry, "metadata.nope.value");
add("missing path with format pipe", entry, "metadata.nope.value|date");
add("through a scalar", entry, "title.x");
add("date en", entry, "published_at|date");
add("date not a date stays", { ...entry, metadata: { d: { value: "soon" } } }, "metadata.d.value|date");
for (const [m, label] of [[0, "0 min"], [2, "2 min"], [59, "59 min"], [60, "1 h"], [90, "90 min"], [23 * 60 + 59, "23 h"], [1440, "1 day"], [2 * 1440, "2 days"],
  [6 * 1440, "6 days"], [7 * 1440, "1 week"], [15 * 1440, "15 days"], [40 * 1440, "40 days"]] as const)
  for (const style of ["ago", "ago-days", "ago-short"]) add(`${style} ${label}`, { ...entry, metadata: { t: { value: minus(m) } } }, `metadata.t.value|${style}`);
add("ago future clamps to 0", { ...entry, metadata: { t: { value: "2026-03-31T12:00:00Z" } } }, "metadata.t.value|ago");
add("media published", entry, "featured_media_id|media");
add("media draft is no file", { ...entry, featured_media_id: "media-12" }, "featured_media_id|media");
add("media unknown", { ...entry, featured_media_id: "media-99" }, "featured_media_id|media");
add("author title fallback", entry, "author_id|author");
add("author display_name", { ...entry, author_id: "user-3" }, "author_id|author");
add("term first of taxonomy", entry, "terms|term:product_cat");
add("term slug", entry, "terms|term-slug:product_cat");
add("term by locale", { ...entry, locale: "ru" }, "terms|term:product_cat");
add("term other taxonomy absent", entry, "terms|term:category");
add("route", entry, "slug|route");
add("route no kind route", { ...entry, kind: "post" }, "slug|route");
for (const [v, cur] of [[3600, "USD"], [3650, "USD"], [-1250, "USD"], [4820000, "USD"], [1250, "EUR"], [1250, "GBP"], [1250, "JPY"], [0, "USD"]] as const)
  add(`number currency ${v} ${cur}`, { ...entry, metadata: { price: { value: v }, currency: { value: cur } } }, "metadata.price.value|number");
for (const [v, unit] of [[12400, undefined], [99.99, "percent"], [0.8, "second"], [120, "millisecond"], [5, "minute"], [2, "hour"], [3, "day"],
  [12, "kilobyte"], [3.456, "megabyte"], [1, "gigabyte"], [1234.5678, undefined], [7, "parsec"], [-3.5, undefined]] as const)
  add(`number figure ${v} ${unit ?? "-"}`, { ...entry, metadata: { v: { value: v }, ...(unit ? { unit: { value: unit } } : {}) } }, "metadata.v.value|number");
add("number not a number", entry, "metadata.sku.value|number");
for (const v of [0, 96, 999, 1000, 1200, 1250, 9999, 15300, 999999, 1000000, 2500000, 1200000000, 1500000000000])
  add(`compact ${v}`, { ...entry, metadata: { v: { value: v } } }, "metadata.v.value|compact");
add("excerpt-fallback own", long, "excerpt|excerpt-fallback");
add("excerpt-fallback from content (absent)", { ...long, excerpt: undefined }, "excerpt|excerpt-fallback");
add("excerpt-fallback short content", { ...entry, excerpt: undefined }, "excerpt|excerpt-fallback");
add("excerpt-fallback nothing", { ...entry, excerpt: undefined, content: undefined }, "excerpt|excerpt-fallback");
add("reading-minutes 450 words", long, "content|reading-minutes");
add("reading-minutes 3 words", entry, "content|reading-minutes");
add("reading-minutes no content", { ...entry, content: undefined }, "content|reading-minutes");
add("term-names", entry, "terms|term-names:product_cat");
add("term-names none", entry, "terms|term-names:category");
add("term-names of a map is absent", { ...entry, terms: { product_cat: { taxonomy: "product_cat", term_id: "product_cat-2" } } }, "terms|term-names:product_cat");
add("settings fallback", entry, "metadata.currency.value|settings:currency");
add("settings own wins", { ...entry, metadata: { currency: { value: "EUR" } } }, "metadata.currency.value|settings:currency");
add("in-stock 0", entry, "metadata.stock.value|in-stock");
add("in-stock unset = unlimited", { ...entry, metadata: {} }, "metadata.stock.value|in-stock");
add("in-stock 5", { ...entry, metadata: { stock: { value: 5 } } }, "metadata.stock.value|in-stock");
add("no options: media undefined", entry, "featured_media_id|media", {});

const out = {
  draft: "draft, заменить на vectors из @ui8kit/contract",
  source: `codegen-x packages/compose-gen/runtimes/core/assets/app/core.ts @ ${rev || "?"}`,
  now: core.DEMO_NOW, default_locale: core.DEFAULT_LOCALE, opts, cases,
};
writeFileSync(join(root, "tests/fixtures/codex-draft/readpath.json"), `${JSON.stringify(out, null, 1)}\n`);
console.log(`${cases.length} readPath vectors (core.ts ${rev})`);
