import { describe, expect, test } from "bun:test";
import { COLUMNS, DEFAULTS, clampWidth, isTyping, parseLayout, shortcutOf, storageKey } from "../src/layout";

const key = (k: Partial<KeyboardEvent>) => shortcutOf({ key: "", code: "", ctrlKey: false, metaKey: false, altKey: false, ...k });

describe("layout", () => {
  test("widths snap to 8px and stay within min/max", () => {
    expect(clampWidth("left", 253)).toBe(256);
    expect(clampWidth("left", 251)).toBe(248);
    expect(clampWidth("left", 10)).toBe(COLUMNS.left.min);
    expect(clampWidth("right", 9999)).toBe(COLUMNS.right.max);
    expect(clampWidth("right", Number.NaN)).toBe(COLUMNS.right.def);
    for (const c of Object.values(COLUMNS)) for (const v of [c.def, c.min, c.max]) expect(v % 8).toBe(0);
  });

  test("storage key per user, shared without a user id", () => {
    expect(storageKey(7)).toBe("wpfasty-builder:layout:7");
    expect(storageKey(0)).toBe("wpfasty-builder:layout");
    expect(storageKey(undefined)).toBe("wpfasty-builder:layout");
  });

  test("stored layout is validated; garbage falls back to the defaults", () => {
    expect(parseLayout(null)).toEqual(DEFAULTS);
    expect(parseLayout("{not json")).toEqual(DEFAULTS);
    expect(parseLayout('"x"')).toEqual(DEFAULTS);
    expect(parseLayout(JSON.stringify({ left: 301, right: 10, leftCollapsed: true, rightCollapsed: "yes" })))
      .toEqual({ left: 304, right: COLUMNS.right.min, leftCollapsed: true, rightCollapsed: false });
  });

  test("shortcuts by physical key (any keyboard layout), never with modifiers", () => {
    expect(key({ key: "[", code: "BracketLeft" })).toBe("left");
    expect(key({ key: "х", code: "BracketLeft" })).toBe("left");
    expect(key({ key: "]" })).toBe("right");
    expect(key({ key: "а", code: "KeyF" })).toBe("focus");
    expect(key({ key: "F", code: "KeyF" })).toBe("focus");
    expect(key({ key: "f", code: "KeyF", ctrlKey: true })).toBeNull();
    expect(key({ key: "[", code: "BracketLeft", metaKey: true })).toBeNull();
    expect(key({ key: "g", code: "KeyG" })).toBeNull();
  });

  test("typing targets: input, textarea, select, contenteditable", () => {
    const el = (match: string | null, editable = false) =>
      ({ isContentEditable: editable, closest: (sel: string) => (match && sel.includes(match) ? {} : null) }) as unknown as EventTarget;
    expect(isTyping(el("input"))).toBe(true);
    expect(isTyping(el("textarea"))).toBe(true);
    expect(isTyping(el(null, true))).toBe(true);
    expect(isTyping(el(null))).toBe(false);
    expect(isTyping(null)).toBe(false);
  });
});
