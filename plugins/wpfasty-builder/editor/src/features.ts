import type {Config, Item, BlockSpec} from "./types";
export interface FeatureContext {attachment?: {id:string;label:string;json:string} | null; clearAttachment?:()=>void; onActivity?:(pending:boolean)=>void;config: Config; source: string; items: Item[]; blocks: BlockSpec[]; selected: string | null; request: <T>(path: string, init?: RequestInit) => Promise<T>; beforeOperation: () => Promise<void>; apply: (id: string, operation: (id: string) => Promise<{version: number}>) => Promise<void>}
export interface FeatureAdapter {update(context: FeatureContext): void; destroy(): void}
export type FeatureFactory = (target: HTMLElement, context: FeatureContext) => FeatureAdapter;
declare global {interface Window {WPFastyBuilderFeatures?: Record<string, FeatureFactory>}}

export const featureCopy = {panels: "Builder panels", blocks: "Blocks"};
