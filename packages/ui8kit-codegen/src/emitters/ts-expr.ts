/**
 * Prints IR expressions as TypeScript. Shared by the React, Svelte, and Vue
 * emitters — only the value-access conventions differ (`variant` vs
 * `props.variant`), injected via `TsPrintCtx`.
 *
 * Non-trivial combinators delegate to the tiny shared runtime in
 * `utils/expr.ts` (one implementation per runtime family, DRY).
 */

import type { Expr } from "../domain/expr";
import { exprType, type TypeEnv } from "./common";

export interface TsPrintCtx {
  env: TypeEnv;
  propCode: (name: string) => string;
  itemCode: (field: string) => string;
  derivedCode: (name: string) => string;
  resolvedTagCode: () => string;
  /** Names of utils/expr helpers referenced (for import generation). */
  helpers: Set<string>;
}

function isIdentityMapping(mapping: Record<string, string>): boolean {
  return Object.entries(mapping).every(([k, v]) => k === v);
}

export function printTsExpr(expr: Expr, ctx: TsPrintCtx): string {
  const p = (e: Expr) => printTsExpr(e, ctx);
  switch (expr.kind) {
    case "lit":
      return typeof expr.value === "string" ? JSON.stringify(expr.value) : String(expr.value);
    case "prop":
      return ctx.propCode(expr.name);
    case "item":
      return ctx.itemCode(expr.field);
    case "derivedRef":
      return ctx.derivedCode(expr.name);
    case "resolvedTag":
      return ctx.resolvedTagCode();
    case "defaultIfEmpty":
      ctx.helpers.add("defaultIfEmpty");
      return `defaultIfEmpty(${p(expr.expr)}, ${JSON.stringify(expr.fallback)})`;
    case "mapOr": {
      if (isIdentityMapping(expr.mapping)) {
        const helper = expr.lower ? "oneOfOrLower" : "oneOfOr";
        ctx.helpers.add(helper);
        const allowed = Object.keys(expr.mapping)
          .map((k) => JSON.stringify(k))
          .join(", ");
        return `${helper}(${p(expr.expr)}, ${JSON.stringify(expr.fallback)}, ${allowed})`;
      }
      const helper = expr.lower ? "mapOrLower" : "mapOr";
      ctx.helpers.add(helper);
      const entries = Object.entries(expr.mapping)
        .map(([k, v]) => `${JSON.stringify(k)}: ${JSON.stringify(v)}`)
        .join(", ");
      return `${helper}(${p(expr.expr)}, { ${entries} }, ${JSON.stringify(expr.fallback)})`;
    }
    case "intMapOr": {
      ctx.helpers.add("intMapOr");
      const entries = Object.entries(expr.mapping)
        .map(([k, v]) => `${k}: ${JSON.stringify(v)}`)
        .join(", ");
      return `intMapOr(${p(expr.expr)}, { ${entries} }, ${JSON.stringify(expr.fallback)})`;
    }
    case "cond":
      return `(${p(expr.test)} ? ${p(expr.then)} : ${p(expr.else)})`;
    case "eq": {
      const t = exprType(expr.left, ctx.env);
      if (t === "string") {
        ctx.helpers.add("eqStr");
        return `eqStr(${p(expr.left)}, ${p(expr.right)})`;
      }
      return `${p(expr.left)} === ${p(expr.right)}`;
    }
    case "and":
      return `(${expr.exprs.map(p).join(" && ")})`;
    case "or":
      return `(${expr.exprs.map(p).join(" || ")})`;
    case "not":
      return `!(${p(expr.expr)})`;
    case "isSet": {
      const t = exprType(expr.expr, ctx.env);
      if (t === "string") {
        ctx.helpers.add("isSetStr");
        return `isSetStr(${p(expr.expr)})`;
      }
      if (t === "int") {
        ctx.helpers.add("isSetNum");
        return `isSetNum(${p(expr.expr)})`;
      }
      return `(${p(expr.expr)} === true)`;
    }
    case "contains":
      return `(${p(expr.expr)} ?? "").includes(${JSON.stringify(expr.substring)})`;
    case "concat":
      ctx.helpers.add("concatTrim");
      return `concatTrim(${expr.parts.map(p).join(", ")})`;
    case "boolToString":
      ctx.helpers.add("boolStr");
      return `boolStr(${p(expr.expr)})`;
    case "intToString":
      return `String(${p(expr.expr)} ?? 0)`;
  }
}
