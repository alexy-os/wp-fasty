/**
 * Canonical renderer — executes a brick definition directly and returns the
 * reference HTML. This is the executable specification of the DOM contract:
 * runtime parity tests render the generated React/Svelte/Vue components and
 * assert their SSR output is DOM-equivalent to this renderer's output.
 */

import type { Expr } from "./expr";
import type {
  AttrSpec,
  BrickDef,
  ClassSpec,
  ElementNode,
  Node,
  PartDef,
  PropDef,
  TagSpec,
} from "./model";
import { propsByName } from "./model";
import { cn } from "./cn";
import { composeRecipe } from "./recipe";
import { resolveTag, titleTag, VOID_TAGS } from "./tags";

export type PropValues = Record<string, unknown>;

export interface RenderOptions {
  /** Pre-rendered children HTML for the default slot. */
  children?: string;
  /** Extra attributes (the Attrs / rest escape hatch). */
  rest?: Record<string, string | number | boolean>;
}

interface Ctx {
  brick: BrickDef;
  part: PartDef;
  props: PropValues;
  propDefs: Map<string, PropDef>;
  item?: Record<string, unknown>;
  resolvedTag?: string;
  children?: string;
  rest?: Record<string, string | number | boolean>;
}

export class RenderError extends Error {}

// ---------------------------------------------------------------------------
// Expression evaluation
// ---------------------------------------------------------------------------

function propValue(ctx: Ctx, name: string): unknown {
  const def = ctx.propDefs.get(name);
  if (!def) throw new RenderError(`unknown prop "${name}" in ${ctx.part.name}`);
  const raw = ctx.props[name];
  if (raw !== undefined && raw !== null) return raw;
  if (def.default !== undefined) return def.default;
  switch (def.type) {
    case "string":
      return "";
    case "bool":
      return false;
    case "int":
      return 0;
    default:
      return undefined;
  }
}

export function evalExpr(expr: Expr, ctx: Ctx): string | number | boolean {
  switch (expr.kind) {
    case "lit":
      return expr.value;
    case "prop":
      return propValue(ctx, expr.name) as string | number | boolean;
    case "item": {
      if (!ctx.item) throw new RenderError(`item ref outside forEach in ${ctx.part.name}`);
      const v = ctx.item[expr.field];
      return (v ?? (typeof v === "boolean" ? false : "")) as string | number | boolean;
    }
    case "derivedRef": {
      const d = ctx.part.derived?.[expr.name];
      if (!d) throw new RenderError(`unknown derived "${expr.name}" in ${ctx.part.name}`);
      return evalExpr(d, ctx);
    }
    case "resolvedTag": {
      if (!ctx.resolvedTag) throw new RenderError(`resolvedTag used before resolution in ${ctx.part.name}`);
      return ctx.resolvedTag;
    }
    case "defaultIfEmpty": {
      const v = String(evalExpr(expr.expr, ctx) ?? "").trim();
      return v === "" ? expr.fallback : v;
    }
    case "mapOr": {
      let v = String(evalExpr(expr.expr, ctx) ?? "").trim();
      if (expr.lower) v = v.toLowerCase();
      return expr.mapping[v] ?? expr.fallback;
    }
    case "intMapOr": {
      const v = Number(evalExpr(expr.expr, ctx) ?? 0);
      return expr.mapping[v] ?? expr.fallback;
    }
    case "cond":
      return truthy(evalExpr(expr.test, ctx)) ? evalExpr(expr.then, ctx) : evalExpr(expr.else, ctx);
    case "eq":
      return evalExpr(expr.left, ctx) === evalExpr(expr.right, ctx);
    case "and":
      return expr.exprs.every((e) => truthy(evalExpr(e, ctx)));
    case "or":
      return expr.exprs.some((e) => truthy(evalExpr(e, ctx)));
    case "not":
      return !truthy(evalExpr(expr.expr, ctx));
    case "isSet": {
      const v = evalExpr(expr.expr, ctx);
      if (typeof v === "string") return v.trim() !== "";
      if (typeof v === "number") return v > 0;
      return v === true;
    }
    case "contains":
      return String(evalExpr(expr.expr, ctx) ?? "").includes(expr.substring);
    case "concat":
      return expr.parts.map((p) => String(evalExpr(p, ctx) ?? "").trim()).join("");
    case "boolToString":
      return truthy(evalExpr(expr.expr, ctx)) ? "true" : "false";
    case "intToString":
      return String(Number(evalExpr(expr.expr, ctx) ?? 0));
  }
}

function truthy(v: string | number | boolean): boolean {
  if (typeof v === "string") return v.trim() !== "";
  if (typeof v === "number") return v > 0;
  return v;
}

// ---------------------------------------------------------------------------
// Class composition
// ---------------------------------------------------------------------------

export function composeClassSpec(spec: ClassSpec, ctx: Ctx): string {
  const parts: (string | undefined)[] = [];
  if (spec.recipe) {
    const recipeId = spec.recipeId ?? ctx.part.recipeId;
    const recipe = recipeId ? ctx.brick.recipes[recipeId] : undefined;
    if (!recipe) throw new RenderError(`part ${ctx.part.name}: recipe not found (${recipeId ?? "?"})`);
    const selection: Record<string, string | undefined> = {};
    for (const [key, source] of Object.entries(spec.recipe)) {
      selection[key] =
        typeof source === "string"
          ? String(propValue(ctx, source) ?? "")
          : String(evalExpr(source, ctx) ?? "");
    }
    parts.push(composeRecipe(recipe, selection));
  }
  if (spec.staticBase) parts.push(spec.staticBase);
  for (const e of spec.extra ?? []) {
    parts.push(String(evalExpr(e, ctx) ?? ""));
  }
  for (const st of spec.state ?? []) {
    if (truthy(evalExpr(st.test, ctx))) parts.push(st.classes);
  }
  if (spec.includeClassProp !== false && ctx.propDefs.has("Class")) {
    parts.push(String(propValue(ctx, "Class") ?? ""));
  }
  return cn(...parts);
}

// ---------------------------------------------------------------------------
// HTML printing
// ---------------------------------------------------------------------------

function escapeHtml(s: string): string {
  return s
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

function escapeAttr(s: string): string {
  return escapeHtml(s).replace(/"/g, "&quot;");
}

function resolveTagSpec(tag: TagSpec, ctx: Ctx): string {
  switch (tag.kind) {
    case "static":
      return tag.tag;
    case "resolved":
      return resolveTag(String(propValue(ctx, tag.fromProp) ?? ""), tag.fallback, tag.group);
    case "heading":
      return titleTag(Number(propValue(ctx, tag.fromProp) ?? 0));
    case "mapped": {
      const t = String(evalExpr(tag.expr, ctx));
      if (!tag.tags.includes(t)) {
        throw new RenderError(`mapped tag "${t}" not in allowed set [${tag.tags.join(", ")}]`);
      }
      return t;
    }
  }
}

function renderAttrs(attrs: AttrSpec[], ctx: Ctx): string {
  const ordered: Array<[string, string | true]> = [];
  const put = (name: string, value: string | true) => {
    const idx = ordered.findIndex(([n]) => n === name);
    if (idx >= 0) ordered.splice(idx, 1);
    ordered.push([name, value]);
  };

  for (const attr of attrs) {
    switch (attr.kind) {
      case "static":
        put(attr.name, attr.value);
        break;
      case "expr": {
        if (attr.when && !truthy(evalExpr(attr.when, ctx))) break;
        const v = evalExpr(attr.expr, ctx);
        const s = typeof v === "number" ? String(v) : String(v ?? "");
        if (!attr.when && !attr.keepEmpty && s.trim() === "") break;
        put(attr.name, s);
        break;
      }
      case "bool":
        if (truthy(evalExpr(attr.expr, ctx))) put(attr.name, true);
        break;
      case "class": {
        const spec = attr.spec ?? ctx.part.classes;
        if (!spec) throw new RenderError(`part ${ctx.part.name}: attrClass() without classes contract`);
        const cls = composeClassSpec(spec, ctx);
        if (cls !== "") put("class", cls);
        break;
      }
      case "rest":
        for (const [k, v] of Object.entries(ctx.rest ?? {})) {
          if (v === false || v === undefined || v === null) continue;
          put(k, v === true ? true : String(v));
        }
        break;
    }
  }

  return ordered
    .map(([name, value]) => (value === true ? ` ${name}` : ` ${name}="${escapeAttr(value)}"`))
    .join("");
}

function renderNode(node: Node, ctx: Ctx): string {
  switch (node.kind) {
    case "element": {
      const tag = resolveTagSpec(node.tag, ctx);
      const inner: Ctx = { ...ctx, resolvedTag: tag };
      const attrs = renderAttrs(node.attrs, inner);
      const isVoid = node.void || VOID_TAGS.has(tag);
      if (isVoid) return `<${tag}${attrs}>`;
      const children = node.children.map((c) => renderNode(c, inner)).join("");
      return `<${tag}${attrs}>${children}</${tag}>`;
    }
    case "slot":
      return ctx.children ?? "";
    case "text":
      return escapeHtml(String(evalExpr(node.expr, ctx) ?? ""));
    case "when":
      if (truthy(evalExpr(node.test, ctx))) {
        return node.then.map((c) => renderNode(c, ctx)).join("");
      }
      return (node.else ?? []).map((c) => renderNode(c, ctx)).join("");
    case "forEach": {
      const items = (ctx.props[node.itemsProp] ?? []) as Record<string, unknown>[];
      return items
        .map((it) => node.body.map((c) => renderNode(c, { ...ctx, item: it })).join(""))
        .join("");
    }
  }
}

/**
 * Render one part of a brick with canonical (PascalCase) props to the
 * reference HTML string.
 */
export function renderPart(
  brick: BrickDef,
  partName: string,
  props: PropValues = {},
  options: RenderOptions = {}
): string {
  const part = brick.parts.find((p) => p.name === partName);
  if (!part) throw new RenderError(`brick ${brick.id}: unknown part "${partName}"`);
  const ctx: Ctx = {
    brick,
    part,
    props,
    propDefs: propsByName(part),
    children: options.children,
    rest: options.rest,
  };
  return renderNode(part.render, ctx);
}
