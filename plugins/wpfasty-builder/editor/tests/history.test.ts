import { describe, expect, test } from "bun:test";
import { History, changedIds } from "../src/history";

describe("History", () => {
  test("undo and redo walk the snapshots", () => {
    const h = new History<number[]>();
    let s = [1];
    h.record(s); s = [1, 2];
    h.record(s); s = [1, 2, 3];
    expect((s = h.undo(s)!)).toEqual([1, 2]);
    expect((s = h.undo(s)!)).toEqual([1]);
    expect(h.undo(s)).toBeNull();
    expect((s = h.redo(s)!)).toEqual([1, 2]);
    expect((s = h.redo(s)!)).toEqual([1, 2, 3]);
    expect(h.redo(s)).toBeNull();
  });

  test("a new change clears redo", () => {
    const h = new History<string>();
    h.record("a");
    expect(h.undo("b")).toBe("a");
    h.record("a");
    expect(h.future).toEqual([]);
  });

  test("same key within the window is one step, otherwise not", () => {
    const h = new History<string>(50, 1000);
    h.record("", "title", 0);
    h.record("H", "title", 500);
    h.record("He", "title", 1400);
    expect(h.past.length).toBe(1);
    h.record("Hel", "title", 2600);
    expect(h.past.length).toBe(2);
    h.record("Hell", "text", 2700);
    expect(h.past.length).toBe(3);
    h.seal();
    h.record("Hello", "text", 2800);
    expect(h.past.length).toBe(4);
    expect(h.undo("Hello!")).toBe("Hello");
  });

  test("keeps at most the limit", () => {
    const h = new History<number>(50);
    for (let i = 0; i < 80; i++) h.record(i);
    expect(h.past.length).toBe(50);
    expect(JSON.parse(h.past[0])).toBe(30);
  });

  test("changedIds reports edited and new blocks only", () => {
    const a = [{ id: "a", v: 1 }, { id: "b", v: 1 }, { id: "c", v: 1 }];
    const b = [{ id: "b", v: 2 }, { id: "a", v: 1 }, { id: "d", v: 1 }];
    expect(changedIds(a, b)).toEqual(["b", "d"]);
  });
});
