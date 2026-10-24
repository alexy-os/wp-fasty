// Undo/redo history of the layout as JSON snapshots (pure, no Svelte: tested with bun test).

/** how many steps are kept */
export const LIMIT = 50;
/** changes with the same key within this many ms are one step (typing in one field) */
export const COALESCE_MS = 1000;

export class History<T> {
  past: string[] = [];
  future: string[] = [];
  private key: string | null = null;
  private at = 0;

  constructor(readonly limit = LIMIT, readonly window = COALESCE_MS) {}

  /**
   * Records the state before a change. A change with the same `key` as the previous one within the coalescing
   * window extends that step instead of adding one. Any new change clears the redo list.
   */
  record(before: T, key: string | null = null, now = Date.now()): void {
    const merge = key !== null && key === this.key && now - this.at <= this.window && this.past.length > 0;
    this.key = key;
    this.at = now;
    this.future = [];
    if (merge) return;
    this.past.push(JSON.stringify(before));
    if (this.past.length > this.limit) this.past.splice(0, this.past.length - this.limit);
  }

  /** the state to go back to (current goes to redo), or null */
  undo(current: T): T | null {
    const prev = this.past.pop();
    if (prev === undefined) return null;
    this.future.push(JSON.stringify(current));
    this.key = null;
    return JSON.parse(prev) as T;
  }

  /** the state to go forward to (current goes to undo), or null */
  redo(current: T): T | null {
    const next = this.future.pop();
    if (next === undefined) return null;
    this.past.push(JSON.stringify(current));
    this.key = null;
    return JSON.parse(next) as T;
  }

  /** ends the current coalescing step (e.g. the field lost focus) */
  seal(): void {
    this.key = null;
  }

  clear(): void {
    this.past = [];
    this.future = [];
    this.key = null;
  }
}

/** ids of the blocks of `next` whose content differs from `prev` (new blocks included) */
export function changedIds<B extends { id: string }>(prev: B[], next: B[]): string[] {
  const old = new Map(prev.map((b) => [b.id, JSON.stringify(b)]));
  return next.filter((b) => old.get(b.id) !== JSON.stringify(b)).map((b) => b.id);
}
