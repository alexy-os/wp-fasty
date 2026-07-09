/**
 * Definition validation — fails fast before any file is emitted.
 * Guards the invariants the four emitters rely on.
 */

import { walkExpr, type Expr } from "./expr";
import {
  fileStem,
  propsByName,
  walkNodes,
  type AttrSpec,
  type BrickDef,
  type PartDef,
} from "./model";
import { htmlPropName } from "./naming";
import { validateRecipe } from "./recipe";
import { TAG_GROUPS } from "./tags";

export class DefinitionError extends Error {}

function collectExprs(part: PartDef): Expr[] {
  const out: Expr[] = [];
  const fromAttr = (attr: AttrSpec) => {
    switch (attr.kind) {
      case "expr": {
        // Plain conditional forwards of passthrough props are the sanctioned
        // passthrough usage — they don't count as expression references.
        const isPlainForward =
          !attr.when &&
          attr.expr.kind === "prop" &&
          part.props.find((p) => p.name === (attr.expr as { name: string }).name)?.passthrough === true;
        if (!isPlainForward) {
          out.push(attr.expr);
          if (attr.when) out.push(attr.when);
        }
        break;
      }
      case "bool":
        out.push(attr.expr);
        break;
      case "class": {
        const spec = attr.spec;
        if (spec) {
          out.push(...(spec.extra ?? []));
          for (const st of spec.state ?? []) out.push(st.test);
        }
        break;
      }
      default:
        break;
    }
  };
  walkNodes(part.render, (node) => {
    switch (node.kind) {
      case "element":
        for (const attr of node.attrs) fromAttr(attr);
        if (node.tag.kind === "mapped") out.push(node.tag.expr);
        break;
      case "text":
        out.push(node.expr);
        break;
      case "when":
        out.push(node.test);
        break;
      default:
        break;
    }
  });
  for (const d of Object.values(part.derived ?? {})) out.push(d);
  if (part.classes) {
    out.push(...(part.classes.extra ?? []));
    for (const st of part.classes.state ?? []) out.push(st.test);
    for (const source of Object.values(part.classes.recipe ?? {})) {
      if (typeof source !== "string") out.push(source);
    }
  }
  return out;
}

export function validatePart(brick: BrickDef, part: PartDef): void {
  const fail = (msg: string): never => {
    throw new DefinitionError(`${brick.id} / ${part.name}: ${msg}`);
  };
  const props = propsByName(part);
  if (props.size !== part.props.length) fail("duplicate prop names");

  if (part.recipeId && !brick.recipes[part.recipeId]) {
    fail(`recipeId "${part.recipeId}" not found in brick recipes`);
  }

  // attrClass() without inline spec requires the part-level classes contract.
  // Passthrough forwards must use the prop's native attribute name so rest
  // props deliver the identical attribute in TS runtimes.
  walkNodes(part.render, (node) => {
    if (node.kind !== "element") return;
    for (const attr of node.attrs) {
      if (attr.kind === "class" && !attr.spec && !part.classes) {
        fail("attrClass() used without a part-level classes contract");
      }
      if (attr.kind === "expr" && !attr.when && attr.expr.kind === "prop") {
        const p = props.get(attr.expr.name);
        if (p?.passthrough && htmlPropName(p) !== attr.name) {
          fail(
            `passthrough prop ${p.name} forwarded as "${attr.name}" but its native attribute is "${htmlPropName(p)}"`
          );
        }
      }
    }
  });

  // CVA props must map onto real recipe dimensions with matching values.
  for (const p of part.props) {
    if (!p.cva) continue;
    const recipe = part.recipeId ? brick.recipes[part.recipeId] : undefined;
    if (!recipe) fail(`cva prop ${p.name} but part has no recipe`);
    const key = p.cvaKey ?? p.name.toLowerCase();
    const choices = recipe!.byKey[key];
    if (!choices) fail(`cva prop ${p.name}: recipe has no key "${key}"`);
    if (p.enumValues) {
      for (const v of p.enumValues) {
        if (choices![v] === undefined) fail(`cva prop ${p.name}: enum value "${v}" missing from recipe`);
      }
    }
  }

  // Referenced names must exist; passthrough props must stay unreferenced.
  const referencedProps = new Set<string>();
  for (const expr of collectExprs(part)) {
    walkExpr(expr, (e) => {
      if (e.kind === "prop") referencedProps.add(e.name);
      if (e.kind === "derivedRef" && !(part.derived ?? {})[e.name]) {
        fail(`unknown derived ref "${e.name}"`);
      }
    });
  }
  walkNodes(part.render, (node) => {
    if (node.kind === "element") {
      if (node.tag.kind === "resolved") {
        referencedProps.add(node.tag.fromProp);
        if (!TAG_GROUPS[node.tag.group]) fail(`unknown tag group ${node.tag.group}`);
      }
      if (node.tag.kind === "heading") referencedProps.add(node.tag.fromProp);
      for (const attr of node.attrs) {
        if (attr.kind === "class" && attr.spec?.recipe) {
          for (const source of Object.values(attr.spec.recipe)) {
            if (typeof source === "string") referencedProps.add(source);
            else walkExpr(source, (e) => {
              if (e.kind === "prop") referencedProps.add(e.name);
            });
          }
        }
      }
    }
    if (node.kind === "forEach") {
      const p = props.get(node.itemsProp);
      if (!p || p.type !== "items") fail(`forEach itemsProp "${node.itemsProp}" is not an items prop`);
      const record = (brick.recordTypes ?? []).find((r) => r.name === p!.itemsOf);
      if (!record) fail(`items prop "${node.itemsProp}" has no record type "${p!.itemsOf ?? "?"}"`);
    }
  });
  for (const source of Object.values(part.classes?.recipe ?? {})) {
    if (typeof source === "string") referencedProps.add(source);
    else walkExpr(source, (e) => {
      if (e.kind === "prop") referencedProps.add(e.name);
    });
  }
  for (const name of referencedProps) {
    const p = props.get(name);
    if (!p) fail(`expression references unknown prop "${name}"`);
    if (p!.passthrough) fail(`passthrough prop "${name}" must not be referenced by expressions`);
  }

  // A part with a slot must declare a children prop; a childless part must not.
  let hasSlot = false;
  walkNodes(part.render, (n) => {
    if (n.kind === "slot") hasSlot = true;
  });
  const hasChildrenProp = part.props.some((p) => p.type === "children");
  if (hasSlot && !hasChildrenProp) fail("render uses slot() but no children prop declared");
  if (!hasSlot && hasChildrenProp) fail("children prop declared but render has no slot()");
}

export function validateBrick(brick: BrickDef): void {
  for (const recipe of Object.values(brick.recipes)) validateRecipe(recipe);
  const names = new Set<string>();
  for (const part of brick.parts) {
    if (names.has(part.name)) {
      throw new DefinitionError(`${brick.id}: duplicate part name ${part.name}`);
    }
    names.add(part.name);
    validatePart(brick, part);
  }
  if (!/^[a-z][a-z0-9-]*$/.test(fileStem(brick))) {
    throw new DefinitionError(`${brick.id}: invalid file stem "${fileStem(brick)}"`);
  }
}

export function validateRegistry(bricks: BrickDef[]): void {
  const ids = new Set<string>();
  for (const brick of bricks) {
    if (ids.has(brick.id)) throw new DefinitionError(`duplicate brick id ${brick.id}`);
    ids.add(brick.id);
    validateBrick(brick);
  }
}
