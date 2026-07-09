/**
 * Prints IR expressions as Twig. Decision tables delegate to the ui8kit_*
 * functions registered by `UI8Kit\TwigExtension` (thin wrappers over
 * `\UI8Kit\Rt`), so the semantics are shared with the Latte/PHP printer.
 */

import type { Expr } from "../domain/expr";
import { exprType, type TypeEnv } from "./common";
import { phpString } from "./php-expr";

export interface TwigPrintCtx {
  env: TypeEnv;
  /** `variant`, `class`, ... — already `|default(...)`-wrapped by the emitter. */
  propCode: (name: string) => string;
  resolvedTagCode: () => string;
  derivedCode: (name: string) => string;
}

function twigHash(mapping: Record<string, string>): string {
  const entries = Object.entries(mapping)
    .map(([k, v]) => `${phpString(k)}: ${phpString(v)}`)
    .join(", ");
  return `{${entries}}`;
}

function isIdentityMapping(mapping: Record<string, string>): boolean {
  return Object.entries(mapping).every(([k, v]) => k === v);
}

export function printTwigExpr(expr: Expr, ctx: TwigPrintCtx): string {
  const p = (e: Expr) => printTwigExpr(e, ctx);
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
      return `ui8kit_default_if_empty(${p(expr.expr)}, ${phpString(expr.fallback)})`;
    case "mapOr": {
      const lower = expr.lower ? ", true" : "";
      if (isIdentityMapping(expr.mapping)) {
        const allowed = Object.keys(expr.mapping).map(phpString).join(", ");
        return `ui8kit_one_of_or(${p(expr.expr)}, ${phpString(expr.fallback)}, [${allowed}]${lower})`;
      }
      return `ui8kit_map_or(${p(expr.expr)}, ${twigHash(expr.mapping)}, ${phpString(expr.fallback)}${lower})`;
    }
    case "intMapOr": {
      const entries = Object.entries(expr.mapping)
        .map(([k, v]) => `${k}: ${phpString(v)}`)
        .join(", ");
      return `ui8kit_int_map_or(${p(expr.expr)}, {${entries}}, ${phpString(expr.fallback)})`;
    }
    case "cond":
      return `((${p(expr.test)}) ? ${p(expr.then)} : ${p(expr.else)})`;
    case "eq":
      return `(${p(expr.left)} == ${p(expr.right)})`;
    case "and":
      return `(${expr.exprs.map(p).join(" and ")})`;
    case "or":
      return `(${expr.exprs.map(p).join(" or ")})`;
    case "not":
      return `(not (${p(expr.expr)}))`;
    case "isSet": {
      const t = exprType(expr.expr, ctx.env);
      if (t === "string") return `ui8kit_is_set_str(${p(expr.expr)})`;
      if (t === "int") return `(${p(expr.expr)} > 0)`;
      return p(expr.expr);
    }
    case "contains":
      return `(${phpString(expr.substring)} in ${p(expr.expr)})`;
    case "concat":
      return `ui8kit_concat_trim([${expr.parts.map(p).join(", ")}])`;
    case "boolToString":
      return `((${p(expr.expr)}) ? 'true' : 'false')`;
    case "intToString":
      return `((${p(expr.expr)}) ~ '')`;
  }
}
