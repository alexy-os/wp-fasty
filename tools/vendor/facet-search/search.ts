// The faceted search engine: one pure function over a static index, `search(engine, state)`, for the browser and later for a server
// or an MCP tool (one ranking and one set of counts). Written for Stock (apps/stock, S2), modelled on @ui8kit/taste-data/search (filter.ts): multi-select within a facet
// (OR), AND across facets, counts that ignore the facet's own selection and obey everything else (disjunctive), a text query matched by
// word prefix with field weights, and the sorts. Variants of one block collapse into one card (the best-ranked variant) and the counts
// then count cards, so the count of a value is the number of cards its selection gives. Soft facets (industry) rank, never filter.
import { LOCALES, type Asset, type Facet, type Index } from "./model";
import type { SearchState } from "./query";

export type Item<P = unknown> = {
  asset: Asset<P>;
  /** collapse key index */
  g: number;
  /** per facet (engine order): value indices, or null when the facet does not apply; WILD for "*" */
  vals: (Int16Array | null)[];
  fields: { w: number; words: string[] }[];
  /** lower-case title for the name sort */
  name: string;
};
export type Engine<P = unknown> = { index: Index<P>; facets: Facet[]; items: Item<P>[]; groups: string[]; valueIndex: Map<string, number>[] };
export type Counts = Record<string, Map<string, number>>;
export type Outcome<P = unknown> = {
  results: Asset<P>[];
  /** cards before collapsing variants, i.e. matching variants */
  matched: number;
  /** facet id -> value id -> count (cards when variants are collapsed, else assets) */
  counts: Counts;
  /** matching variants per collapse key (the "Variants (N)" of a card) */
  variantsOf: Map<string, number>;
  sort: string;
  total: number;
};

const WILD = -1;
const NONE = new Int16Array(0);
/** query words: lower case, split on anything that is not a letter or a digit (any script) */
export const queryTokens = (q: string): string[] => q.toLowerCase().split(/[^\p{L}\p{N}]+/u).filter(Boolean);
const split = (s: string): string[] => [...new Set([s.toLowerCase().replace(/\s+/g, ""), ...queryTokens(s)])].filter(Boolean);

/** load an index: label value indices per facet and the words of the labels (names and synonyms in every locale) added to the haystack */
export function prepare<P>(index: Index<P>): Engine<P> {
  const facets = index.facets;
  const valueIndex = facets.map((f) => new Map(f.values.map((v, i) => [v.id, i])));
  const groups: string[] = [];
  const gIndex = new Map<string, number>();
  const items = index.assets.map((a) => {
    let g = gIndex.get(a.group);
    if (g === undefined) { g = groups.length; groups.push(a.group); gIndex.set(a.group, g); }
    const vals = facets.map((f, fi) => {
      const l = a.labels[f.id];
      if (!l) return null;
      if (l.includes("*")) return Int16Array.of(WILD);
      return l.length ? Int16Array.from(l.map((v) => valueIndex[fi]!.get(v) ?? -2).filter((i) => i >= 0)) : NONE;
    });
    const labelWords = new Set<string>();
    facets.forEach((f, fi) => {
      const v = vals[fi];
      if (!v || v[0] === WILD || f.ui === "tabs" || f.derive === "markup") return;
      for (const i of v) { const fv = f.values[i]!; for (const w of [...split(fv.id), ...LOCALES.flatMap((l) => [...(fv.label[l] ? split(fv.label[l]!) : []), ...(fv.synonyms?.[l] ?? []).flatMap(split)])]) labelWords.add(w); }
    });
    return { asset: a, g, vals, fields: [...a.fields, { w: 2, words: [...labelWords] }], name: (a.title + " " + a.subtitle).toLowerCase() };
  });
  // the variants of one collapse key next to each other (stable), so the counts only remember the last key per value
  items.sort((x, y) => x.g - y.g);
  return { index, facets, items, groups, valueIndex };
}

/** relevance of one item: every token must match a word by prefix; per token the best field weight, doubled for an exact word */
export function relevance(item: Item<unknown>, tokens: readonly string[]): number {
  if (!tokens.length) return 1;
  let total = 0;
  for (const t of tokens) {
    let best = 0;
    for (const f of item.fields) {
      for (const w of f.words) {
        if (w === t) { best = Math.max(best, f.w * 2); break; }
        if (w.startsWith(t)) best = Math.max(best, f.w);
      }
    }
    if (!best) return 0;
    total += best;
  }
  return total;
}

/** the sort that applies: the asked one, else relevance with a query and the catalog order without */
export const effectiveSort = (s: SearchState, sorts: readonly { id: string }[]): string =>
  s.sort && sorts.some((x) => x.id === s.sort) ? s.sort : s.q ? "relevance" : "catalog";

export function search<P>(e: Engine<P>, s: SearchState): Outcome<P> {
  const tokens = queryTokens(s.q);
  const F = e.facets.length;
  const hard: number[] = [];
  const sel: (Set<number> | null)[] = e.facets.map((f, fi) => {
    const v = s.filters[f.id];
    if (!v?.length) return null;
    const set = new Set(v.map((x) => e.valueIndex[fi]!.get(x)).filter((i): i is number => i !== undefined));
    if (!set.size) return null;
    if (f.mode !== "soft") hard.push(fi);
    return set;
  });
  const soft = e.facets.map((f, fi) => (f.mode === "soft" ? sel[fi] : null));
  const BIT_Q = 1 << F, BIT_SPEC = BIT_Q << 1;
  const collapse = s.variants !== "all";
  // counts: per facet, per value, the cards (collapse keys) or the items that pass every other filter
  const seen: Int32Array[] = e.facets.map((f) => new Int32Array(f.values.length).fill(-1));
  const cnt: Int32Array[] = e.facets.map((f) => new Int32Array(f.values.length));
  const bump = (fi: number, vi: number, g: number, n: number): void => {
    if (collapse) { if (seen[fi]![vi] === g) return; seen[fi]![vi] = g; }
    cnt[fi]![vi]! += n;
  };
  const hits: { it: Item<P>; rel: number; boost: number }[] = [];
  for (let k = 0; k < e.items.length; k++) {
    const it = e.items[k]!;
    let mask = 0;
    for (const fi of hard) {
      const v = it.vals[fi];
      if (!v || !v.length) { mask |= 1 << fi; continue; }
      if (v[0] === WILD) continue;
      const set = sel[fi]!;
      let ok = false;
      for (const x of v) if (set.has(x)) { ok = true; break; }
      if (!ok) mask |= 1 << fi;
    }
    let rel = 1;
    if (tokens.length) { rel = relevance(it, tokens); if (!rel) mask |= BIT_Q; }
    if (s.spec && it.asset.spec !== s.spec) mask |= BIT_SPEC;
    for (let fi = 0; fi < F; fi++) {
      if (mask & ~(1 << fi)) continue;
      const v = it.vals[fi];
      if (!v) continue;
      if (v.length && v[0] === WILD) { for (let vi = 0; vi < cnt[fi]!.length; vi++) bump(fi, vi, it.g, 1); continue; }
      for (const vi of v) bump(fi, vi, it.g, 1);
    }
    if (mask === 0) {
      let boost = 0;
      soft.forEach((set, fi) => { if (!set) return; const v = it.vals[fi]; if (v) for (const x of v) if (x === WILD || set.has(x)) { boost++; break; } });
      hits.push({ it, rel, boost });
    }
  }
  const sort = effectiveSort(s, e.index.sorts);
  hits.sort((a, b) => {
    const A = a.it, B = b.it;
    let d = 0;
    if (sort === "name") d = A.name < B.name ? -1 : A.name > B.name ? 1 : 0;
    else if (sort === "newest") d = (B.asset.added ?? "").localeCompare(A.asset.added ?? "") || B.asset.order - A.asset.order;
    else {
      d = b.boost - a.boost;
      if (!d && sort === "relevance") d = b.rel - a.rel;
      if (!d && sort === "unlabelled") d = B.asset.missing.length - A.asset.missing.length || B.asset.weak.length - A.asset.weak.length;
    }
    return d || A.asset.order - B.asset.order;
  });
  const variantsOf = new Map<string, number>();
  for (const h of hits) variantsOf.set(h.it.asset.group, (variantsOf.get(h.it.asset.group) ?? 0) + 1);
  let results: Asset<P>[];
  if (collapse) {
    const taken = new Set<number>();
    results = [];
    for (const h of hits) { if (taken.has(h.it.g)) continue; taken.add(h.it.g); results.push(h.it.asset); }
  } else results = hits.map((h) => h.it.asset);
  const counts: Counts = {};
  e.facets.forEach((f, fi) => { counts[f.id] = new Map(f.values.map((v, vi) => [v.id, cnt[fi]![vi]!])); });
  return { results, matched: hits.length, counts, variantsOf, sort, total: e.items.length };
}

export type Relaxation = { facet: string; value: string; count: number } | { facet: "q" | "spec"; value: string; count: number };

/** what each active filter costs: the result size without it, largest first (the hint of an empty result: "remove X -> N") */
export function relaxations<P>(e: Engine<P>, s: SearchState): Relaxation[] {
  const out: Relaxation[] = [];
  if (s.q) out.push({ facet: "q", value: s.q, count: search(e, { ...s, q: "" }).results.length });
  if (s.spec) out.push({ facet: "spec", value: s.spec, count: search(e, { ...s, spec: "" }).results.length });
  for (const [f, vals] of Object.entries(s.filters)) for (const v of vals) {
    const rest = vals.filter((x) => x !== v);
    const filters = { ...s.filters };
    if (rest.length) filters[f] = rest; else delete filters[f];
    out.push({ facet: f, value: v, count: search(e, { ...s, filters }).results.length });
  }
  return out.sort((a, b) => b.count - a.count);
}

export type Page<T> = { items: T[]; page: number; pages: number; perPage: number; total: number; from: number; to: number };

/** one page of a result list: the asked page clamped to the last one; from/to are 1-based positions (0 when empty) */
export function paginate<T>(list: readonly T[], page: number, perPage: number): Page<T> {
  const size = Math.max(1, Math.floor(perPage));
  const pages = Math.max(1, Math.ceil(list.length / size));
  const p = Math.min(Math.max(1, Math.floor(page) || 1), pages);
  const start = (p - 1) * size;
  const items = list.slice(start, start + size);
  return { items, page: p, pages, perPage: size, total: list.length, from: items.length ? start + 1 : 0, to: start + items.length };
}

/** the page numbers to show around the current one: first, last, a window of `around` on each side, null for a gap */
export function pageWindow(page: number, pages: number, around = 1): (number | null)[] {
  const out: (number | null)[] = [];
  for (let n = 1; n <= pages; n++) {
    if (n === 1 || n === pages || Math.abs(n - page) <= around) out.push(n);
    else if (out[out.length - 1] !== null) out.push(null);
  }
  return out;
}
