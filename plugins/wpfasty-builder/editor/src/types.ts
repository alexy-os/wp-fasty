export type Json = string | number | boolean | null | Json[] | { [k: string]: Json };
export type Obj = { [k: string]: Json };

export interface Config {
  onboarding?: boolean;
  features?: {id: string; label: string}[];
  rest: string; nonce: string; page: number; title: string; source: string;
  permalink: string; canPublish: boolean; editUrl: string;
  /** the current user id (per-user layout in localStorage); absent in older bootstrap data */
  user?: number;
  /** starter sets of the "New page" panel available on this site (Starters::forEditor) */
  starters?: Starter[];
  /** open the "New page" panel on load (?new=1: "Add New with Builder") */
  newPage?: boolean;
}
/** a starter set: the blocks of a demo landing, put into the Builder draft of a new page */
export interface Starter { id: string; label: string; description: string; blocks: number; items?: { block: string; variant: string }[] }
/** POST /pages: the new draft page and its Builder URL */
export interface NewPage { id: number; slug: string; url: string; blocks: number }
export interface PropSpec { name: string; type: string | { ts?: string; record?: string }; optional: boolean; docs: string }
/** a field of a catalog record (catalog `records`, from the codegen-x specs) */
export interface RecordField { name: string; type: string; optional?: boolean; docs?: string; default?: Json }
/** a variant of a block-contract/v1 block: `set` = its axis values; the card shows it live (prototype.ts) */
export interface Variant {
  id: string; label?: string; set: Obj; organism: string; template: string; props: Obj;
}
export interface BlockSpec {
  contract?: { item: string; version: number; model: string };
  id: string; title: string; docs: string; group: string; axes: Record<string, string[]>; defaults: Obj;
  props: PropSpec[]; variants: Variant[];
}
/** a block of the layout: props only, no data bindings (ADR-007). `bindings` is declared (never set) only so the
 *  unchanged optional Agent module (modules/builder-agent/editor/context.ts) still type-checks; see ADR-007. */
export interface Item { id: string; block: string; variant: string; props: Obj; bindings?: undefined }
export interface Catalog {
  source: string; sources: { id: string; label: string }[];
  meta: Obj; blocks: BlockSpec[];
}
export interface Issue { code: string; path: string; message: string }
/** a rectangle in preview viewport coordinates (= overlay coordinates) */
export interface Box { top: number; left: number; width: number; height: number }
