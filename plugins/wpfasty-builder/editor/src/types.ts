export type Json = string | number | boolean | null | Json[] | { [k: string]: Json };
export type Obj = { [k: string]: Json };

export interface Config {
  rest: string; nonce: string; page: number; title: string; source: string; collection: string;
  permalink: string; canPublish: boolean; editUrl: string;
  /** the current user id (per-user layout in localStorage); absent in older bootstrap data */
  user?: number;
}
export interface PropSpec { name: string; type: string | { ts?: string; record?: string }; optional: boolean; docs: string }
/** a field of a catalog record (catalog `records`, from the codegen-x specs) */
export interface RecordField { name: string; type: string; optional?: boolean; docs?: string; default?: Json }
export interface Variant { id: string; set: Obj; organism: string; template: string; props: Obj; bindings: { path: string[]; data: string; pick?: string }[] }
export interface BlockSpec {
  id: string; title: string; docs: string; group: string; axes: Record<string, string[]>; defaults: Obj;
  props: PropSpec[]; data: { as: string; kind: string; record: string; fields: Record<string, string> }[]; variants: Variant[];
}
export interface Collection { id: string; label: string; type: string; docs?: string; blocks: string[]; pages?: { id: string; blocks: { block: string; variant: string; props: Obj }[] }[] }
export interface DataSource { id: string; label: string; kinds: string[]; type: string; object: string }
export interface Binding { source: string; limit: number; term?: string; sale?: boolean }
export interface Item { id: string; block: string; variant: string; props: Obj; bindings: Record<string, Binding> }
export interface Catalog {
  source: string; sources: { id: string; label: string }[]; collections: { id: string; label: string; type: string; blocks: number }[];
  dataSources: DataSource[]; meta: Obj; collection: Collection | null; blocks: BlockSpec[];
}
export interface Issue { code: string; path: string; message: string }
/** a rectangle in preview viewport coordinates (= overlay coordinates) */
export interface Box { top: number; left: number; width: number; height: number }
