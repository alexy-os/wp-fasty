export type Json = string | number | boolean | null | Json[] | { [k: string]: Json };
export type Obj = { [k: string]: Json };

export interface Config {
  rest: string; nonce: string; page: number; title: string; source: string; collection: string;
  permalink: string; canPublish: boolean; editUrl: string;
}
export interface PropSpec { name: string; type: string | { ts?: string; record?: string }; optional: boolean; docs: string }
export interface Variant { id: string; set: Obj; organism: string; template: string; props: Obj; bindings: { path: string[]; data: string; pick?: string }[] }
export interface BlockSpec {
  id: string; title: string; docs: string; group: string; axes: Record<string, string[]>; defaults: Obj;
  props: PropSpec[]; data: { as: string; kind: string; record: string; fields: Record<string, string> }[]; variants: Variant[];
}
export interface Collection { id: string; label: string; type: string; docs?: string; blocks: string[]; pages?: { id: string; blocks: { block: string; variant: string; props: Obj }[] }[] }
export interface DataSource { id: string; label: string; kinds: string[]; type: string; object: string }
export interface Binding { source: string; limit: number; term?: string }
export interface Item { id: string; block: string; variant: string; props: Obj; bindings: Record<string, Binding> }
export interface Catalog {
  source: string; sources: { id: string; label: string }[]; collections: { id: string; label: string; type: string; blocks: number }[];
  dataSources: DataSource[]; meta: Obj; collection: Collection | null; blocks: BlockSpec[];
}
export interface Issue { code: string; path: string; message: string }
