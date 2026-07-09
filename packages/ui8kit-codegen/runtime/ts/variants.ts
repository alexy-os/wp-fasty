import { cn } from "./cn";
import { isDevEnv } from "./env";
import type { RecipeKey, VariantRecipe } from "./recipe-types";

export type { RecipeKey, VariantRecipe };

export type Variants = {
  id?: string;
  base: string;
  keys: string[];
  defaults: Record<string, string>;
  byKey: Record<string, Record<string, string>>;
};

export function recipeToVariants<R extends VariantRecipe>(recipe: R): Variants {
  return {
    id: recipe.id,
    base: recipe.base,
    keys: recipe.keys,
    defaults: recipe.defaults ?? {},
    byKey: recipe.byKey,
  };
}

/**
 * Merge base, variant selections, and optional tail classes.
 * Mirrors Go `uiutils.Compose`.
 */
export function compose(
  v: Variants,
  selection: Record<string, string>,
  ...extra: (string | undefined | null | false)[]
): string {
  const parts: string[] = [];
  if (v.base.trim() !== "") {
    parts.push(v.base.trim());
  }
  for (const key of v.keys) {
    const choices = v.byKey[key];
    if (!choices) continue;
    const selected = (selection[key] ?? "").trim();
    const fallback = (v.defaults[key] ?? "").trim();
    const choice = selected || fallback;
    if (!choice) {
      if (isDevEnv() && Object.keys(choices).length > 0) {
        throw new Error(
          `[compose] missing default for key "${key}" (recipe id: ${String(v.id ?? "?")})`
        );
      }
      continue;
    }
    const cls = choices[choice];
    if (cls !== undefined) {
      if (cls.trim() !== "") parts.push(cls.trim());
    } else if (isDevEnv()) {
      throw new Error(
        `[compose] unknown variant "${choice}" for key "${key}" (recipe id: ${String(v.id ?? "?")})`
      );
    }
    // Unknown choices are silently dropped in production to avoid leaking
    // typos into the DOM as literal class names.
  }
  return cn(...parts, ...extra);
}

/** Selection type for `composeRecipe`. */
export type RecipeSelection<R extends VariantRecipe> = {
  [K in keyof R["byKey"] & string]?: RecipeKey<R, K> | undefined;
};

/** Compose classes from a typed recipe. */
export function composeRecipe<R extends VariantRecipe>(
  recipe: R,
  selection: RecipeSelection<R>,
  ...extra: (string | undefined | null | false)[]
): string {
  return compose(recipeToVariants(recipe), selection as Record<string, string>, ...extra);
}
