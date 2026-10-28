import { describe, expect, test } from "bun:test";
import { applyMacro, expandText, filterIndex, OPEN_NOW_PREVIEW, productTokens, insertMacro, isLinkField, parseMacro, relative, resolveMacro, tokenMode, type LinkIndex } from "../src/links";

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
    expect(filterIndex(null, "x")).toEqual({ site: [], pages: [], sections: [], contact: [], data: [], posts: [], terms: [], products: null, prices: null, stock: null });
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

describe("records: posts, products, terms, price and stock", () => {
  const shop: LinkIndex = {
    ...index,
    posts: [{ token: "{post:hello-world}", label: "Hello world", slug: "hello-world", url: "http://x.test/hello-world/" }],
    products: [
      { token: "{product:mug}", label: "Ceramic mug", slug: "mug", url: "http://x.test/product/mug/", amount: 1800, currency: "USD", price: "$18", in_stock: true, stock: "In stock" },
      { token: "{product:tray}", label: "Oak tray", slug: "tray", url: "http://x.test/product/tray/", amount: 6450, currency: "USD", price: "$64.50", in_stock: false, stock: "Out of stock" },
      { token: "{product:gift}", label: "Gift card", slug: "gift", url: "http://x.test/product/gift/", amount: null, currency: "USD", price: "", in_stock: true, stock: "In stock" },
    ],
    terms: [{ token: "{term:product_cat:kitchen}", label: "Kitchen", slug: "kitchen", taxonomy: "product_cat", kind: "Product category", url: "http://x.test/shop/category/kitchen/" }],
  };
  test("parse: {term:<taxonomy>:<slug>} has two parts, the others one", () => {
    expect(parseMacro("{term:product_cat:kitchen}#top")).toEqual({ kind: "term", arg: "product_cat", arg2: "kitchen", token: "{term:product_cat:kitchen}", prefix: "", suffix: "#top" });
    expect(parseMacro("{post:hello-world}")).toEqual({ kind: "post", arg: "hello-world", token: "{post:hello-world}", prefix: "", suffix: "" });
    expect(parseMacro("{posts}")?.kind).toBe("posts");
  });
  test("link fields: the URL of the post, product or term", () => {
    expect(resolveMacro("{post:hello-world}#c", shop)).toEqual({ url: "http://x.test/hello-world/#c", shown: "/hello-world/#c" });
    expect(resolveMacro("{product:tray}", shop)).toEqual({ url: "http://x.test/product/tray/", shown: "/product/tray/" });
    expect(resolveMacro("{term:product_cat:kitchen}", shop)).toEqual({ url: "http://x.test/shop/category/kitchen/", shown: "/shop/category/kitchen/" });
  });
  test("link fields: missing records, missing parts, text tokens", () => {
    expect(resolveMacro("{post:nope}", shop)).toEqual({ error: "post nope not found" });
    expect(resolveMacro("{term:product_cat:nope}", shop)).toEqual({ error: "term product_cat:nope not found" });
    expect(resolveMacro("{term:product_cat}", shop)).toEqual({ error: "add a slug: {term:product_cat:<slug>}" });
    expect(resolveMacro("{term}", shop)).toEqual({ error: "add a slug: {term:<taxonomy>:<slug>}" });
    expect(resolveMacro("{post:a:b}", shop)).toEqual({ error: "{post:a:b}: only {term:<taxonomy>:<slug>} takes two parts" });
    expect(resolveMacro("{price:mug}", shop)).toEqual({ error: "{price:…} is text, not a link: use it in a text field" });
    expect(resolveMacro("{stock:mug}", shop)).toEqual({ error: "{stock:…} is text, not a link: use it in a text field" });
    // a site without a product type: products link to #
    expect(resolveMacro("{product:mug}", { ...shop, products: null })).toEqual({ error: "this site has no product type: links to #" });
  });
  test("text fields: price and stock as the site shows them", () => {
    expect(expandText("Only {price:mug} · {stock:tray}", shop)).toEqual({ text: "Only $18 · Out of stock", warnings: [] });
    expect(expandText("{price:gift}", shop)).toEqual({ text: "", warnings: ["Gift card has no price: shows nothing"] });
    expect(expandText("{price:nope}", shop)).toEqual({ text: "{price:nope}", warnings: ["product nope not found"] });
    expect(expandText("{stock:mug}", { ...shop, products: null })?.warnings).toEqual(["this site has no product type: shows nothing"]);
  });
  test("picker columns: posts, products, terms (links); prices and stock (text); none without a product type", () => {
    const all = filterIndex(shop, "");
    expect(all.posts.map((t) => t.token)).toEqual(["{post:hello-world}"]);
    expect(all.products?.map((t) => t.token)).toEqual(["{product:mug}", "{product:tray}", "{product:gift}"]);
    expect(all.terms.map((t) => t.token)).toEqual(["{term:product_cat:kitchen}"]);
    expect(all.prices).toEqual(productTokens(shop.products!, "price"));
    expect(all.prices?.map((t) => [t.token, t.value])).toEqual([["{price:mug}", "$18"], ["{price:tray}", "$64.50"], ["{price:gift}", ""]]);
    expect(all.stock?.map((t) => t.value)).toEqual(["In stock", "Out of stock", "In stock"]);
    expect(filterIndex(shop, "tray").prices?.map((t) => t.token)).toEqual(["{price:tray}"]);
    expect(filterIndex(shop, "kitchen").terms.length).toBe(1);
    const none = filterIndex({ ...shop, products: null }, "");
    expect([none.products, none.prices, none.stock]).toEqual([null, null, null]);
    // an older server without the record lists: empty columns, no crash
    expect(filterIndex(index, "").posts).toEqual([]);
  });
});
