import { describe, expect, test } from "bun:test";
import { HIDE_DELAY, Peek, SHOW_DELAY, SWITCH_DELAY, type Timers } from "../src/peek";

/** a manual clock: run(ms) fires the timers due by then */
function clock() {
  let now = 0, id = 0;
  const due = new Map<number, { at: number; fn: () => void }>();
  const timers: Timers = { set: (fn, ms) => { due.set(++id, { at: now + ms, fn }); return id; }, clear: (h) => { due.delete(h as number); } };
  const run = (ms: number) => {
    now += ms;
    for (const [k, t] of [...due].sort((a, b) => a[1].at - b[1].at)) if (t.at <= now) { due.delete(k); t.fn(); }
  };
  return { timers, run };
}

describe("template page preview timing", () => {
  test("opens after the show delay, not before", () => {
    const c = clock(), seen: (string | null)[] = [];
    const p = new Peek<string>((v) => seen.push(v), c.timers);
    p.enter("home");
    c.run(SHOW_DELAY - 1);
    expect(p.current).toBeNull();
    c.run(1);
    expect(p.current).toBe("home");
    expect(seen).toEqual(["home"]);
  });

  test("a quick pass over a button never opens it", () => {
    const c = clock();
    const p = new Peek<string>(() => {}, c.timers);
    p.enter("home");
    c.run(80);
    p.leave();
    c.run(1000);
    expect(p.current).toBeNull();
  });

  test("switches after a short dwell while open, closes after the hide delay", () => {
    const c = clock();
    const p = new Peek<string>(() => {}, c.timers);
    p.enter("home"); c.run(SHOW_DELAY);
    p.leave(); p.enter("cart");
    expect(p.current).toBe("home");
    c.run(SWITCH_DELAY);
    expect(p.current).toBe("cart");
    p.leave(); c.run(HIDE_DELAY - 1);
    expect(p.current).toBe("cart");
    c.run(1);
    expect(p.current).toBeNull();
  });

  test("crossing other buttons on the way to the panel keeps the page", () => {
    const c = clock();
    const p = new Peek<string>(() => {}, c.timers);
    p.enter("home"); c.run(SHOW_DELAY);
    p.leave(); p.enter("catalog"); c.run(40); p.leave(); p.enter("browse"); c.run(40); p.leave();
    c.run(20); p.hold(); c.run(1000);
    expect(p.current).toBe("home");
  });

  test("the pointer reaching the panel keeps it open; Esc closes it now", () => {
    const c = clock();
    const p = new Peek<string>(() => {}, c.timers);
    p.enter("home"); c.run(SHOW_DELAY);
    p.leave(); c.run(50); p.hold(); c.run(1000);
    expect(p.current).toBe("home");
    p.close();
    expect(p.current).toBeNull();
    c.run(1000);
    expect(p.current).toBeNull();
  });
});
