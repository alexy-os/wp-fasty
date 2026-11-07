// Generated offline by tools/builder/local-search-build.ts; MIT CodeGen-X. Do not edit.
export const emptySearch = () => ({ q: "", filters: {}, spec: "", variants: "one", sort: null, page: 1 });
const RESERVED = new Set(["q", "spec", "variants", "sort", "page"]);
const MAX_PAGE = 9999;
const SPEC_RE = /^[a-z]+\.[a-z0-9-]+$/;
const list = (p, k) => p.getAll(k).flatMap((v) => v.split(",")).map((v) => v.trim()).filter(Boolean);
export function parseSearch(input, vocab) {
  const at = input.indexOf("?");
  const p = new URLSearchParams(at >= 0 ? input.slice(at + 1) : input.startsWith("#") ? "" : input);
  const s = emptySearch();
  s.q = (p.get("q") ?? "").replace(/\s+/g, " ").trim().slice(0, 120);
  for (const f of vocab.facets) {
    if (RESERVED.has(f.id))
      continue;
    const asked = new Set(list(p, f.id));
    const vals = f.values.map((v) => v.id).filter((v) => asked.has(v));
    if (vals.length)
      s.filters[f.id] = vals;
  }
  const spec = p.get("spec") ?? "";
  s.spec = SPEC_RE.test(spec) ? spec : "";
  s.variants = p.get("variants") === "all" ? "all" : "one";
  const sort = p.get("sort");
  s.sort = vocab.sorts.some((x) => x.id === sort) ? sort : null;
  const page = Number(p.get("page"));
  s.page = Number.isInteger(page) && page > 1 ? Math.min(page, MAX_PAGE) : 1;
  return s;
}
export function stringifySearch(s, vocab) {
  const parts = [];
  const add = (k, v) => parts.push(`${k}=${encodeURIComponent(v).replace(/%2C/gi, ",")}`);
  if (s.q)
    add("q", s.q);
  for (const f of vocab.facets) {
    const sel = new Set(s.filters[f.id] ?? []);
    const vals = f.values.map((v) => v.id).filter((v) => sel.has(v));
    if (vals.length)
      add(f.id, vals.join(","));
  }
  if (s.spec)
    add("spec", s.spec);
  if (s.variants === "all")
    add("variants", "all");
  if (s.sort)
    add("sort", s.sort);
  if (s.page > 1)
    add("page", String(s.page));
  return parts.join("&");
}
export const searchHash = (s, vocab) => {
  const q = stringifySearch(s, vocab);
  return "#/search" + (q ? "?" + q : "");
};
export function toggleValue(s, facet, value) {
  const cur = s.filters[facet] ?? [];
  const next = cur.includes(value) ? cur.filter((v) => v !== value) : [...cur, value];
  const filters = { ...s.filters };
  if (next.length)
    filters[facet] = next;
  else
    delete filters[facet];
  return { ...s, filters, page: 1 };
}
export function setFacet(s, facet, values) {
  const filters = { ...s.filters };
  if (values.length)
    filters[facet] = [...values];
  else
    delete filters[facet];
  return { ...s, filters, page: 1 };
}
export const activeCount = (s) => (s.q ? 1 : 0) + (s.spec ? 1 : 0) + Object.values(s.filters).reduce((n, v) => n + v.length, 0);
