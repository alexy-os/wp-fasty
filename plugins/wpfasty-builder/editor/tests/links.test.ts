import { describe, expect, test } from "bun:test";
import { applyMacro, filterIndex, isLinkField, parseMacro, relative, resolveMacro, type LinkIndex } from "../src/links";

const index: LinkIndex = {
  home: "http://x.test/",
  site: [{ token: "{home}", label: "Home", url: "http://x.test/" }, { token: "{posts}", label: "Posts", url: "http://x.test/blog/" }],
  pages: [{ token: "{page:about}", label: "About us", slug: "about", url: "http://x.test/about/" }],
  sections: [
    { token: "{category:news}", label: "News", slug: "news", kind: "category", url: "http://x.test/category/news/" },
    { token: "{archive:product}", label: "Products", slug: "product", kind: "archive", url: "http://x.test/shop/" },
  ],
};

describe("parseMacro", () => {
  test("token with prefix and suffix", () => {
    expect(parseMacro("{page:about}#team")).toEqual({ kind: "page", arg: "about", token: "{page:about}", prefix: "", suffix: "#team" });
    expect(parseMacro("{home}")).toEqual({ kind: "home", arg: "", token: "{home}", prefix: "", suffix: "" });
    expect(parseMacro("x{archive:product}?sale=1")?.prefix).toBe("x");
  });
  test("no token: plain URLs, unknown names, slugs the server would not match", () => {
    expect(parseMacro("https://example.com/a")).toBeNull();
    expect(parseMacro("{unknown}")).toBeNull();
    expect(parseMacro("{page:Bad Slug}")).toBeNull();
  });
});

describe("applyMacro", () => {
  test("replaces the token, keeps what follows it", () => {
    expect(applyMacro("{page:about}#team", "{page:contact}")).toBe("{page:contact}#team");
    expect(applyMacro("{archive:product}?sale=1", "{category:news}")).toBe("{category:news}?sale=1");
    expect(applyMacro("{home}path/x/", "{posts}")).toBe("{posts}path/x/");
  });
  test("a value without a token becomes the token; a leading #anchor or ?query stays", () => {
    expect(applyMacro("", "{home}")).toBe("{home}");
    expect(applyMacro("https://old.example/a", "{home}")).toBe("{home}");
    expect(applyMacro("#team", "{page:about}")).toBe("{page:about}#team");
    expect(applyMacro("?q=1", "{posts}")).toBe("{posts}?q=1");
  });
});

describe("resolveMacro", () => {
  test("the URL the token resolves to, with the suffix, relative to home", () => {
    expect(resolveMacro("{page:about}#team", index)).toEqual({ url: "http://x.test/about/#team", shown: "/about/#team" });
    expect(resolveMacro("{home}", index)).toEqual({ url: "http://x.test/", shown: "/" });
    expect(resolveMacro("{archive:product}?sale=1", index)).toEqual({ url: "http://x.test/shop/?sale=1", shown: "/shop/?sale=1" });
  });
  test("missing targets", () => {
    expect(resolveMacro("{page:team}", index)).toEqual({ error: "страница team не найдена" });
    expect(resolveMacro("{category:old}", index)).toEqual({ error: "рубрика old не найдена" });
    expect(resolveMacro("{archive:event}", index)).toEqual({ error: "архив event не найден" });
    expect(resolveMacro("{page}", index)).toEqual({ error: "укажите слаг: {page:<слаг>}" });
  });
  test("no token: nothing to show", () => {
    expect(resolveMacro("/about/", index)).toBeNull();
  });
  test("relative", () => {
    expect(relative("http://x.test/a?b", "http://x.test/")).toBe("/a?b");
    expect(relative("https://other.example/a", "http://x.test/")).toBe("https://other.example/a");
  });
});

describe("isLinkField", () => {
  test("by prop name, text fields only", () => {
    for (const n of ["href", "url", "link", "Link", "ctaHref", "secondaryHref", "imageUrl", "brandURL"]) expect(isLinkField(n, { type: "text" })).toBe(true);
    for (const n of ["title", "links", "hrefLabel", "linkText"]) expect(isLinkField(n, { type: "text" })).toBe(false);
    expect(isLinkField("href", { type: "textarea" })).toBe(false);
  });
});

describe("filterIndex", () => {
  test("label, slug or token, per column", () => {
    expect(filterIndex(index, "ABO").pages.map((t) => t.slug)).toEqual(["about"]);
    expect(filterIndex(index, "archive").sections.map((t) => t.slug)).toEqual(["product"]);
    expect(filterIndex(index, "").site.length).toBe(2);
    expect(filterIndex(null, "x")).toEqual({ site: [], pages: [], sections: [] });
  });
});
