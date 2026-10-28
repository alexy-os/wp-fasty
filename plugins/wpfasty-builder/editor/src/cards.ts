/**
 * The block cards of the editor's left column: one card per variant of the blocks the site can show, narrowed by a
 * search and a category, laid out in a grid that renders only the rows in view (474 cards, a few dozen in the DOM).
 * Pure functions (bun test).
 */
import type { BlockSpec, Collection } from "./types";
import { human } from "./util";

export interface Card {
  key: string; block: string; variant: string; title: string; label: string; group: string;
  preview?: { src: string; width: number; height: number };
}

/** every variant of the blocks, in catalog order */
export function cardsOf(blocks: BlockSpec[]): Card[] {
  return blocks.flatMap((b) => b.variants.map((v) => ({
    key: `${b.id}/${v.id}`, block: b.id, variant: v.id, title: b.title, label: human(v.id), group: b.group,
    ...(v.preview ? { preview: v.preview } : {}),
  })));
}

/** cards matching every word of the search (block title, variant, ids, category) and the category ("" = all) */
export function filterCards(cards: Card[], search: string, group: string): Card[] {
  const words = search.toLowerCase().split(/\s+/).filter(Boolean);
  return cards.filter((c) => (!group || c.group === group)
    && words.every((w) => `${c.title} ${c.label} ${c.block} ${c.variant} ${c.group}`.toLowerCase().includes(w)));
}

/** the categories of the blocks (catalog groups, labelled like the catalog's group collections), by label */
export function groupsOf(blocks: BlockSpec[], collections: Pick<Collection, "id" | "label" | "type">[]): { id: string; label: string; count: number }[] {
  const label = new Map(collections.filter((c) => c.type === "group").map((c) => [c.id, c.label]));
  const count = new Map<string, number>();
  for (const b of blocks) count.set(b.group, (count.get(b.group) ?? 0) + b.variants.length);
  return [...count].map(([id, n]) => ({ id, label: label.get(id) ?? human(id), count: n })).sort((a, b) => a.label.localeCompare(b.label));
}

/** HTML5 drag data of a card: "<block>/<variant>" */
export const MIME = "application/x-wpfb-variant";

export const GAP = 8;
/** narrowest card: the column holds 1 card at the default width, 2 from about 416px */
export const MIN_CARD = 200;
/** caption under the image: block title + variant, 16px lines, 8px padding */
export const CAPTION = 48;

export interface Grid { cols: number; cardWidth: number; imageHeight: number; rowHeight: number; rows: number; height: number }

/** the grid for a container width: 16:9 images (the previews' frame) */
export function gridOf(width: number, count: number): Grid {
  const w = Math.max(MIN_CARD, width);
  const cols = Math.max(1, Math.floor((w + GAP) / (MIN_CARD + GAP)));
  const cardWidth = (w - GAP * (cols - 1)) / cols;
  const imageHeight = Math.round((cardWidth * 9) / 16);
  const rowHeight = imageHeight + CAPTION + GAP;
  const rows = Math.ceil(count / cols);
  return { cols, cardWidth, imageHeight, rowHeight, rows, height: Math.max(0, rows * rowHeight - GAP) };
}

/** the cards to render for a scroll position: whole rows in view plus `overscan` rows above and below */
export function windowOf(grid: Grid, count: number, scrollTop: number, viewport: number, overscan = 2): { from: number; to: number; offset: number } {
  if (!count) return { from: 0, to: 0, offset: 0 };
  const first = Math.max(0, Math.floor(scrollTop / grid.rowHeight) - overscan);
  const last = Math.min(grid.rows, Math.ceil((scrollTop + Math.max(viewport, grid.rowHeight)) / grid.rowHeight) + overscan);
  return { from: first * grid.cols, to: Math.min(count, last * grid.cols), offset: first * grid.rowHeight };
}

/** the row of a card, to scroll it into view when the keyboard moves past the rendered rows */
export const rowOf = (grid: Grid, index: number) => Math.floor(index / grid.cols);
