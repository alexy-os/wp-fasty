/**
 * Variant recipe — the shared CVA-style class map (`*.variants.json`).
 * Same JSON is consumed verbatim by all four runtimes; the engine only
 * validates and composes it for the canonical renderer.
 */

import { cn } from "./cn";

export interface VariantRecipe {
  id: string;
  base: string;
  keys: string[];
  defaults: Record<string, string>;
  byKey: Record<string, Record<string, string>>;
  meta?: Record<string, string>;
}

export class RecipeError extends Error {}

export function validateRecipe(recipe: VariantRecipe): void {
  if (!recipe.byKey) throw new RecipeError(`recipe ${recipe.id ?? "?"}: missing byKey`);
  for (const key of recipe.keys) {
    const choices = recipe.byKey[key];
    if (!choices) throw new RecipeError(`recipe ${recipe.id}: keys lists "${key}" missing from byKey`);
    for (const choice of Object.keys(choices)) {
      if (choice.trim() === "") {
        throw new RecipeError(`recipe ${recipe.id}: empty-string key in byKey.${key}`);
      }
    }
    const def = recipe.defaults?.[key];
    if (def !== undefined && choices[def] === undefined) {
      throw new RecipeError(`recipe ${recipe.id}: defaults.${key}="${def}" not present in byKey.${key}`);
    }
  }
}

/**
 * Compose recipe classes for a selection. Mirrors Go `uiutils.Compose` and TS
 * `composeRecipe`: base → per-key variant classes (selection or default) →
 * extra fragments, merged Tailwind-conflict-aware.
 */
export function composeRecipe(
  recipe: VariantRecipe,
  selection: Record<string, string | undefined>,
  ...extra: (string | undefined | null | false)[]
): string {
  const parts: string[] = [];
  if (recipe.base.trim() !== "") parts.push(recipe.base.trim());
  for (const key of recipe.keys) {
    const choices = recipe.byKey[key];
    if (!choices) continue;
    const selected = (selection[key] ?? "").trim();
    const fallback = (recipe.defaults[key] ?? "").trim();
    const choice = selected || fallback;
    if (!choice) {
      if (Object.keys(choices).length > 0) {
        throw new RecipeError(`[compose] missing default for key "${key}" (recipe id: ${recipe.id})`);
      }
      continue;
    }
    const cls = choices[choice];
    if (cls !== undefined) {
      if (cls.trim() !== "") parts.push(cls.trim());
    } else {
      throw new RecipeError(`[compose] unknown variant "${choice}" for key "${key}" (recipe id: ${recipe.id})`);
    }
  }
  return cn(...parts, ...extra);
}
