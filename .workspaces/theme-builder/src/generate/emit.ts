// Pure emitters: manifest (+ archetype) → file contents of a child theme.
import { brandDark } from "../configure/palette";

export type J = Record<string, any>;

export const DEFAULTS = {
  uri: "https://github.com/alexy-os/wp-fasty",
  author: { name: "alexy-os", uri: "https://github.com/alexy-os" },
  requires: { platform: "6.4", php: "8.2" },
  testedUpTo: "7.1",
  version: "1.0.0",
};

export const phpString = (s: string): string => s.replace(/\\/g, "\\\\").replace(/'/g, "\\'");

/** Template variables shared by every archetype template. */
export function templateVars(m: J, arch: J): J {
  const t = m.theme;
  const f = m.features ?? {};
  const blogIndexSite = (f.blogIndexTitle ?? "archive") === "site";
  const readMorePhp = m.labels?.readMore ? phpString(m.labels.readMore) : "";
  return {
    theme: { ...t, version: t.version ?? DEFAULTS.version },
    author: { ...DEFAULTS.author, ...t.author },
    requires: { ...DEFAULTS.requires, ...t.requires },
    testedUpTo: t.testedUpTo ?? DEFAULTS.testedUpTo,
    features: f,
    labels: m.labels ?? {},
    summary: arch.summary,
    copyrightYear: arch.copyrightYear,
    packageName: t.name.replace(/[^A-Za-z0-9]+/g, "_"),
    backgroundHex: f.background ? f.background.slice(1).toLowerCase() : "",
    blogIndexSite,
    readMorePhp,
    contextFilter: blogIndexSite || readMorePhp !== "",
  };
}

export function styleCss(m: J, body: string): string {
  const t = m.theme;
  const v = templateVars(m, {});
  const header = [
    ["Theme Name", t.name],
    ["Theme URI", t.uri ?? DEFAULTS.uri],
    ["Author", v.author.name],
    ["Author URI", v.author.uri],
    ["Description", t.description ?? ""],
    ["Version", v.theme.version],
    ["Requires at least", v.requires.platform],
    ["Tested up to", v.testedUpTo],
    ["Requires PHP", v.requires.php],
    ["Template", parentSlug(m)],
    ["License", "GNU General Public License v2 or later"],
    ["License URI", "https://www.gnu.org/licenses/gpl-2.0.html"],
    ["Text Domain", t.textDomain],
    ["Tags", (t.tags ?? []).join(", ")],
  ];
  return `/*\n${header.map(([k, val]) => `${k}: ${val}`).join("\n")}\n*/\n${body ? `\n${body}` : ""}`;
}

/**
 * The parent theme a child runs on: the engine's own parent build (ADR-001, K7: isolated runtimes) — `twig` for
 * engine twig (twig.zip), the preset (`latte`, latte.zip) otherwise.
 */
export function parentSlug(m: J): string {
  return m.engine === "twig" ? "twig" : (m.parent?.preset ?? "latte");
}

export function tokensCss(m: J, palette: string): string {
  const b = m.brand ?? {};
  const decl = (vars: [string, string][]) => vars.map(([k, val]) => `  --${k}: ${val};`).join("\n");
  const root: [string, string][] = [];
  if (b.fonts?.body) root.push(["font-body", b.fonts.body]);
  if (b.fonts?.display) root.push(["font-display", b.fonts.display]);
  root.push(...Object.entries<string>(b.light ?? {}));
  if (b.radius) root.push(["radius", b.radius]);
  let css = `/* ${m.theme.name} — ${palette}. All colors are tokens. */\n:root {\n${decl(root)}\n}\n`;
  // Both schemes always: brand.dark, or one derived from brand.light (a child's :root loads after the parent's .dark
  // and would otherwise override it). The front end stays light; .dark applies only under the class.
  const dark = brandDark(b);
  if (dark) css += `\n.dark {\n${decl(Object.entries<string>(dark))}\n}\n`;
  return css;
}

// ---- types/<name>.php (presentation + data module consumed by WPFasty\Types\Registry and wpfasty-types) ----

const ind = (n: number) => "    ".repeat(n);
const tr = (s: string, domain: string) => `__('${phpString(s)}', '${domain}')`;
const lit = (v: unknown): string =>
  typeof v === "string" ? `'${phpString(v)}'` : typeof v === "boolean" ? String(v) : String(v);

/** PHP array literal; values already rendered as PHP source are passed as {php: "..."}. */
function arr(o: J | unknown[], depth: number): string {
  const entries = Array.isArray(o) ? o.map((v) => [null, v] as const) : Object.entries(o);
  const inline = entries.every(([, v]) => typeof v !== "object" || v === null || "php" in (v as J));
  const val = (v: any, d: number): string =>
    v && typeof v === "object" && "php" in v ? v.php : v && typeof v === "object" ? arr(v, d) : lit(v);
  const item = ([k, v]: readonly [string | null, unknown], d: number) => `${k === null ? "" : `'${k}' => `}${val(v, d)}`;
  const flat = `[${entries.map((e) => item(e, depth)).join(", ")}]`;
  if (inline && flat.length + depth * 4 < 100) return flat;
  return `[\n${entries.map((e) => `${ind(depth + 1)}${item(e, depth + 1)},`).join("\n")}\n${ind(depth)}]`;
}

/** Body of an archetype `types/<name>.extra.php` fragment: the lines between `return [` and the final `];`. */
export function extraEntries(src: string): string {
  const lines = src.split("\n");
  const start = lines.indexOf("return [");
  const end = lines.lastIndexOf("];");
  if (start < 0 || end <= start) throw new Error("types extra fragment must contain `return [` … `];`");
  return lines.slice(start + 1, end).join("\n");
}

export function typePhp(pt: J, m: J, extra = ""): string {
  const d = m.theme.textDomain;
  const L = pt.labels;
  const labels: J = { name: { php: tr(L.plural, d) }, singular_name: { php: tr(L.singular, d) } };
  if (L.addNew) labels.add_new_item = { php: tr(L.addNew, d) };
  if (L.edit) labels.edit_item = { php: tr(L.edit, d) };
  const args: J = { labels, public: pt.public ?? true };
  if (pt.adminParent) {
    args.show_ui = true;
    args.show_in_menu = `edit.php?post_type=${pt.adminParent}`;
  }
  if (pt.archiveSlug) args.has_archive = pt.archiveSlug;
  if (pt.slug) args.rewrite = { slug: pt.slug, with_front: false };
  if (pt.icon) args.menu_icon = `dashicons-${pt.icon}`;
  if (pt.rest ?? true) args.show_in_rest = true;
  args.supports = (pt.supports ?? ["title", "editor"]).map((s: string) => (s === "order" ? "page-attributes" : s));
  if (pt.createInAdmin === false) {
    args.capability_type = "post";
    args.capabilities = { create_posts: "do_not_allow" };
    args.map_meta_cap = true;
  }
  const def: J = { name: pt.name, text_domain: d };
  const fields = Object.entries<J>(pt.fields ?? {});
  if (fields.length) def.meta_prefix = pt.metaPrefix ?? m.theme.prefix;
  def.args = args;
  const taxes = Object.entries<J>(pt.taxonomies ?? {});
  if (taxes.length)
    def.taxonomies = Object.fromEntries(taxes.map(([k, x]) => [k, {
      labels: { name: { php: tr(x.labels.plural, d) }, singular_name: { php: tr(x.labels.singular, d) } },
      public: x.public ?? true,
      hierarchical: x.hierarchical ?? true,
      show_in_rest: x.rest ?? true,
      show_admin_column: x.adminColumn ?? true,
      ...(x.slug ? { rewrite: { slug: x.slug, with_front: false } } : {}),
    }]));
  if (fields.length)
    def.fields = Object.fromEntries(fields.map(([k, f]) => [k, {
      type: f.type, label: { php: tr(f.label, d) }, ...(f.rest === false ? { show_in_rest: false } : {}),
      ...(f.metaKey ? { meta_key: f.metaKey } : {}),
      ...(f.options ? {
        options: f.options.map((o: J) => o.value),
        option_labels: Object.fromEntries(f.options.map((o: J) => [o.value, { php: tr(o.label, d) }])),
      } : {}),
    }]));
  if (pt.metabox === false) def.metabox = false;
  else if (pt.metabox) {
    def.metabox = { title: { php: tr(pt.metabox.title, d) } };
    if ((pt.metabox.position ?? "main") === "side") def.metabox.context = "side";
    if ((pt.metabox.priority ?? "default") !== "default") def.metabox.priority = pt.metabox.priority;
  }
  const caps = pt.capabilities ?? {};
  if (caps.purchasable) {
    const p = caps.purchasable;
    def.supports = { purchasable: { price_field: p.price, ...(p.sale ? { sale_field: p.sale } : {}), ...(p.stock ? { stock_field: p.stock } : {}) } };
  } else if (Object.keys(caps).length) def.supports = Object.keys(caps);
  if (pt.views) def.views = pt.views;
  if (pt.archive) {
    const a = pt.archive;
    def.archive = {
      ...(a.perPage ? { per_page: a.perPage } : {}),
      ...(a.orderBy ? { orderby: Object.fromEntries(a.orderBy.map((o: J) => [o.field === "order" ? "menu_order" : o.field, o.direction.toUpperCase()])) } : {}),
      ...(a.title ? { title: { php: tr(a.title, d) } } : {}),
      ...(a.titlePrefix === false ? { prefix: false } : {}),
    };
  }
  let body = arr(def, 0);
  if (extra) body = `${body.slice(0, -1)}${extra}\n]`;
  const pkg = m.theme.name.replace(/[^A-Za-z0-9]+/g, "_");
  return `<?php
/**
 * Post-type module: ${pt.name}. Generated by theme:generate from the site manifest (do not edit by hand);
 * presentation via WPFasty\\Types\\Registry, data registration via the wpfasty-types plugin.
 *
 * @package ${pkg}
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return ${body};
`;
}
