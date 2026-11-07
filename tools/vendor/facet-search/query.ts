// The search state and its URL form (Stock: `#/search?q=…&<facet>=a,b&…&spec=block.hero&variants=all&sort=name&page=2`).
// Modelled on @ui8kit/taste-data/search (query.ts): parseSearch is forgiving (unknown facets and values are dropped, repeated keys and
// comma lists both work, order never matters) and stringifySearch is canonical (facets in vocabulary order, values in vocabulary
// order), so equal searches give equal URLs. The facets come from the vocabulary (data), never from code.
import type { Facet, SortDef } from "./model";

export type Variants = "one" | "all";
export type SearchState = {
  q: string;
  /** facet id -> selected value ids (vocabulary order); facets without a selection are absent */
  filters: Record<string, string[]>;
  /** only the variants of one spec ("Variants" of a card), "" for all */
  spec: string;
  /** one card per block (the best variant) or every variant */
  variants: Variants;
  /** null: the default for the situation (relevance with a query, else the catalog order) */
  sort: string | null;
  /** the page of the results, 1-based (pages, not an endless scroll) */
  page: number;
};

export const emptySearch = (): SearchState => ({ q: "", filters: {}, spec: "", variants: "one", sort: null, page: 1 });

/** the vocabulary the URL is read against: the facets (their ids and values) and the sorts */
export type QueryVocab = { facets: Pick<Facet, "id" | "values">[]; sorts: Pick<SortDef, "id">[] };

const RESERVED = new Set(["q", "spec", "variants", "sort", "page"]);
const MAX_PAGE = 9999;
const SPEC_RE = /^[a-z]+\.[a-z0-9-]+$/;
const list = (p: URLSearchParams, k: string): string[] => p.getAll(k).flatMap((v) => v.split(",")).map((v) => v.trim()).filter(Boolean);

/** accepts `q=…&job=…`, `?q=…`, `#/search?q=…` or a whole hash */
export function parseSearch(input: string, vocab: QueryVocab): SearchState {
  const at = input.indexOf("?");
  const p = new URLSearchParams(at >= 0 ? input.slice(at + 1) : input.startsWith("#") ? "" : input);
  const s = emptySearch();
  s.q = (p.get("q") ?? "").replace(/\s+/g, " ").trim().slice(0, 120);
  for (const f of vocab.facets) {
    if (RESERVED.has(f.id)) continue;
    const asked = new Set(list(p, f.id));
    const vals = f.values.map((v) => v.id).filter((v) => asked.has(v));
    if (vals.length) s.filters[f.id] = vals;
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

/** the canonical query string (no leading "?"); "" for the empty search */
export function stringifySearch(s: SearchState, vocab: QueryVocab): string {
  const parts: string[] = [];
  const add = (k: string, v: string) => parts.push(`${k}=${encodeURIComponent(v).replace(/%2C/gi, ",")}`);
  if (s.q) add("q", s.q);
  for (const f of vocab.facets) {
    const sel = new Set(s.filters[f.id] ?? []);
    const vals = f.values.map((v) => v.id).filter((v) => sel.has(v));
    if (vals.length) add(f.id, vals.join(","));
  }
  if (s.spec) add("spec", s.spec);
  if (s.variants === "all") add("variants", "all");
  if (s.sort) add("sort", s.sort);
  if (s.page > 1) add("page", String(s.page));
  return parts.join("&");
}

/** the hash of a search */
export const searchHash = (s: SearchState, vocab: QueryVocab): string => { const q = stringifySearch(s, vocab); return "#/search" + (q ? "?" + q : ""); };

/** a copy with one value toggled in a facet (the facet disappears when its last value goes); a new selection starts at page 1 */
export function toggleValue(s: SearchState, facet: string, value: string): SearchState {
  const cur = s.filters[facet] ?? [];
  const next = cur.includes(value) ? cur.filter((v) => v !== value) : [...cur, value];
  const filters = { ...s.filters };
  if (next.length) filters[facet] = next; else delete filters[facet];
  return { ...s, filters, page: 1 };
}

/** a copy with one facet set to exactly these values (none: the facet is cleared); back to page 1 */
export function setFacet(s: SearchState, facet: string, values: string[]): SearchState {
  const filters = { ...s.filters };
  if (values.length) filters[facet] = [...values]; else delete filters[facet];
  return { ...s, filters, page: 1 };
}

/** how many things narrow the search (the query and the spec count as one each) */
export const activeCount = (s: SearchState): number => (s.q ? 1 : 0) + (s.spec ? 1 : 0) + Object.values(s.filters).reduce((n, v) => n + v.length, 0);
