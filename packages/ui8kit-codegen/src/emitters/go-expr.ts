/**
 * Prints IR expressions as Go. Non-trivial combinators delegate to the small
 * generic helpers shipped in the generated `utils` package (`expr.go`).
 */

import type { Expr } from "../domain/expr";
import { exprType, type TypeEnv } from "./common";

export interface GoPrintCtx {
  env: TypeEnv;
  propCode: (name: string) => string; // p.Variant
  itemCode: (field: string) => string; // item.Label
  derivedCode: (name: string) => string; // linkTarget(p)
  resolvedTagCode: () => string;
}

function goString(value: string): string {
  return JSON.stringify(value);
}

function isIdentityMapping(mapping: Record<string, string>): boolean {
  return Object.entries(mapping).every(([k, v]) => k === v);
}

export function printGoExpr(expr: Expr, ctx: GoPrintCtx): string {
  const p = (e: Expr) => printGoExpr(e, ctx);
  switch (expr.kind) {
    case "lit":
      return typeof expr.value === "string" ? goString(expr.value) : String(expr.value);
    case "prop":
      return ctx.propCode(expr.name);
    case "item":
      return ctx.itemCode(expr.field);
    case "derivedRef":
      return ctx.derivedCode(expr.name);
    case "resolvedTag":
      return ctx.resolvedTagCode();
    case "defaultIfEmpty":
      return `uiutils.DefaultIfEmpty(${p(expr.expr)}, ${goString(expr.fallback)})`;
    case "mapOr": {
      if (isIdentityMapping(expr.mapping)) {
        const fn = expr.lower ? "OneOfOrLower" : "OneOfOr";
        const allowed = Object.keys(expr.mapping).map(goString).join(", ");
        return `uiutils.${fn}(${p(expr.expr)}, ${goString(expr.fallback)}, ${allowed})`;
      }
      const fn = expr.lower ? "MapOrLower" : "MapOr";
      const entries = Object.entries(expr.mapping)
        .map(([k, v]) => `${goString(k)}: ${goString(v)}`)
        .join(", ");
      return `uiutils.${fn}(${p(expr.expr)}, map[string]string{${entries}}, ${goString(expr.fallback)})`;
    }
    case "intMapOr": {
      const entries = Object.entries(expr.mapping)
        .map(([k, v]) => `${k}: ${goString(v)}`)
        .join(", ");
      return `uiutils.IntMapOr(int(${p(expr.expr)}), map[int]string{${entries}}, ${goString(expr.fallback)})`;
    }
    case "cond": {
      return `uiutils.If(${p(expr.test)}, ${p(expr.then)}, ${p(expr.else)})`;
    }
    case "eq":
      return `${p(expr.left)} == ${p(expr.right)}`;
    case "and":
      return `(${expr.exprs.map(p).join(" && ")})`;
    case "or":
      return `(${expr.exprs.map(p).join(" || ")})`;
    case "not":
      return `!(${p(expr.expr)})`;
    case "isSet": {
      const t = exprType(expr.expr, ctx.env);
      if (t === "string") return `uiutils.IsSetStr(${p(expr.expr)})`;
      if (t === "int") return `(int(${p(expr.expr)}) > 0)`;
      return p(expr.expr);
    }
    case "contains":
      return `strings.Contains(${p(expr.expr)}, ${goString(expr.substring)})`;
    case "concat":
      return `uiutils.ConcatTrim(${expr.parts.map(p).join(", ")})`;
    case "boolToString":
      return `uiutils.BoolStr(${p(expr.expr)})`;
    case "intToString":
      return `strconv.Itoa(int(${p(expr.expr)}))`;
  }
}

/** Go stdlib imports required to print the given expressions. */
export function goImportsFor(exprs: Expr[]): Set<string> {
  const imports = new Set<string>();
  const visit = (e: Expr): void => {
    if (e.kind === "contains") imports.add("strings");
    if (e.kind === "intToString") imports.add("strconv");
    for (const child of childList(e)) visit(child);
  };
  for (const e of exprs) visit(e);
  return imports;
}

function childList(expr: Expr): Expr[] {
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
