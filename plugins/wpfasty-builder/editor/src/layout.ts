// IDE layout of the editor: the widths of the side columns and which of them are collapsed (pure logic; the runes
// state is layout.svelte.ts). Widths snap to the 8px grid and stay within each column's min/max.

export type Side = "left" | "right";
export interface ColumnSpec { def: number; min: number; max: number }
export interface LayoutState { left: number; right: number; leftCollapsed: boolean; rightCollapsed: boolean }

export const STEP = 8;
export const COLUMNS: Record<Side, ColumnSpec> = {
  left: { def: 248, min: 200, max: 480 },
  right: { def: 336, min: 280, max: 560 },
};
export const DEFAULTS: LayoutState = { left: COLUMNS.left.def, right: COLUMNS.right.def, leftCollapsed: false, rightCollapsed: false };

/** a width on the 8px grid within the column's min/max */
export function clampWidth(side: Side, px: number): number {
  const { min, max, def } = COLUMNS[side];
  if (!Number.isFinite(px)) return def;
  return Math.min(max, Math.max(min, Math.round(px / STEP) * STEP));
}

/** the localStorage key: per user when the bootstrap data has the user id, shared otherwise */
export function storageKey(user?: number | null): string {
  return user && user > 0 ? `wpfasty-builder:layout:${user}` : "wpfasty-builder:layout";
}

/** reads a stored layout; anything missing or invalid falls back to the defaults */
export function parseLayout(raw: string | null): LayoutState {
  let v: unknown = null;
  try {
    v = raw ? JSON.parse(raw) : null;
  } catch {
    v = null;
  }
  if (!v || typeof v !== "object") return { ...DEFAULTS };
  const o = v as Record<string, unknown>;
  return {
    left: typeof o.left === "number" ? clampWidth("left", o.left) : DEFAULTS.left,
    right: typeof o.right === "number" ? clampWidth("right", o.right) : DEFAULTS.right,
    leftCollapsed: o.leftCollapsed === true,
    rightCollapsed: o.rightCollapsed === true,
  };
}

/** the key of a single-key shortcut (layout independent: by physical key, then by character) */
export function shortcutOf(e: Pick<KeyboardEvent, "key" | "code" | "ctrlKey" | "metaKey" | "altKey">): "left" | "right" | "focus" | null {
  if (e.ctrlKey || e.metaKey || e.altKey) return null;
  if (e.code === "BracketLeft" || e.key === "[") return "left";
  if (e.code === "BracketRight" || e.key === "]") return "right";
  if (e.code === "KeyF" || e.key === "f" || e.key === "F") return "focus";
  return null;
}

/** focus is in a text control: single-key shortcuts must not fire */
export function isTyping(t: EventTarget | null): boolean {
  if (!t || typeof (t as Element).closest !== "function") return false;
  const el = t as HTMLElement;
  return el.isContentEditable === true || !!el.closest("input, textarea, select, [contenteditable]:not([contenteditable='false'])");
}
