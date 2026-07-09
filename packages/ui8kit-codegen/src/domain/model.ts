/**
 * Domain model — bricks, parts, props, and the render-tree IR.
 *
 * A `BrickDef` is the single source of truth for one `ui/<brick>/` primitive.
 * Emitters print the same definition into Templ, React, Svelte 5, and Vue 3;
 * the canonical renderer executes it to produce the reference DOM.
 */

import type { Expr, PropExpr } from "./expr";
import type { TagGroupName } from "./tags";
import type { VariantRecipe } from "./recipe";

// ---------------------------------------------------------------------------
// Props
// ---------------------------------------------------------------------------

export type PropType = "string" | "bool" | "int" | "attrs" | "children" | "items";

export interface PropDef {
  /** Canonical PascalCase name (Go casing); runtimes derive their own. */
  name: string;
  type: PropType;
  /** CVA prop — resolved through the brick's variants.json recipe. */
  cva?: boolean;
  /** Recipe dimension key when cva (defaults to lowercased name). */
  cvaKey?: string;
  enumValues?: string[];
  default?: string | number | boolean;
  /**
   * Forwarded verbatim as a DOM attribute with the same name and semantics in
   * every runtime. TS runtimes absorb it through rest props; Go declares an
   * explicit struct field. Passthrough props must never be referenced by any
   * expression (validated).
   */
  passthrough?: boolean;
  /** DOM attribute name when it differs from lowercased prop name. */
  domName?: string;
  /** Record type for `items` props (e.g. BreadcrumbItem). */
  itemsOf?: string;
  /** Go struct field type override (e.g. `uiutils.HeadingLevel`). */
  goType?: string;
  docs?: string;
}

/** Named record used by `items` props (Breadcrumb items, Select options). */
export interface RecordTypeDef {
  name: string;
  fields: Array<{ name: string; type: "string" | "bool"; docs?: string }>;
}

// ---------------------------------------------------------------------------
// Render tree
// ---------------------------------------------------------------------------

export type TagSpec =
  | { kind: "static"; tag: string }
  /** Tag resolved from a prop against a TagGroup allow-list. */
  | { kind: "resolved"; fromProp: string; fallback: string; group: TagGroupName }
  /** h1–h6 from an int prop (default h2). */
  | { kind: "heading"; fromProp: string }
  /** Tag chosen by a string expression over a closed tag set (Break: br|wbr). */
  | { kind: "mapped"; expr: Expr; tags: string[] };

export interface StateClass {
  test: Expr;
  classes: string;
}

/**
 * The class attribute contract. Merge order is fixed and identical in every
 * runtime: recipe base → variant/size classes → static base → extra
 * expressions → state classes → caller class. The merge is Tailwind-conflict
 * aware (`cn`) in TS runtimes and the canonical renderer.
 */
export interface ClassSpec {
  /**
   * Recipe selection: dimension key → prop name (cva props) or expression
   * (icon's derived `type`).
   */
  recipe?: Record<string, string | Expr>;
  /**
   * Exported name of the shared classes helper generated for this spec
   * (defaults to `<partName>Classes` for the part's root spec).
   */
  helperName?: string;
  /** Recipe id when the part uses a non-default recipe (multi-recipe bricks). */
  recipeId?: string;
  /** Hardcoded classes for parts without a recipe (CardHeader etc.). */
  staticBase?: string;
  /** Computed class fragments (grid col maps, breadcrumb item states). */
  extra?: Expr[];
  state?: StateClass[];
  /** Append the caller's Class prop (default true when the part has one). */
  includeClassProp?: boolean;
  /** Class prop is sourced from the loop item instead (never used today). */
  fromItem?: boolean;
}

export type AttrSpec =
  /** Fixed attribute: `role="status"`. */
  | { kind: "static"; name: string; value: string }
  /**
   * Computed attribute. Omitted when `when` is false; when `when` is absent,
   * string attrs are omitted if the value is empty (trimmed) unless
   * `keepEmpty` is set.
   */
  | { kind: "expr"; name: string; expr: Expr; when?: Expr; keepEmpty?: boolean }
  /** Native boolean attribute: present when true, absent when false. */
  | { kind: "bool"; name: string; expr: Expr }
  /**
   * The merged class attribute. Without `spec`, uses the part's `classes`
   * contract and prints as a call to the shared classes helper; with an
   * inline `spec`, computed in place (loop items).
   */
  | { kind: "class"; spec?: ClassSpec }
  /** Rest/Attrs escape hatch; always spread last so the caller wins. */
  | { kind: "rest" };

export type Node =
  | ElementNode
  | SlotNode
  | TextExprNode
  | WhenNode
  | ForEachNode;

export interface ElementNode {
  kind: "element";
  tag: TagSpec;
  attrs: AttrSpec[];
  children: Node[];
  /** Void element (input, hr, br, img, source, col). */
  void?: boolean;
}

/** Default children slot. */
export interface SlotNode {
  kind: "slot";
}

export interface TextExprNode {
  kind: "text";
  expr: Expr;
}

export interface WhenNode {
  kind: "when";
  test: Expr;
  then: Node[];
  else?: Node[];
}

export interface ForEachNode {
  kind: "forEach";
  /** Items prop (type "items"). */
  itemsProp: string;
  body: Node[];
}

// ---------------------------------------------------------------------------
// Parts and bricks
// ---------------------------------------------------------------------------

export interface PartDef {
  /** Exported component name: `Button`, `CardHeader`, … */
  name: string;
  docs: string;
  props: PropDef[];
  /** Named derived values shared by several attrs (DRY in generated code). */
  derived?: Record<string, Expr>;
  /** Root render node (usually an element; icon uses a top-level switch). */
  render: Node;
  /** Recipe id consumed by this part's ClassSpec (resolved from brick). */
  recipeId?: string;
  /**
   * The part's root class contract. Emitted once as an exported
   * `<Name>Classes` helper per runtime (the documented server-composition
   * escape hatch) and referenced by `attrClass()` in the render tree.
   */
  classes?: ClassSpec;
  /** React runtime: support Radix-style `asChild` via the shared Slot. */
  asChild?: boolean;
}

export interface BrickDef {
  /** Registry id, e.g. `ui.button`. */
  id: string;
  /** Directory name under ui/. */
  dir: string;
  /** File stem (differs from dir for ui/form/controls.*). */
  file?: string;
  /** Go package name override (switch → formswitch, select → selectfield). */
  goPackage?: string;
  docs: string;
  parts: PartDef[];
  recordTypes?: RecordTypeDef[];
  /** Recipes keyed by id; loaded from colocated `*.variants.json`. */
  recipes: Record<string, VariantRecipe>;
  /** variants.json file names to copy next to generated runtime files. */
  recipeFiles: Record<string, string>;
}

export const fileStem = (brick: BrickDef): string => brick.file ?? brick.dir;

/** All props of a part indexed by canonical name. */
export function propsByName(part: PartDef): Map<string, PropDef> {
  return new Map(part.props.map((p) => [p.name, p]));
}

export function walkNodes(node: Node, visit: (n: Node) => void): void {
  visit(node);
  switch (node.kind) {
    case "element":
      for (const child of node.children) walkNodes(child, visit);
      break;
    case "when":
      for (const child of node.then) walkNodes(child, visit);
      for (const child of node.else ?? []) walkNodes(child, visit);
      break;
    case "forEach":
      for (const child of node.body) walkNodes(child, visit);
      break;
    default:
      break;
  }
}

// ---------------------------------------------------------------------------
// Node builders
// ---------------------------------------------------------------------------

export const el = (
  tag: TagSpec | string,
  attrs: AttrSpec[],
  children: Node[] = [],
  opts?: { void?: boolean }
): ElementNode => ({
  kind: "element",
  tag: typeof tag === "string" ? { kind: "static", tag } : tag,
  attrs,
  children,
  void: opts?.void,
});

export const slot = (): SlotNode => ({ kind: "slot" });
export const text = (expr: Expr): TextExprNode => ({ kind: "text", expr });
export const when = (test: Expr, then: Node[], elseNodes?: Node[]): WhenNode => ({
  kind: "when",
  test,
  then,
  else: elseNodes,
});
export const forEach = (itemsProp: string, body: Node[]): ForEachNode => ({
  kind: "forEach",
  itemsProp,
  body,
});

export const attrStatic = (name: string, value: string): AttrSpec => ({
  kind: "static",
  name,
  value,
});
export const attrExpr = (
  name: string,
  expr: Expr,
  opts?: { when?: Expr; keepEmpty?: boolean }
): AttrSpec => ({ kind: "expr", name, expr, when: opts?.when, keepEmpty: opts?.keepEmpty });
export const attrBool = (name: string, expr: Expr): AttrSpec => ({ kind: "bool", name, expr });
export const attrClass = (spec?: ClassSpec): AttrSpec => ({ kind: "class", spec });
export const attrRest = (): AttrSpec => ({ kind: "rest" });

/** Shorthand for the ubiquitous conditional string forward: `id={p.ID}` when set. */
export const fwd = (name: string, propName: string): AttrSpec =>
  attrExpr(name, { kind: "prop", name: propName } satisfies PropExpr);
