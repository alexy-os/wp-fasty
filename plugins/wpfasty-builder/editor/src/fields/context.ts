import type { Json } from "../types";

/** what a field needs besides its value: whether the block overrides the variant sample (no data bindings, ADR-007) */
export interface FieldCtx {
  /** true when the block overrides the variant sample at this path */
  overridden: (path: string[]) => boolean;
}

export type OnChange = (path: string[], value: Json | undefined) => void;

/** item fields: no overrides */
export const plain: FieldCtx = { overridden: () => false };

export const fid = (path: string[]) => "wpfb-f-" + path.join("-");
