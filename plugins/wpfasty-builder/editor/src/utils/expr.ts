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
export const orUndef = <T extends string>(v: T | null | undefined): Exclude<T, ""> | undefined => {
  const t = trimStr(v);
  return t === "" ? undefined : (String(v) as Exclude<T, "">);
};

/**
 * Sync a form control's live `value` property (never an attribute, so SSR
 * markup is untouched). `undefined` leaves the current selection alone, so
 * the children's initial `selected` state stays in charge. Usable directly as
 * a Svelte action (`use:syncValue={value}`) and from Solid refs / WC renderers.
 */
export function syncValue(node: { value: string }, value?: string | null): { update(v?: string | null): void } {
  const apply = (v?: string | null): void => {
    if (v !== undefined && v !== null && node.value !== v) node.value = v;
  };
  apply(value);
  return { update: apply };
}

const DATA_IMAGE = /^data:image\/(png|jpeg|gif|webp|avif|svg\+xml)[;,]/;
/**
 * URL attribute value: "" for javascript:, vbscript: and data: (ASCII whitespace/control chars removed,
 * case-insensitive). `dataImage` (only `<img src>`) allows data:image/(png|jpeg|gif|webp|avif|svg+xml).
 */
export const safeUrl = (v: string | null | undefined, dataImage = false): string => {
  const k = String(v ?? "").replace(/[\u0000-\u0020]/g, "").toLowerCase();
  return /^(javascript|vbscript|data):/.test(k) && !(dataImage && DATA_IMAGE.test(k)) ? "" : (v ?? "");
};

/** Per-element rest rule: `img` allows data:image in `src`; `blank` lists optional attrs (lowercase) omitted when blank. */
export type RestRule = { readonly img?: boolean; readonly blank?: readonly string[] };

const URL_KEYS = new Set(["href", "src", "action", "formaction", "poster", "cite", "background", "xlink:href", "xlinkhref"]);
/** True when a rest entry must be omitted: an unsafe URL or a blank optional attribute (`value` is never in `blank`). */
export function dropRestKey(k: string, v: unknown, rule?: RestRule): boolean {
  if (typeof v !== "string") return false;
  const key = k.toLowerCase();
  if (rule?.blank?.includes(key) && v.trim() === "") return true;
  return v !== "" && URL_KEYS.has(key) && safeUrl(v, rule?.img === true && key === "src") === "";
}
/** Rest props / caller attrs without unsafe URLs and blank optional attributes; identity when clean. */
export function safeRest<T extends object>(rest: T, rule?: RestRule): T {
  const bad = Object.keys(rest).filter((k) => dropRestKey(k, (rest as Record<string, unknown>)[k], rule));
  if (bad.length === 0) return rest;
  const out = { ...rest } as Record<string, unknown>;
  for (const k of bad) delete out[k];
  return out as T;
}
