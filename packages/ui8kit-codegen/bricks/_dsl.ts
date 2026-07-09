/**
 * Authoring prelude for brick definitions — re-exports the IR constructors
 * and provides factories for the recurring prop shapes so each definition
 * stays close to the prose of its spec.
 */

import type { BrickDef, PartDef, PropDef } from "../src/domain/model";
import type { VariantRecipe } from "../src/domain/recipe";

export * from "../src/domain/expr";
export {
  attrBool,
  attrClass,
  attrExpr,
  attrRest,
  attrStatic,
  el,
  forEach,
  fwd,
  slot,
  text,
  when,
} from "../src/domain/model";
export type { AttrSpec, BrickDef, ClassSpec, PartDef, PropDef, TagSpec } from "../src/domain/model";

// ---------------------------------------------------------------------------
// Prop factories
// ---------------------------------------------------------------------------

export const pClass = (docs = "adds Tailwind utilities"): PropDef => ({
  name: "Class",
  type: "string",
  cva: false,
  docs,
});

export const pAttrs = (): PropDef => ({ name: "Attrs", type: "attrs", cva: false });

export const pChildren = (): PropDef => ({ name: "Children", type: "children", cva: false });

export const pVariant = (docs = "sets appearance preset"): PropDef => ({
  name: "Variant",
  type: "string",
  cva: true,
  docs,
});

export const pSize = (docs = "sets density preset"): PropDef => ({
  name: "Size",
  type: "string",
  cva: true,
  docs,
});

/** A cva prop bound to an explicit recipe dimension key. */
export const pCva = (name: string, cvaKey: string, docs?: string): PropDef => ({
  name,
  type: "string",
  cva: true,
  cvaKey,
  docs,
});

export const pStr = (name: string, docs?: string): PropDef => ({
  name,
  type: "string",
  cva: false,
  docs,
});

export const pBool = (name: string, docs?: string): PropDef => ({
  name,
  type: "bool",
  cva: false,
  docs,
});

export const pInt = (name: string, docs?: string): PropDef => ({
  name,
  type: "int",
  cva: false,
  docs,
});

/** Forwarded verbatim as a DOM attribute; TS runtimes absorb it via rest. */
export const pPass = (name: string, docs?: string): PropDef => ({
  name,
  type: "string",
  cva: false,
  passthrough: true,
  docs,
});

/** The recurring ID/Role/TabIndex/AriaLabel quartet on form controls. */
export const controlPassthroughProps = (): PropDef[] => [
  pPass("ID", "element id"),
  pPass("Role", "ARIA role override"),
  pPass("TabIndex", "tab order override"),
  pPass("AriaLabel", "accessible name for unlabeled controls"),
];

// ---------------------------------------------------------------------------
// Brick assembly
// ---------------------------------------------------------------------------

export interface BrickInput {
  id: string;
  dir: string;
  file?: string;
  goPackage?: string;
  docs: string;
  parts: PartDef[];
  recordTypes?: BrickDef["recordTypes"];
  /** recipeId → [variants.json file name, parsed recipe]. */
  recipes?: Record<string, { file: string; recipe: VariantRecipe }>;
}

export function brick(input: BrickInput): BrickDef {
  const recipes: Record<string, VariantRecipe> = {};
  const recipeFiles: Record<string, string> = {};
  for (const [id, entry] of Object.entries(input.recipes ?? {})) {
    recipes[id] = entry.recipe;
    recipeFiles[id] = entry.file;
  }
  return {
    id: input.id,
    dir: input.dir,
    file: input.file,
    goPackage: input.goPackage,
    docs: input.docs,
    parts: input.parts,
    recordTypes: input.recordTypes,
    recipes,
    recipeFiles,
  };
}
