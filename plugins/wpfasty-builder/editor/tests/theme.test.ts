import { describe, expect, test } from "bun:test";
import { darkKey, parseDark } from "../src/theme";

describe("dark mode", () => {
  test("remembered per user, light unless stored as dark", () => {
    expect(darkKey(7)).toBe("wpfasty-builder:dark:7");
    expect(darkKey(0)).toBe("wpfasty-builder:dark");
    expect(darkKey(undefined)).toBe("wpfasty-builder:dark");
    expect(parseDark("1")).toBe(true);
    for (const raw of [null, "0", "", "true", "dark"]) expect(parseDark(raw)).toBe(false);
  });


});
