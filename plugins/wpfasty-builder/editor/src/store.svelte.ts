// Editor state on Svelte 5 runes: the blocks of the page, the selection and the undo/redo history.
// (History serialises the proxied state with JSON.stringify, so no $state.snapshot is needed.)
import { History, changedIds } from "./history";
import type { Item } from "./types";

export class EditorStore {
  items = $state<Item[]>([]);
  selected = $state<string | null>(null);
  hovered = $state<string | null>(null);
  #history = new History<Item[]>();
  #rev = $state(0);

  canUndo = $derived.by(() => (this.#rev, this.#history.past.length > 0));
  canRedo = $derived.by(() => (this.#rev, this.#history.future.length > 0));
  current = $derived(this.items.find((i) => i.id === this.selected) ?? null);
  index = $derived(this.items.findIndex((i) => i.id === this.selected));

  /** sets the blocks without history (loading the page) */
  load(items: Item[]) {
    this.items = items;
    this.#history.clear();
    this.#rev++;
  }

  /** replaces the blocks as one undo step; changes with the same `key` within a second are one step */
  commit(next: Item[], key: string | null = null) {
    this.#history.record(this.items, key);
    this.items = next;
    this.#fix();
    this.#rev++;
  }

  /** goes one step back; returns the ids of the blocks whose HTML changed, or null when there is nothing to undo */
  undo(): string[] | null {
    return this.#travel(this.#history.undo(this.items));
  }

  redo(): string[] | null {
    return this.#travel(this.#history.redo(this.items));
  }

  seal() {
    this.#history.seal();
  }

  #travel(to: Item[] | null): string[] | null {
    if (!to) return null;
    const ids = changedIds(this.items, to);
    this.items = to;
    this.#fix();
    this.#rev++;
    return ids;
  }

  #fix() {
    if (this.selected && !this.items.some((i) => i.id === this.selected)) this.selected = null;
    if (this.hovered && !this.items.some((i) => i.id === this.hovered)) this.hovered = null;
  }
}
