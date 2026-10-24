import type { Binding, DataSource, Json } from "../types";

/** what a field needs besides its value: override state and the block's data bindings */
export interface FieldCtx {
  /** true when the block overrides the variant sample at this path */
  overridden: (path: string[]) => boolean;
  /** bindable paths of the variant (top level only; item fields get none) → data kind */
  bindable: Record<string, string>;
  bindings: Record<string, Binding>;
  dataSources: DataSource[];
  setBinding: (path: string, binding: Binding | null) => void;
}

export type OnChange = (path: string[], value: Json | undefined) => void;

/** item fields: no overrides, no bindings */
export const plain: FieldCtx = { overridden: () => false, bindable: {}, bindings: {}, dataSources: [], setBinding: () => {} };

export const fid = (path: string[]) => "wpfb-f-" + path.join("-");
