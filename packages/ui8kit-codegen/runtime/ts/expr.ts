/**
 * Tiny attribute-logic helpers used by generated components. One
 * implementation per runtime family keeps every default-resolution decision
 * table byte-identical across React, Svelte, and Vue (mirrors `expr.go`).
 */

export const trimStr = (v: string | number | null | undefined): string =>
  v === null || v === undefined ? "" : String(v).trim();

export const isSetStr = (v: string | null | undefined): boolean => trimStr(v) !== "";

export const isSetNum = (v: number | null | undefined): boolean => (v ?? 0) > 0;

/**
 * `trim(v) === "" ? fallback : trim(v)` — DefaultInputType-style defaults.
 * Generic so enum-typed props keep their literal union through the default.
 */
export const defaultIfEmpty = <T extends string>(v: T | null | undefined, fallback: T): T => {
  const t = trimStr(v) as T;
  return t === "" ? fallback : t;
};

/** Whitelist: returns `trim(v)` when in `allowed`, otherwise `fallback`. */
export const oneOfOr = <T extends string>(
  v: string | null | undefined,
  fallback: T,
  ...allowed: T[]
): T => {
  const t = trimStr(v) as T;
  return allowed.includes(t) ? t : fallback;
};

export const oneOfOrLower = <T extends string>(
  v: string | null | undefined,
  fallback: T,
  ...allowed: T[]
): T => {
  const t = trimStr(v).toLowerCase() as T;
  return allowed.includes(t) ? t : fallback;
};

/** Decision table: `mapping[trim(v)] ?? fallback`. */
export const mapOr = <T extends string>(
  v: string | null | undefined,
  mapping: Record<string, T>,
  fallback: T
): T => mapping[trimStr(v)] ?? fallback;

export const mapOrLower = <T extends string>(
  v: string | null | undefined,
  mapping: Record<string, T>,
  fallback: T
): T => mapping[trimStr(v).toLowerCase()] ?? fallback;

/** Integer decision table: `mapping[v] ?? fallback`. */
export const intMapOr = <T extends string>(
  v: number | null | undefined,
  mapping: Record<number, T>,
  fallback: T
): T => mapping[v ?? 0] ?? fallback;

/** Null-safe string equality (both sides normalized to ""). */
export const eqStr = (a: string | null | undefined, b: string | null | undefined): boolean =>
  (a ?? "") === (b ?? "");

/** ARIA state stringification: always "true"/"false", never omitted. */
export const boolStr = (v: boolean | null | undefined): "true" | "false" =>
  v ? "true" : "false";

/** Concatenation of trimmed parts (icon `Prefix + Name`). */
export const concatTrim = (...parts: (string | null | undefined)[]): string =>
  parts.map(trimStr).join("");

/** Omit-if-empty attribute value: `""` → `undefined` so the attr is dropped. */
export const orUndef = (v: string | null | undefined): string | undefined => {
  const t = trimStr(v);
  return t === "" ? undefined : String(v);
};

export type HeadingLevel = 1 | 2 | 3 | 4 | 5 | 6;

/** h1–h6 from a heading level; 0/undefined/out-of-range default to h2. */
export const titleTag = (order: HeadingLevel | number | null | undefined): string => {
  switch (order) {
    case 1:
      return "h1";
    case 3:
      return "h3";
    case 4:
      return "h4";
    case 5:
      return "h5";
    case 6:
      return "h6";
    default:
      return "h2";
  }
};
