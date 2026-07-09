/**
 * Expression IR — the closed vocabulary in which every brick's attribute and
 * class logic is written exactly once. Each target runtime prints these
 * expressions in its own idiom; the canonical renderer evaluates them to
 * define the single source of truth for the DOM contract.
 *
 * The set is intentionally tiny (KISS): decision tables (`mapOr`,
 * `intMapOr`), string defaults (`defaultIfEmpty`), boolean algebra, and a
 * conditional. Nothing here is Turing-complete on purpose.
 */

export type Expr =
  | LitExpr
  | PropExpr
  | ItemExpr
  | DerivedRefExpr
  | ResolvedTagExpr
  | DefaultIfEmptyExpr
  | MapOrExpr
  | IntMapOrExpr
  | CondExpr
  | EqExpr
  | AndExpr
  | OrExpr
  | NotExpr
  | IsSetExpr
  | ContainsExpr
  | ConcatExpr
  | BoolToStringExpr
  | IntToStringExpr;

export type LitExpr = { kind: "lit"; value: string | number | boolean };
/** Value of a component prop, referenced by canonical (PascalCase) name. */
export type PropExpr = { kind: "prop"; name: string };
/** Field of the current loop item inside a `forEach` node. */
export type ItemExpr = { kind: "item"; field: string };
/** Named derived value declared on the part (DRY across attrs). */
export type DerivedRefExpr = { kind: "derivedRef"; name: string };
/** The tag the part's root resolved to (List/ListItem need it in attr logic). */
export type ResolvedTagExpr = { kind: "resolvedTag" };
/** `trim(e) === "" ? fallback : trim(e)` — mirrors Go DefaultInputType et al. */
export type DefaultIfEmptyExpr = { kind: "defaultIfEmpty"; expr: Expr; fallback: string };
/**
 * Whitelist decision table over a trimmed string:
 * `mapping[trim(e)] ?? fallback`. `lower: true` lowercases before lookup
 * (mirrors Go tableScope / AriaHasPopup style switches).
 */
export type MapOrExpr = {
  kind: "mapOr";
  expr: Expr;
  mapping: Record<string, string>;
  fallback: string;
  lower?: boolean;
};
/** Integer decision table: `mapping[n] ?? fallback` (grid class maps, titleTag). */
export type IntMapOrExpr = {
  kind: "intMapOr";
  expr: Expr;
  mapping: Record<number, string>;
  fallback: string;
};
export type CondExpr = { kind: "cond"; test: Expr; then: Expr; else: Expr };
export type EqExpr = { kind: "eq"; left: Expr; right: Expr };
export type AndExpr = { kind: "and"; exprs: Expr[] };
export type OrExpr = { kind: "or"; exprs: Expr[] };
export type NotExpr = { kind: "not"; expr: Expr };
/**
 * Presence test with per-type semantics matching the Go originals:
 * string → `trim(v) !== ""`; bool → `v === true`; int → `v > 0`.
 */
export type IsSetExpr = { kind: "isSet"; expr: Expr };
export type ContainsExpr = { kind: "contains"; expr: Expr; substring: string };
/** Concatenation of trimmed string parts (icon `Prefix + Name`). */
export type ConcatExpr = { kind: "concat"; parts: Expr[] };
/** ARIA state stringification: always `"true"` / `"false"`, never omitted. */
export type BoolToStringExpr = { kind: "boolToString"; expr: Expr };
export type IntToStringExpr = { kind: "intToString"; expr: Expr };

// ---------------------------------------------------------------------------
// Constructors (ergonomic builder API for brick definitions)
// ---------------------------------------------------------------------------

export const lit = (value: string | number | boolean): LitExpr => ({ kind: "lit", value });
export const prop = (name: string): PropExpr => ({ kind: "prop", name });
export const item = (field: string): ItemExpr => ({ kind: "item", field });
export const derived = (name: string): DerivedRefExpr => ({ kind: "derivedRef", name });
export const resolvedTag = (): ResolvedTagExpr => ({ kind: "resolvedTag" });
export const defaultIfEmpty = (expr: Expr, fallback: string): DefaultIfEmptyExpr => ({
  kind: "defaultIfEmpty",
  expr,
  fallback,
});
export const mapOr = (
  expr: Expr,
  mapping: Record<string, string>,
  fallback: string,
  opts?: { lower?: boolean }
): MapOrExpr => ({ kind: "mapOr", expr, mapping, fallback, lower: opts?.lower });
export const intMapOr = (
  expr: Expr,
  mapping: Record<number, string>,
  fallback: string
): IntMapOrExpr => ({ kind: "intMapOr", expr, mapping, fallback });
export const cond = (test: Expr, then: Expr, elseExpr: Expr): CondExpr => ({
  kind: "cond",
  test,
  then,
  else: elseExpr,
});
export const eq = (left: Expr, right: Expr): EqExpr => ({ kind: "eq", left, right });
export const and = (...exprs: Expr[]): AndExpr => ({ kind: "and", exprs });
export const or = (...exprs: Expr[]): OrExpr => ({ kind: "or", exprs });
export const not = (expr: Expr): NotExpr => ({ kind: "not", expr });
export const isSet = (expr: Expr): IsSetExpr => ({ kind: "isSet", expr });
export const contains = (expr: Expr, substring: string): ContainsExpr => ({
  kind: "contains",
  expr,
  substring,
});
export const concat = (...parts: Expr[]): ConcatExpr => ({ kind: "concat", parts });
export const boolToString = (expr: Expr): BoolToStringExpr => ({ kind: "boolToString", expr });
export const intToString = (expr: Expr): IntToStringExpr => ({ kind: "intToString", expr });

/** Convenience: `oneOfOr(v, ["submit","reset"], "button")` whitelist. */
export const oneOfOr = (
  expr: Expr,
  allowed: string[],
  fallback: string,
  opts?: { lower?: boolean }
): MapOrExpr =>
  mapOr(expr, Object.fromEntries(allowed.map((a) => [a, a])), fallback, opts);

// ---------------------------------------------------------------------------
// Traversal
// ---------------------------------------------------------------------------

export function childExprs(expr: Expr): Expr[] {
  switch (expr.kind) {
    case "lit":
    case "prop":
    case "item":
    case "derivedRef":
    case "resolvedTag":
      return [];
    case "defaultIfEmpty":
    case "mapOr":
    case "intMapOr":
    case "not":
    case "isSet":
    case "contains":
    case "boolToString":
    case "intToString":
      return [expr.expr];
    case "cond":
      return [expr.test, expr.then, expr.else];
    case "eq":
      return [expr.left, expr.right];
    case "and":
    case "or":
      return expr.exprs;
    case "concat":
      return expr.parts;
  }
}

export function walkExpr(expr: Expr, visit: (e: Expr) => void): void {
  visit(expr);
  for (const child of childExprs(expr)) walkExpr(child, visit);
}
