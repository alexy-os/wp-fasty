// Generated offline by tools/builder/local-search-build.ts; MIT CodeGen-X. Do not edit.
import { LOCALES } from "./model.mjs";
const WILD = -1;
const NONE = new Int16Array(0);
export const queryTokens = (q) => q.toLowerCase().split(/[^\p{L}\p{N}]+/u).filter(Boolean);
const split = (s) => [...new Set([s.toLowerCase().replace(/\s+/g, ""), ...queryTokens(s)])].filter(Boolean);
export function prepare(index) {
  const facets = index.facets;
  const valueIndex = facets.map((f) => new Map(f.values.map((v, i) => [v.id, i])));
  const groups = [];
  const gIndex = new Map;
  const items = index.assets.map((a) => {
    let g = gIndex.get(a.group);
    if (g === undefined) {
      g = groups.length;
      groups.push(a.group);
      gIndex.set(a.group, g);
    }
    const vals = facets.map((f, fi) => {
      const l = a.labels[f.id];
      if (!l)
        return null;
      if (l.includes("*"))
        return Int16Array.of(WILD);
      return l.length ? Int16Array.from(l.map((v) => valueIndex[fi].get(v) ?? -2).filter((i) => i >= 0)) : NONE;
    });
    const labelWords = new Set;
    facets.forEach((f, fi) => {
      const v = vals[fi];
      if (!v || v[0] === WILD || f.ui === "tabs" || f.derive === "markup")
        return;
      for (const i of v) {
        const fv = f.values[i];
        for (const w of [...split(fv.id), ...LOCALES.flatMap((l) => [...fv.label[l] ? split(fv.label[l]) : [], ...(fv.synonyms?.[l] ?? []).flatMap(split)])])
          labelWords.add(w);
      }
    });
    return { asset: a, g, vals, fields: [...a.fields, { w: 2, words: [...labelWords] }], name: (a.title + " " + a.subtitle).toLowerCase() };
  });
  items.sort((x, y) => x.g - y.g);
  return { index, facets, items, groups, valueIndex };
}
export function relevance(item, tokens) {
  if (!tokens.length)
    return 1;
  let total = 0;
  for (const t of tokens) {
    let best = 0;
    for (const f of item.fields) {
      for (const w of f.words) {
        if (w === t) {
          best = Math.max(best, f.w * 2);
          break;
        }
        if (w.startsWith(t))
          best = Math.max(best, f.w);
      }
    }
    if (!best)
      return 0;
    total += best;
  }
  return total;
}
export const effectiveSort = (s, sorts) => s.sort && sorts.some((x) => x.id === s.sort) ? s.sort : s.q ? "relevance" : "catalog";
export function search(e, s) {
  const tokens = queryTokens(s.q);
  const F = e.facets.length;
  const hard = [];
  const sel = e.facets.map((f, fi) => {
    const v = s.filters[f.id];
    if (!v?.length)
      return null;
    const set = new Set(v.map((x) => e.valueIndex[fi].get(x)).filter((i) => i !== undefined));
    if (!set.size)
      return null;
    if (f.mode !== "soft")
      hard.push(fi);
    return set;
  });
  const soft = e.facets.map((f, fi) => f.mode === "soft" ? sel[fi] : null);
  const BIT_Q = 1 << F, BIT_SPEC = BIT_Q << 1;
  const collapse = s.variants !== "all";
  const seen = e.facets.map((f) => new Int32Array(f.values.length).fill(-1));
  const cnt = e.facets.map((f) => new Int32Array(f.values.length));
  const bump = (fi, vi, g, n) => {
    if (collapse) {
      if (seen[fi][vi] === g)
        return;
      seen[fi][vi] = g;
    }
    cnt[fi][vi] += n;
  };
  const hits = [];
  for (let k = 0;k < e.items.length; k++) {
    const it = e.items[k];
    let mask = 0;
    for (const fi of hard) {
      const v = it.vals[fi];
      if (!v || !v.length) {
        mask |= 1 << fi;
        continue;
      }
      if (v[0] === WILD)
        continue;
      const set = sel[fi];
      let ok = false;
      for (const x of v)
        if (set.has(x)) {
          ok = true;
          break;
        }
      if (!ok)
        mask |= 1 << fi;
    }
    let rel = 1;
    if (tokens.length) {
      rel = relevance(it, tokens);
      if (!rel)
        mask |= BIT_Q;
    }
    if (s.spec && it.asset.spec !== s.spec)
      mask |= BIT_SPEC;
    for (let fi = 0;fi < F; fi++) {
      if (mask & ~(1 << fi))
        continue;
      const v = it.vals[fi];
      if (!v)
        continue;
      if (v.length && v[0] === WILD) {
        for (let vi = 0;vi < cnt[fi].length; vi++)
          bump(fi, vi, it.g, 1);
        continue;
      }
      for (const vi of v)
        bump(fi, vi, it.g, 1);
    }
    if (mask === 0) {
      let boost = 0;
      soft.forEach((set, fi) => {
        if (!set)
          return;
        const v = it.vals[fi];
        if (v) {
          for (const x of v)
            if (x === WILD || set.has(x)) {
              boost++;
              break;
            }
        }
      });
      hits.push({ it, rel, boost });
    }
  }
  const sort = effectiveSort(s, e.index.sorts);
  hits.sort((a, b) => {
    const A = a.it, B = b.it;
    let d = 0;
    if (sort === "name")
      d = A.name < B.name ? -1 : A.name > B.name ? 1 : 0;
    else if (sort === "newest")
      d = (B.asset.added ?? "").localeCompare(A.asset.added ?? "") || B.asset.order - A.asset.order;
    else {
      d = b.boost - a.boost;
      if (!d && sort === "relevance")
        d = b.rel - a.rel;
      if (!d && sort === "unlabelled")
        d = B.asset.missing.length - A.asset.missing.length || B.asset.weak.length - A.asset.weak.length;
    }
    return d || A.asset.order - B.asset.order;
  });
  const variantsOf = new Map;
  for (const h of hits)
    variantsOf.set(h.it.asset.group, (variantsOf.get(h.it.asset.group) ?? 0) + 1);
  let results;
  if (collapse) {
    const taken = new Set;
    results = [];
    for (const h of hits) {
      if (taken.has(h.it.g))
        continue;
      taken.add(h.it.g);
      results.push(h.it.asset);
    }
  } else
    results = hits.map((h) => h.it.asset);
  const counts = {};
  e.facets.forEach((f, fi) => {
    counts[f.id] = new Map(f.values.map((v, vi) => [v.id, cnt[fi][vi]]));
  });
  return { results, matched: hits.length, counts, variantsOf, sort, total: e.items.length };
}
export function relaxations(e, s) {
  const out = [];
  if (s.q)
    out.push({ facet: "q", value: s.q, count: search(e, { ...s, q: "" }).results.length });
  if (s.spec)
    out.push({ facet: "spec", value: s.spec, count: search(e, { ...s, spec: "" }).results.length });
  for (const [f, vals] of Object.entries(s.filters))
    for (const v of vals) {
      const rest = vals.filter((x) => x !== v);
      const filters = { ...s.filters };
      if (rest.length)
        filters[f] = rest;
      else
        delete filters[f];
      out.push({ facet: f, value: v, count: search(e, { ...s, filters }).results.length });
    }
  return out.sort((a, b) => b.count - a.count);
}
export function paginate(list, page, perPage) {
  const size = Math.max(1, Math.floor(perPage));
  const pages = Math.max(1, Math.ceil(list.length / size));
  const p = Math.min(Math.max(1, Math.floor(page) || 1), pages);
  const start = (p - 1) * size;
  const items = list.slice(start, start + size);
  return { items, page: p, pages, perPage: size, total: list.length, from: items.length ? start + 1 : 0, to: start + items.length };
}
export function pageWindow(page, pages, around = 1) {
  const out = [];
  for (let n = 1;n <= pages; n++) {
    if (n === 1 || n === pages || Math.abs(n - page) <= around)
      out.push(n);
    else if (out[out.length - 1] !== null)
      out.push(null);
  }
  return out;
}
