/**
 * The block cards of the editor's left column (ADR-007): one card per block, drawn as the live prototype of its
 * selected variant, with a variant switcher (like buildytech/chat's block feed + inspector variant): one row of
 * buttons per design axis of the block (block-contract/v1 `axes`, the values its variants `set`) when the axes tell
 * every variant apart, otherwise a list of the labelled variants. Narrowed by a search and a category.
 * Pure functions (bun test).
 */
import type { BlockSpec } from "./types";
import { human } from "./util";

export interface VariantChoice { id: string; label: string; set: Record<string, string> }
export interface Axis { name: string; label: string; values: { value: string; label: string }[] }
export interface Card {
  /** the block id */
  key: string; block: string; title: string; group: string;
  /** a block-contract/v1 block: its variants are live prototypes (SimpleLine) */
  prototype: boolean;
  variants: VariantChoice[];
  /** the axes that switch variants; empty when they do not tell every variant apart (then `variants` is the switcher) */
  axes: Axis[];
}

/** the axis value set of a variant, restricted to some axes, as a stable key */
const keyOf = (set: Record<string, string>, axes: string[]) => axes.map((a) => `${a}=${set[a] ?? ""}`).join("&");

/** every block of the catalog with at least one variant, in catalog order */
export function cardsOf(blocks: BlockSpec[]): Card[] {
  return blocks.filter((b) => b.variants.length > 0).map((b) => {
    const variants = b.variants.map((v) => ({
      id: v.id, label: v.label ?? human(v.id),
      set: Object.fromEntries(Object.entries(v.set ?? {}).map(([k, x]) => [k, String(x)])),
    }));
    // only axes some variants differ on, values in the order of the block's axes
    const names = Object.keys(b.axes ?? {}).filter((a) => new Set(variants.map((v) => v.set[a])).size > 1);
    const distinct = new Set(variants.map((v) => keyOf(v.set, names))).size === variants.length;
    const axes = distinct ? names.map((name) => {
      const used = new Set(variants.map((v) => v.set[name]).filter((x): x is string => x !== undefined));
      const order = (b.axes[name] ?? []).map(String);
      const values = [...order.filter((x) => used.has(x)), ...[...used].filter((x) => !order.includes(x))];
      return { name, label: human(name), values: values.map((value) => ({ value, label: human(value) })) };
    }) : [];
    return { key: b.id, block: b.id, title: b.title, group: b.group, prototype: b.contract?.version === 1, variants, axes };
  });
}

/** the variant a card shows: the chosen one when it is a variant of the block, else the first */
export function selectedOf(card: Card, chosen: string | undefined): VariantChoice {
  return card.variants.find((v) => v.id === chosen) ?? card.variants[0]!;
}

/**
 * The variant after choosing `value` on `axis`: among the variants with that value, the one that keeps most of the
 * other axis values of the current variant (the first such in catalog order).
 */
export function pick(card: Card, current: string, axis: string, value: string): string {
  const now = selectedOf(card, current);
  let best: VariantChoice | null = null;
  let score = -1;
  for (const v of card.variants) {
    if (v.set[axis] !== value) continue;
    const s = card.axes.filter((a) => a.name !== axis && v.set[a.name] === now.set[a.name]).length;
    if (s > score) [best, score] = [v, s];
  }
  return best?.id ?? now.id;
}

/** cards matching every word of the search (block title, id, category, variant labels and ids) and the category */
export function filterCards(cards: Card[], search: string, group: string): Card[] {
  const words = search.toLowerCase().split(/\s+/).filter(Boolean);
  return cards.filter((c) => (!group || c.group === group) && words.every((w) =>
    `${c.title} ${c.block} ${c.group} ${c.variants.map((v) => `${v.label} ${v.id}`).join(" ")}`.toLowerCase().includes(w)));
}

/** the categories of the blocks with their number of blocks, by label */
export function groupsOf(blocks: BlockSpec[]): { id: string; label: string; count: number }[] {
  const count = new Map<string, number>();
  for (const b of blocks) if (b.variants.length) count.set(b.group, (count.get(b.group) ?? 0) + 1);
  return [...count].map(([id, n]) => ({ id, label: human(id), count: n })).sort((a, b) => a.label.localeCompare(b.label));
}

/** HTML5 drag data of a card: "<block>/<variant>" (the selected variant) */
export const MIME = "application/x-wpfb-variant";

export const GAP = 8;
/** narrowest card: the column holds 1 card at the default width, 2 from about 416px */
export const MIN_CARD = 200;

/** the columns for a container width and the width of one card (the prototype frame is scaled to it) */
export function columnsOf(width: number): { cols: number; cardWidth: number } {
  const w = Math.max(MIN_CARD, width);
  const cols = Math.max(1, Math.floor((w + GAP) / (MIN_CARD + GAP)));
  return { cols, cardWidth: (w - GAP * (cols - 1)) / cols };
}
