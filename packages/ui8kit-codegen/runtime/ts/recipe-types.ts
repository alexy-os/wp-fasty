/**
 * Type helpers to derive literal unions from `*.variants.json` recipes.
 * Usage:
 *   import buttonRecipe from "./button.variants.json";
 *   type ButtonVariant = RecipeKey<typeof buttonRecipe, "variant">;
 *   // ^? "default" | "destructive" | "ghost" | ...
 */

export type VariantRecipe = {
  id: string;
  base: string;
  keys: string[];
  defaults: Record<string, string>;
  byKey: Record<string, Record<string, string>>;
};

/**
 * Extract the literal union of variant keys for a given recipe dimension.
 * Empty string (used as a fallback in some JSON recipes) is excluded so
 * consumers get only meaningful values in autocomplete.
 */
export type RecipeKey<
  R extends VariantRecipe,
  K extends keyof R["byKey"] & string,
> = Exclude<keyof R["byKey"][K] & string, "">;
