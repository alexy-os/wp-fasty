// Runes state of the IDE layout: column widths, collapsed columns (remembered in localStorage) and focus mode
// (not remembered: a reload always shows the panels).
import { COLUMNS, DEFAULTS, clampWidth, parseLayout, storageKey, type LayoutState, type Side } from "./layout";

export class PanelLayout {
  left = $state(DEFAULTS.left);
  right = $state(DEFAULTS.right);
  leftCollapsed = $state(false);
  rightCollapsed = $state(false);
  focus = $state(false);
  #key: string;

  leftOpen = $derived(!this.focus && !this.leftCollapsed);
  rightOpen = $derived(!this.focus && !this.rightCollapsed);

  constructor(user?: number | null) {
    this.#key = storageKey(user);
    let raw: string | null = null;
    try {
      raw = localStorage.getItem(this.#key);
    } catch {
      raw = null;
    }
    const s = parseLayout(raw);
    this.left = s.left;
    this.right = s.right;
    this.leftCollapsed = s.leftCollapsed;
    this.rightCollapsed = s.rightCollapsed;
  }

  width(side: Side): number {
    return side === "left" ? this.left : this.right;
  }

  setWidth(side: Side, px: number) {
    const w = clampWidth(side, px);
    if (side === "left") this.left = w;
    else this.right = w;
    this.#save();
  }

  reset(side: Side) {
    this.setWidth(side, COLUMNS[side].def);
  }

  /** collapses or opens a column; in focus mode it leaves focus mode with that column open */
  toggle(side: Side) {
    if (this.focus) {
      this.focus = false;
      if (side === "left") this.leftCollapsed = false;
      else this.rightCollapsed = false;
    } else if (side === "left") this.leftCollapsed = !this.leftCollapsed;
    else this.rightCollapsed = !this.rightCollapsed;
    this.#save();
  }

  toggleFocus() {
    this.focus = !this.focus;
  }

  #save() {
    const s: LayoutState = { left: this.left, right: this.right, leftCollapsed: this.leftCollapsed, rightCollapsed: this.rightCollapsed };
    try {
      localStorage.setItem(this.#key, JSON.stringify(s));
    } catch {
      // storage full or blocked: the layout just is not remembered
    }
  }
}
