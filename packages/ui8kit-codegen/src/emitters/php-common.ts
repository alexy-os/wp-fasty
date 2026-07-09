/**
 * Shared analysis for the two PHP template emitters (Latte, Twig): parameter
 * model, class-helper naming, and the attribute-entry classification that
 * mirrors the canonical renderer's omit/keep semantics.
 */

import type { Expr } from "../domain/expr";
import type { PartDef, PropDef } from "../domain/model";
import { phpPropName } from "../domain/naming";
import { lowerFirst } from "./common";

export interface PhpParam {
  def: PropDef;
  /** Template variable name without the sigil (`variant`, `class`, `ariaLabel`). */
  name: string;
  type: "string" | "bool" | "int";
}

/** Declared + passthrough props that become template parameters, in order. */
export function phpParams(part: PartDef): PhpParam[] {
  const out: PhpParam[] = [];
  for (const p of part.props) {
    if (p.type === "attrs" || p.type === "children") continue;
    if (p.type === "items") {
      throw new Error(`${part.name}.${p.name}: items props are not supported by PHP emitters`);
    }
    out.push({ def: p, name: phpPropName(p), type: p.type });
  }
  return out;
}

export function hasAttrsProp(part: PartDef): boolean {
  return part.props.some((p) => p.type === "attrs");
}

export function hasChildrenProp(part: PartDef): boolean {
  return part.props.some((p) => p.type === "children");
}

/** `\UI8Kit\Classes` method name for a part's classes helper. */
export function phpClassesMethod(part: PartDef): string {
  return lowerFirst(part.name) + "Classes";
}

/**
 * How one AttrSpec value prints, independent of dialect. The canonical
 * renderer's rules: `when` gates unconditionally keep the value (even
 * empty); `keepEmpty` keeps empty strings; plain string attrs are omitted
 * when trimmed-empty; ints and booleans always print.
 */
export type AttrValuePlan =
  | { kind: "always"; expr: Expr; type: "string" | "int" | "bool" }
  | { kind: "whenGate"; when: Expr; expr: Expr; type: "string" | "int" | "bool" }
  | { kind: "omitIfEmpty"; expr: Expr };
