import { describe, expect, test } from "bun:test";
import { defaultSource, levelOf, sourcesFor } from "../src/sources";
import type { DataSource } from "../src/types";

const ds = (id: string, levels: Record<string, string>, purchasable = false): DataSource =>
  ({ id, label: id, kinds: Object.keys(levels), levels, type: "post_type", object: id, purchasable });
const all = [
  ds("pages", { feature: "auto" }),
  ds("posts", { post: "exact", feature: "auto", project: "auto" }),
  ds("projects", { post: "auto", project: "exact" }),
  ds("products", { product: "exact" }, true),
  ds("categories", { category: "exact" }),
];

describe("data sources by compatibility", () => {
  test("only exact/auto sources, exact first", () => {
    expect(sourcesFor(all, "post").map((d) => d.id)).toEqual(["posts", "projects"]);
    expect(sourcesFor(all, "project").map((d) => d.id)).toEqual(["projects", "posts"]);
    expect(sourcesFor(all, "feature").map((d) => d.id)).toEqual(["pages", "posts"]);
    expect(sourcesFor(all, "stat")).toEqual([]);
  });
  test("the bound source stays even when manual", () => {
    expect(sourcesFor(all, "product", "posts").map((d) => d.id)).toEqual(["products", "posts"]);
  });
  test("default binding: first exact, only for list kinds", () => {
    expect(defaultSource(all, "post")?.id).toBe("posts");
    expect(defaultSource(all, "product")?.id).toBe("products");
    expect(defaultSource(all, "category")?.id).toBe("categories");
    expect(defaultSource(all, "feature")).toBeUndefined();
  });
  test("level marker", () => {
    expect(levelOf(all[2], "post")).toBe("auto");
    expect(levelOf(all[1], "post")).toBe("");
  });
  test("a server without levels: kinds count as exact", () => {
    const old = [{ id: "posts", label: "Posts", kinds: ["post"], type: "post_type", object: "post" }] as DataSource[];
    expect(sourcesFor(old, "post").map((d) => d.id)).toEqual(["posts"]);
  });
});
