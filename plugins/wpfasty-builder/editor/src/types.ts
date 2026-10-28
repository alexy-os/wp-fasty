export type Json = string | number | boolean | null | Json[] | { [k: string]: Json };
export type Obj = { [k: string]: Json };

export interface Config {
  rest: string; nonce: string; page: number; title: string; source: string; collection: string;
  permalink: string; canPublish: boolean; editUrl: string;
  /** the current user id (per-user layout in localStorage); absent in older bootstrap data */
  user?: number;
  /** starter sets of the "New page" panel available on this site (Starters::forEditor) */
  starters?: Starter[];
  /** open the "New page" panel on load (?new=1: "Add New with Builder") */
  newPage?: boolean;
}
/** a starter set: the blocks of a demo landing, put into the Builder draft of a new page */
export interface Starter { id: string; label: string; description: string; blocks: number }
/** POST /pages: the new draft page and its Builder URL */
export interface NewPage { id: number; slug: string; url: string; blocks: number }
export interface PropSpec { name: string; type: string | { ts?: string; record?: string }; optional: boolean; docs: string }
/** a field of a catalog record (catalog `records`, from the codegen-x specs) */
export interface RecordField { name: string; type: string; optional?: boolean; docs?: string; default?: Json }
/** preview: the card images of the variant (bundled sources: sources/<id>/previews): light `src`, dark-mode `dark`
 *  (when shot), CSS size */
export interface Variant {
  id: string; set: Obj; organism: string; template: string; props: Obj; bindings: { path: string[]; data: string; pick?: string }[];
  /** paths the record of the current route fills (data `scope: "current"`: richtext title, content…); not editable */
  current?: { path: string[]; data: string; field: string }[];
  preview?: { src: string; dark?: string; width: number; height: number };
}
export interface BlockSpec {
  id: string; title: string; docs: string; group: string; axes: Record<string, string[]>; defaults: Obj;
  props: PropSpec[]; data: { as: string; kind: string; record: string; fields: Record<string, string>; scope?: "current"; sample?: Obj }[]; variants: Variant[];
}
export interface Collection { id: string; label: string; type: string; docs?: string; blocks: string[]; pages?: TemplatePage[] }
/** a page of a template collection; `preview`: its tall page shot (file pixels, 640 wide), light and dark */
export interface TemplatePage { id: string; blocks: { block: string; variant: string; props: Obj }[]; preview?: { src: string; dark?: string; width: number; height: number } }
/** a site data source; `kinds` = data kinds it fills exactly or by an auto rule, `levels` their Compat level */
export interface DataSource {
  id: string; label: string; kinds: string[]; levels?: Record<string, "exact" | "auto">; type: string; object: string; purchasable?: boolean;
}
export interface Binding { source: string; limit: number; term?: string; sale?: boolean; map?: Record<string, string> }
export interface Item { id: string; block: string; variant: string; props: Obj; bindings: Record<string, Binding> }
export interface Catalog {
  source: string; sources: { id: string; label: string }[]; collections: { id: string; label: string; type: string; blocks: number }[];
  dataSources: DataSource[]; meta: Obj; collection: Collection | null; blocks: BlockSpec[];
}
export interface Issue { code: string; path: string; message: string }
/** a rectangle in preview viewport coordinates (= overlay coordinates) */
export interface Box { top: number; left: number; width: number; height: number }
