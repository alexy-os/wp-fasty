import type { DataSource } from "./types";

/** Data kinds a new block binds to site data by default. */
export const AUTO_BIND_KINDS = ["post", "product", "project", "category", "topic"];
const RANK: Record<string, number> = { exact: 0, auto: 1 };

/**
 * Data sources offered for a slot kind: only those the server rates exact or auto for it (Compat, DESIGN.md D),
 * exact first; the source already bound stays listed even when it is no longer compatible (a manual mapping).
 */
export function sourcesFor(all: DataSource[], kind: string, current?: string): DataSource[] {
  const rank = (d: DataSource) => RANK[d.levels?.[kind] ?? (d.kinds.includes(kind) ? "exact" : "")] ?? 9;
  const fit = all.filter((d) => rank(d) < 9 || d.id === current);
  return fit.map((d, i) => ({ d, i })).sort((a, b) => rank(a.d) - rank(b.d) || a.i - b.i).map((x) => x.d);
}

/** The source a new block of this kind binds to: the first exact one, else the first auto one. */
export function defaultSource(all: DataSource[], kind: string): DataSource | undefined {
  if (!AUTO_BIND_KINDS.includes(kind)) return undefined;
  return sourcesFor(all, kind)[0];
}

/** "auto" when the source fills the kind through a rewrite rule, "" when exact or unknown. */
export function levelOf(d: DataSource, kind: string): string {
  return d.levels?.[kind] === "auto" ? "auto" : "";
}
