// The data model of a faceted index: the facet vocabulary (data, never code), the assets and the index the engine loads.
// Types, plus the locale list and the label lookup. An app chooses what an asset previews (the type parameter P: Stock's block or screen recipe).

/** the interface languages of the vocabulary labels; English is required, the others fall back to it */
export const LOCALES = ["en", "fr", "de", "es", "ru"] as const;
export type Locale = (typeof LOCALES)[number];
export type Text = Partial<Record<Locale, string>> & { en: string };
/** the label in a locale, else English */
export const textIn = (t: Text, locale: Locale): string => t[locale] ?? t.en;

/** a clause of a derivation rule: the value applies when any listed key matches (an app's index builder evaluates the rules; the engine never reads them) */
export type Clause = {
  block?: string[];
  group?: string[];
  theme?: string[];
  axis?: Record<string, string[]>;
  slot?: string[];
  record?: string[];
  field?: string[];
  label?: string[];
  skin?: string[];
  colour?: string[];
  screen?: string[];
  area?: string[];
  facet?: Record<string, string[]>;
};
export type Rule = { any: Clause; not?: Clause };

export type FacetValue = {
  id: string;
  label: Text;
  synonyms?: Partial<Record<Locale, string[]>>;
  rules?: Rule[];
  /** a type that has no assets yet (the type bar shows it, disabled) */
  soon?: boolean;
  /** the value's labels are drafts (a hand-written table) while the facet's other values are computed: the storefront marks it */
  draft?: boolean;
};

export type FacetUi = "tabs" | "checkbox" | "chips" | "tiles" | "swatches" | "segments" | "toggle";

export type Facet = {
  id: string;
  label: Text;
  ui: FacetUi;
  order: number;
  /** shown expanded in the facet column */
  open?: boolean;
  /** values shown before "show N more" */
  visible?: number;
  /** shared facets survive a change of type; typed ones apply to `types` only */
  scope: "shared" | "typed";
  /** the asset types the facet applies to (all when absent) */
  types?: string[];
  source: "auto" | "weak" | "human" | "behavior" | "system";
  /** the labels are weak (not reviewed by a person): the storefront marks the facet as a draft */
  draft?: boolean;
  draftNote?: Text;
  /** soft facets rank the results (selected values first) and never filter */
  mode?: "soft";
  /** how labels are derived: rules of the values, or a named source */
  derive: string;
  /** asset types that match every value (a block renders in any skin, at any radius) */
  wildcard?: string[];
  /** the values are generated from the sources (the skins, the values of an axis) */
  valuesFrom?: string;
  values: FacetValue[];
};

export type QuickFilter = { facet: string; value: string; label?: Text };
export type SortDef = { id: string; label: Text };
export type Vocabulary = { version: string; locales: Locale[]; facets: Facet[]; quick: QuickFilter[]; sorts: SortDef[] };

export type Asset<P = unknown> = {
  /** unique: block.bento/even, section.bento-grid, screen.theme-commerce-shop/cart, skin.sky */
  id: string;
  type: string;
  /** the collapse key: the variants of one block share it */
  group: string;
  title: string;
  /** the second line of a card (the variant, the screen) */
  subtitle: string;
  /** the source spec (block.bento, screen.theme-commerce-shop, organism.bento-grid, skin.sky) */
  spec: string;
  variant?: string;
  /** the order of the catalog (by topic, then name, then variant) */
  order: number;
  /** ISO date (YYYY-MM-DD) when the asset's spec entered Stock; used by the newest sort */
  added: string;
  /** facet id -> value ids ("*" = every value) */
  labels: Record<string, string[]>;
  /** the facets whose labels are weak (draft) for this asset */
  weak: string[];
  /** the facets that apply to the asset's type but gave it no label (what still needs labelling) */
  missing: string[];
  /** the free-text haystack, lower case, by weight */
  fields: { w: number; words: string[] }[];
  preview: P | null;
};

export type Index<P = unknown> = {
  version: string;
  /** sha256 of the canonical JSON of the vocabulary and the assets */
  digest: string;
  counts: Record<string, number>;
  facets: Facet[];
  quick: QuickFilter[];
  sorts: SortDef[];
  /** what the index builder left out: facets that do not discriminate (no labels, or the same labels on every asset) and values no asset carries */
  hidden?: { facets: { facet: string; reason: "empty" | "uniform" }[]; values: { facet: string; value: string }[] };
  assets: Asset<P>[];
};

