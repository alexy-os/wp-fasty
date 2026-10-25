// Codex conformance of a live site: GET /wpfasty/v1/codex/* → (1) @ui8kit/contract validation (entry/term/settings
// schemas + validateCollectionV04 against the theme's codex.manifest.json), (2) canonical bytes: every entry body must
// equal canonicalize() of itself (@ui8kit/contract src/canonical.ts), (3) byte-for-byte compare with golden files.
//   bun tools/codex/conform.ts <base-url> <theme-slug> [--golden dir] [--write]
import { existsSync, mkdirSync, readdirSync, readFileSync, rmSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import Ajv2020 from "ajv/dist/2020";
import addFormats from "ajv-formats";

const root = resolve(dirname(new URL(import.meta.url).pathname), "../..");
// The contract with canonicalize() (ui8kit/contract >= 8299fee; npm 0.3.0 not published yet): CODEX_CONTRACT = path
// of a built checkout (CI pins the commit), else the installed @ui8kit/contract.
const contractDir = process.env.CODEX_CONTRACT ? resolve(process.env.CODEX_CONTRACT) : join(root, "node_modules/@ui8kit/contract");
const C = await import(join(contractDir, "dist/index.js"));
const { canonicalize, validateCollectionV04 } = C as { canonicalize?: (t: string) => string; validateCollectionV04: any };
if (!canonicalize) { console.error(`${contractDir}: this @ui8kit/contract has no canonicalize() — set CODEX_CONTRACT`); process.exit(2); }
const [base, slug] = process.argv.slice(2);
const at = process.argv.indexOf("--golden");
const golden = at > 0 ? resolve(process.argv[at + 1]) : "";
const write = process.argv.includes("--write");
if (!base || !slug) { console.error("usage: conform.ts <base-url> <theme-slug> [--golden dir] [--write]"); process.exit(2); }

const api = `${base.replace(/\/$/, "")}/wp-json/wpfasty/v1/codex`;
const fails: string[] = [];
const bodies = new Map<string, string>(); // golden file name → bytes

async function get(path: string, expect = 200): Promise<string> {
  const r = await fetch(api + path);
  const text = await r.text();
  if (r.status !== expect) fails.push(`${path}: HTTP ${r.status} (want ${expect}) ${text.slice(0, 200)}`);
  const ct = r.headers.get("content-type");
  if (ct !== "application/json; charset=utf-8") fails.push(`${path}: Content-Type "${ct}"`);
  return text;
}

const manifest = JSON.parse(readFileSync(join(root, "themes", slug, "types/codex.manifest.json"), "utf8"));
const ajv = new Ajv2020({ allErrors: true, strict: false });
addFormats(ajv);
const schema = (n: string) => ajv.compile(JSON.parse(readFileSync(join(contractDir, `schemas/${n}.schema.json`), "utf8")));
const check = { entry: schema("entry"), term: schema("term"), settings: schema("settings") };
const shape = (what: keyof typeof check, v: unknown, where: string) => {
  if (!check[what](v)) fails.push(`${where}: ${what} schema: ${ajv.errorsText(check[what].errors)}`);
};

const disco = await get("/");
bodies.set("discovery.json", disco);
const d = JSON.parse(disco);
const delivery = JSON.parse(readFileSync(join(root, "themes", slug, "types/codex.delivery.json"), "utf8"));
if (d.manifest_digest !== delivery.manifest_digest) fails.push(`discovery digest ${d.manifest_digest} ≠ codex.delivery.json ${delivery.manifest_digest}`);

const entries: { file: string; data: unknown }[] = [];
const terms: { file: string; data: unknown }[] = [];
const kinds = manifest.resources.map((r: any) => r.record.id).filter((k: string) => k !== "media" && k !== "user");
const refs = new Set<string>();
for (const kind of kinds) {
  for (let page = 1; ; page++) {
    const body = await get(`/entries/${kind}?per_page=100&page=${page}`);
    const env = JSON.parse(body);
    bodies.set(`entries.${kind}.${page}.json`, body);
    for (const e of env.data) {
      const one = await get(`/entries/${kind}/${e.id}`);
      bodies.set(`entry.${e.id}.json`, one);
      if (JSON.stringify(JSON.parse(one)) !== JSON.stringify(e)) fails.push(`${kind}/${e.id}: single ≠ list item`);
      try { if (canonicalize(one) !== one) fails.push(`${kind}/${e.id}: body is not its canonical form`); }
      catch (err) { fails.push(`${kind}/${e.id}: canonicalize: ${(err as Error).message}`); }
      shape("entry", e, `${kind}/${e.id}`);
      entries.push({ file: `${kind}/${e.id}`, data: e });
      for (const k of ["author_id", "featured_media_id"]) if (e[k]) refs.add(e[k]);
    }
    if (page >= env.pagination.total_pages) break;
  }
}
for (const id of [...refs].sort()) {
  const kind = id.slice(0, id.lastIndexOf("-"));
  const r = await fetch(`${api}/entries/${kind}/${id}`);
  if (r.status === 404) continue; // e.g. an author without public content
  const one = await r.text();
  bodies.set(`entry.${id}.json`, one);
  try { if (canonicalize(one) !== one) fails.push(`${id}: body is not its canonical form`); } catch (err) { fails.push(`${id}: ${(err as Error).message}`); }
  const e = JSON.parse(one);
  shape("entry", e, id);
  entries.push({ file: id, data: e });
}
const tx = JSON.parse(await get("/taxonomies"));
bodies.set("taxonomies.json", JSON.stringify(tx));
for (const t of tx.data) {
  const body = await get(`/terms/${t.id}?per_page=100`);
  bodies.set(`terms.${t.id}.json`, body);
  for (const term of JSON.parse(body).data) { shape("term", term, `${t.id}/${term.id}`); terms.push({ file: `${t.id}/${term.id}`, data: term }); }
}
const settingsBody = await get("/settings");
bodies.set("settings.json", settingsBody);
const settings = JSON.parse(settingsBody);
shape("settings", settings, "settings");

// Errors and pagination in the form of fastygo/backend TestCodexV1Conform (internal/conformance, 1ef630f): same cases,
// same codes and messages, error = {code, message, status[, details]} with status = the HTTP status.
const errorCases: [string, number, string, string][] = [
  ["/entries/unknownkind", 404, "not_found", "kind was not found"],
  ["/entries/post/missing", 404, "not_found", "content was not found"],
  ["/entries/post/post-0", 404, "not_found", "content was not found"],
  ["/entries/post?locale=fr", 404, "not_found", "locale was not found"],
  ["/terms/missing", 404, "not_found", "taxonomy was not found"],
  ["/entries/post?locale=ENGLISH", 400, "validation_error", "locale is invalid"],
  ["/entries/post?page=0", 400, "validation_error", "page must be a positive integer"],
  ["/entries/post?per_page=-1", 400, "validation_error", "per_page must be a positive integer"],
  ["/entries/post?foo=1", 400, "validation_error", "unknown query parameter"],
];
for (const [path, status, code, message] of errorCases) {
  const body = JSON.parse(await get(path, status));
  const e = body.error ?? {};
  const keys = Object.keys(body).join(",");
  if (keys !== "error") fails.push(`${path}: error document keys ${keys}`);
  for (const k of Object.keys(e)) if (!["code", "message", "status", "details"].includes(k)) fails.push(`${path}: error.${k} not in the form`);
  if (e.code !== code || e.status !== status || e.message !== message)
    fails.push(`${path}: error ${JSON.stringify(e)} (want ${code} ${status} "${message}")`);
}
{
  const all = JSON.parse(await get("/entries/post"));
  const p = all.pagination ?? {};
  if (p.page !== 1 || p.per_page !== 20) fails.push(`/entries/post: default pagination ${JSON.stringify(p)} (want page 1, per_page 20)`);
  if (p.total_pages !== (p.total ? Math.ceil(p.total / p.per_page) : 0)) fails.push(`/entries/post: total_pages ${p.total_pages}`);
  const ids = all.data.map((d: { id: string }) => d.id);
  if (ids.join() !== [...ids].sort((a: string, b: string) => (Buffer.from(a) < Buffer.from(b) ? -1 : 1)).join()) fails.push("/entries/post: not in id order");
  if (p.total >= 1) {
    const one = JSON.parse(await get("/entries/post?page=1&per_page=1"));
    if (one.pagination.per_page !== 1 || one.pagination.total !== p.total || one.data[0]?.id !== ids[0])
      fails.push(`page=1&per_page=1: ${JSON.stringify(one.pagination)} first ${one.data[0]?.id}`);
  }
  const clamped = JSON.parse(await get("/entries/post?per_page=1000"));
  if (clamped.pagination?.per_page !== 100) fails.push(`per_page=1000: per_page ${clamped.pagination?.per_page} (want clamped to 100)`);
  const past = await get("/entries/post?page=999&per_page=1");
  if (!/"data":\[\]/.test(past)) fails.push(`page past the end must be an empty array: ${past}`);
}

const issues = validateCollectionV04({ entries, terms, settings: [{ file: "settings", data: settings }] }, { manifest });
for (const i of issues) if (i.severity !== "warning") fails.push(`contract ${i.code} ${i.path}: ${i.message}`);
const warnings = issues.filter((i) => i.severity === "warning");

// Golden bytes are host-independent: the site origin (base URL) is stored as "{base}"; everything else is compared
// byte for byte.
const portable = (b: string) => b.split(base.replace(/\/$/, "")).join("{base}");
if (golden) {
  if (write) {
    rmSync(golden, { recursive: true, force: true });
    mkdirSync(golden, { recursive: true });
    for (const [f, b] of bodies) writeFileSync(join(golden, f), portable(b));
  } else {
    if (!existsSync(golden)) fails.push(`golden dir ${golden} missing (run with --write)`);
    else {
      const have = new Set(readdirSync(golden));
      for (const [f, b] of bodies) {
        if (!have.has(f)) fails.push(`golden: ${f} missing`);
        else if (readFileSync(join(golden, f), "utf8") !== portable(b)) fails.push(`golden: ${f} differs`);
        have.delete(f);
      }
      for (const f of have) fails.push(`golden: ${f} not served any more`);
    }
  }
}

console.log(`${slug}: ${entries.length} entries, ${terms.length} terms, ${bodies.size} bodies, ${warnings.length} warning(s)${golden ? (write ? ", golden written" : ", golden compared") : ""}`);
for (const w of warnings.slice(0, 10)) console.log(`  warning ${w.code} ${w.path}`);
if (fails.length) { for (const f of fails) console.log(`✗ ${f}`); process.exit(1); }
console.log(`✓ ${slug}: Codex conformance OK`);
