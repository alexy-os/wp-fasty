// Timing of the template page preview panel (TemplatePages.svelte), kept free of the DOM for tests: the panel opens
// SHOW_DELAY ms after the pointer (or the focus) reaches a page button; while open, it switches to another button
// after SWITCH_DELAY (a pointer crossing the buttons on its way to the panel does not switch it); it closes
// HIDE_DELAY ms after the pointer leaves, unless the pointer reaches the panel first (to scroll it).
export const SHOW_DELAY = 150;
export const SWITCH_DELAY = 100;
export const HIDE_DELAY = 120;

export interface Timers {
  set: (fn: () => void, ms: number) => unknown;
  clear: (handle: unknown) => void;
}
const real: Timers = { set: (fn, ms) => setTimeout(fn, ms), clear: (h) => clearTimeout(h as ReturnType<typeof setTimeout>) };

export class Peek<T> {
  current: T | null = null;
  private show: unknown = null;
  private hide: unknown = null;

  constructor(private readonly onchange: (value: T | null) => void, private readonly timers: Timers = real) {}

  /** the pointer or the focus reaches the button of `value` */
  enter(value: T): void {
    if (this.show !== null) { this.timers.clear(this.show); this.show = null; }
    if (this.current === value) return this.hold();
    // while open the hide timer keeps running: crossing a button is not a stop on it
    this.show = this.timers.set(() => { this.show = null; this.hold(); this.set(value); }, this.current !== null ? SWITCH_DELAY : SHOW_DELAY);
  }

  /** the pointer or the focus leaves a button or the panel */
  leave(): void {
    if (this.show !== null) { this.timers.clear(this.show); this.show = null; }
    if (this.current === null || this.hide !== null) return;
    this.hide = this.timers.set(() => { this.hide = null; this.set(null); }, HIDE_DELAY);
  }

  /** the pointer reached the panel: stay open */
  hold(): void {
    if (this.hide !== null) { this.timers.clear(this.hide); this.hide = null; }
  }

  /** Esc, a click on the button, the column closing: closed now */
  close(): void {
    this.cancel();
    this.set(null);
  }

  private cancel(): void {
    for (const k of ["show", "hide"] as const) if (this[k] !== null) { this.timers.clear(this[k]); this[k] = null; }
  }

  private set(value: T | null): void {
    if (value === this.current) return;
    this.current = value;
    this.onchange(value);
  }
}
