import { describe, expect, test } from "bun:test";
import { applyMacro, expandText, filterIndex, OPEN_NOW_PREVIEW, insertMacro, isLinkField, parseMacro, relative, resolveMacro, tokenMode, type LinkIndex } from "../src/links";

const index: LinkIndex = {
  home: "http://x.test/",
  site: [{ token: "{home}", label: "Home", url: "http://x.test/" }, { token: "{posts}", label: "Posts", url: "http://x.test/blog/" }],
  pages: [{ token: "{page:about}", label: "About us", slug: "about", url: "http://x.test/about/" }],
  sections: [
    { token: "{category:news}", label: "News", slug: "news", kind: "category", url: "http://x.test/category/news/" },
    { token: "{archive:product}", label: "Products", slug: "product", kind: "archive", url: "http://x.test/shop/" },
  ],
  contact: [
    { token: "{tel}", label: "Call", url: "tel:+79001234567", source: "phone" },
    { token: "{mailto}", label: "Email", url: "#", source: "email" },
    { token: "{map}", label: "Map", url: "#", source: "address" },
  ],
  data: [
    { token: "{site:name}", label: "Site name", value: "Demo Shop" },
    { token: "{site:phone}", label: "Phone", value: "+7 900 123-45-67" },
    { token: "{site:hours}", label: "Opening hours", value: "" },
    { token: "{year}", label: "Year", value: "2026" },
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
    expect(resolveMacro("{page:team}", index)).toEqual({ error: "page team not found" });
    expect(resolveMacro("{category:old}", index)).toEqual({ error: "category old not found" });
    expect(resolveMacro("{archive:event}", index)).toEqual({ error: "archive event not found" });
    expect(resolveMacro("{page}", index)).toEqual({ error: "add a slug: {page:<slug>}" });
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
    for (const n of ["href", "url", "link", "Link", "ctaHref", "secondaryHref", "brandURL", "logoLinkUrl"]) expect(isLinkField(n, { type: "text" })).toBe(true);
    for (const n of ["title", "links", "hrefLabel", "linkText"]) expect(isLinkField(n, { type: "text" })).toBe(false);
    expect(isLinkField("href", { type: "textarea" })).toBe(false);
  });
  test("never media props", () => {
    for (const n of ["imageUrl", "imageHref", "image", "src", "imageSrc", "videoSrc", "poster", "avatarUrl", "avatar", "iconUrl", "iconHref"]) {
      expect(isLinkField(n, { type: "text" })).toBe(false);
      expect(isLinkField(n, { type: "url" as never })).toBe(false);
    }
    expect(isLinkField("posterHref", { type: "text" })).toBe(true);
  });
});

describe("filterIndex", () => {
  test("label, slug or token, per column", () => {
    expect(filterIndex(index, "ABO").pages.map((t) => t.slug)).toEqual(["about"]);
    expect(filterIndex(index, "archive").sections.map((t) => t.slug)).toEqual(["product"]);
    expect(filterIndex(index, "").site.length).toBe(2);
    expect(filterIndex(null, "x")).toEqual({ site: [], pages: [], sections: [], contact: [], data: [] });
    expect(filterIndex(index, "phone").data.map((t) => t.token)).toEqual(["{site:phone}"]);
    expect(filterIndex(index, "tel").contact.map((t) => t.token)).toEqual(["{tel}"]);
  });
});

describe("contact, site data and dates", () => {
  test("parse the new tokens", () => {
    expect(parseMacro("{site:phone}")?.arg).toBe("phone");
    expect(parseMacro("{tel}")?.kind).toBe("tel");
    expect(parseMacro("© {year}")?.prefix).toBe("© ");
    expect(parseMacro("{open-now}")?.kind).toBe("open-now");
  });
  test("contact links in link fields: the URL, or # with a warning", () => {
    expect(resolveMacro("{tel}", index)).toEqual({ url: "tel:+79001234567", shown: "tel:+79001234567" });
    expect(resolveMacro("{mailto}?subject=Hi", index)).toEqual({ url: "#?subject=Hi", shown: "#?subject=Hi", warning: "no email in the site settings: links to #" });
    expect(applyMacro("{page:about}?x=1", "{tel}")).toBe("{tel}?x=1");
  });
  test("text fields: every token expanded, empty values reported once", () => {
    expect(expandText("© {year} {site:name}", index)).toEqual({ text: "© 2026 Demo Shop", warnings: [] });
    expect(expandText("Open {site:hours}, {site:hours}. Call {site:phone}", index)).toEqual({
      text: "Open , . Call +7 900 123-45-67", warnings: ["opening hours is not set in the site settings"],
    });
    expect(expandText("{site} and {site:fax}", index)).toEqual({ text: "{site} and {site:fax}", warnings: ["add a key: {site:<key>}", "site data fax not found"] });
    expect(expandText("plain text", index)).toBeNull();
  });
  test("insertMacro: at the caret or over the selection", () => {
    expect(insertMacro("© 2025 Shop", "{year}", 2, 6)).toEqual({ value: "© {year} Shop", caret: 8 });
    expect(insertMacro("Hi ", "{site:name}")).toEqual({ value: "Hi {site:name}", caret: 14 });
    expect(insertMacro("", "{year}", 5, 9)).toEqual({ value: "{year}", caret: 6 });
  });
  test("tokenMode: link fields get links, other text fields site data, media fields nothing", () => {
    expect(tokenMode("href", { type: "text" })).toBe("link");
    expect(tokenMode("title", { type: "text" })).toBe("text");
    expect(tokenMode("body", { type: "textarea" })).toBe("text");
    expect(tokenMode("imageUrl", { type: "text" })).toBeNull();
    expect(tokenMode("iconName", { type: "text" })).toBeNull();
    expect(tokenMode("count", { type: "number" as never })).toBeNull();
  });
});

describe("{open-now}", () => {
  const hours: LinkIndex = { ...index, data: index.data.map((t) => (t.token === "{site:hours}" ? { ...t, value: "Mon–Fri 09:00–18:00" } : t)) };
  test("Site data column only (text fields), with the client-side note; not set without hours", () => {
    expect(filterIndex(hours, "open now").data).toEqual([{ token: "{open-now}", label: "Open now", value: OPEN_NOW_PREVIEW }]);
    expect(filterIndex(index, "open now").data[0]?.value).toBe("");
    const all = filterIndex(hours, "");
    expect([...all.site, ...all.pages, ...all.sections, ...all.contact].map((t) => t.token)).not.toContain("{open-now}");
  });
  test("text fields: the note, never a computed state", () => {
    expect(expandText("We are {open-now}", hours)).toEqual({ text: "We are open now / closed (client-side)", warnings: [] });
    expect(expandText("{open-now}", index)).toEqual({ text: "", warnings: ["opening hours are not set in the site settings: shows nothing"] });
  });
  test("never an href", () => {
    expect(resolveMacro("{open-now}", hours)).toEqual({ error: "{open-now} is text, not a link: use it in a text field" });
  });
});
