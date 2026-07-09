/**
 * Prints IR expressions as PHP — the expression dialect embedded by the
 * Latte emitter ({var ...}, n:attr arrays, {if ...}). Non-trivial
 * combinators delegate to the shared `\UI8Kit\Rt` runtime so every decision
 * table stays byte-identical with the Go/TS implementations.
 */

import type { Expr } from "../domain/expr";
import { exprType, type TypeEnv } from "./common";

export interface PhpPrintCtx {
  env: TypeEnv;
  /** `$variant`, `$class`, ... (phpPropName with $). */
  propCode: (name: string) => string;
  /** `$uiTag` for dynamic-tag roots. */
  resolvedTagCode: () => string;
  /** `$target`, `$rel`, ... ({var} preamble assignments). */
  derivedCode: (name: string) => string;
}

/** PHP single-quoted string literal. */
export function phpString(value: string): string {
  return `'${value.replace(/\\/g, "\\\\").replace(/'/g, "\\'")}'`;
}

function phpArrayStr(mapping: Record<string, string>): string {
  const entries = Object.entries(mapping)
    .map(([k, v]) => `${phpString(k)} => ${phpString(v)}`)
    .join(", ");
  return `[${entries}]`;
}

function isIdentityMapping(mapping: Record<string, string>): boolean {
  return Object.entries(mapping).every(([k, v]) => k === v);
}

export function printPhpExpr(expr: Expr, ctx: PhpPrintCtx): string {
  const p = (e: Expr) => printPhpExpr(e, ctx);
  switch (expr.kind) {
    case "lit":
      return typeof expr.value === "string" ? phpString(expr.value) : String(expr.value);
    case "prop":
      return ctx.propCode(expr.name);
    case "item":
      throw new Error("item refs are not supported by the PHP emitters (forEach parts are skipped)");
    case "derivedRef":
      return ctx.derivedCode(expr.name);
    case "resolvedTag":
      return ctx.resolvedTagCode();
    case "defaultIfEmpty":
      return `\\UI8Kit\\Rt::defaultIfEmpty(${p(expr.expr)}, ${phpString(expr.fallback)})`;
    case "mapOr": {
      if (isIdentityMapping(expr.mapping)) {
        const fn = expr.lower ? "oneOfOrLower" : "oneOfOr";
        const allowed = Object.keys(expr.mapping).map(phpString).join(", ");
        return `\\UI8Kit\\Rt::${fn}(${p(expr.expr)}, ${phpString(expr.fallback)}, ${allowed})`;
      }
      const fn = expr.lower ? "mapOrLower" : "mapOr";
      return `\\UI8Kit\\Rt::${fn}(${p(expr.expr)}, ${phpArrayStr(expr.mapping)}, ${phpString(expr.fallback)})`;
    }
    case "intMapOr": {
      const entries = Object.entries(expr.mapping)
        .map(([k, v]) => `${k} => ${phpString(v)}`)
        .join(", ");
      return `\\UI8Kit\\Rt::intMapOr(${p(expr.expr)}, [${entries}], ${phpString(expr.fallback)})`;
    }
    case "cond":
      return `((${p(expr.test)}) ? ${p(expr.then)} : ${p(expr.else)})`;
    case "eq":
      return `(${p(expr.left)} === ${p(expr.right)})`;
    case "and":
      return `(${expr.exprs.map(p).join(" && ")})`;
    case "or":
      return `(${expr.exprs.map(p).join(" || ")})`;
    case "not":
      return `!(${p(expr.expr)})`;
    case "isSet": {
      const t = exprType(expr.expr, ctx.env);
      if (t === "string") return `\\UI8Kit\\Rt::isSetStr(${p(expr.expr)})`;
      if (t === "int") return `(${p(expr.expr)} > 0)`;
      return p(expr.expr);
    }
    case "contains":
      return `str_contains(${p(expr.expr)}, ${phpString(expr.substring)})`;
    case "concat":
      return `\\UI8Kit\\Rt::concatTrim(${expr.parts.map(p).join(", ")})`;
    case "boolToString":
      return `(${p(expr.expr)} ? 'true' : 'false')`;
    case "intToString":
      return `(string) (${p(expr.expr)})`;
  }
}
