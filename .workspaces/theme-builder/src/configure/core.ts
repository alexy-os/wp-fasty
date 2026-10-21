// buildManifest(answers) → full site manifest v0.2. Pure (no fs): the CLI, the MCP server and the browser form
// pass the data (archetype example manifests + palette presets) in.
import { AA, checkTokens } from "./contrast";

export type J = Record<string, any>;

export type Answers = {
  archetype: string;
  /** Template engine; twig ⇒ no builder landings yet (schema rule). */
  engine?: "latte" | "twig";
  name: string;
  slug?: string;
  prefix?: string;
  description?: string;
  version?: string;
  tags?: string[];
  /** Fixed palette id (presets/palettes/<id>.json) … */
  palette?: string;
  /** … or free tokens, accepted only when every text/surface pair passes WCAG AA. */
  tokens?: { light: Record<string, string>; dark?: Record<string, string>; radius?: string; fonts?: { body?: string; display?: string } };
  features?: J;
  labels?: J;
  currency?: string;
  /** Extra content types on top of the archetype's own (site manifest postType shape). */
  postTypes?: J[];
  /** Subset of the archetype's landing files (tools/builder/landings/*.json); default all, [] = none. */
  landings?: string[];
  output?: J;
};

export type Data = {
  examples: Record<string, J>;
  palettes: Record<string, J>;
  schemaId?: string;
};

export class ConfigureError extends Error {
  constructor(public problems: string[]) {
    super(`answers rejected:\n  - ${problems.join("\n  - ")}`);
  }
}

/** Names the platform already uses (post types and taxonomies). */
export const RESERVED_TYPES = ["post", "page", "attachment", "revision", "nav_menu_item", "custom_css", "customize_changeset",
  "oembed_cache", "user_request", "wp_block", "wp_template", "wp_template_part", "wp_global_styles", "wp_navigation", "wp_font_family", "wp_font_face"];
export const RESERVED_TAXONOMIES = ["category", "post_tag", "nav_menu", "link_category", "post_format", "wp_theme", "wp_template_part_area"];

export const slugify = (s: string) =>
  s.normalize("NFKD").replace(/[\u0300-\u036f]/g, "").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "");
const pascal = (slug: string) => slug.split("-").map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join("");
const clone = <T>(v: T): T => JSON.parse(JSON.stringify(v));

export function buildManifest(a: Answers, data: Data): { manifest: J; warnings: string[] } {
  const problems: string[] = [];
  const warnings: string[] = [];
  const base = data.examples[a.archetype];
  if (!base) throw new ConfigureError([`unknown archetype "${a.archetype}" (have: ${Object.keys(data.examples).join(", ")})`]);
  if (!a.name || a.name.trim().length < 2) problems.push("name is required (2+ characters)");
  const m = clone(base);
  const slug = a.slug ?? slugify(a.name ?? "");
  if (!/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/.test(slug)) problems.push(`slug "${slug}" must be lowercase words joined by dashes`);
  if (slug === (m.parent?.preset ?? "latte")) problems.push(`slug "${slug}" is the parent theme`);
  const prefix = a.prefix ?? slugify(slug.split("-").pop() ?? slug).replace(/-/g, "").slice(0, 16);
  if (!/^[a-z][a-z0-9]{1,15}$/.test(prefix)) problems.push(`prefix "${prefix}" must be 2–16 lowercase letters/digits`);

  if (data.schemaId) m.$schema = data.schemaId;
  m.theme = {
    ...m.theme,
    name: a.name?.trim(),
    slug,
    textDomain: slug,
    prefix,
    namespace: pascal(slug),
    ...(a.description ? { description: a.description } : {}),
    ...(a.version ? { version: a.version } : {}),
    ...(a.tags ? { tags: a.tags } : {}),
  };

  // Brand: fixed palette, or free tokens behind the contrast gate.
  if (a.palette && a.tokens) problems.push("give either palette or tokens, not both");
  if (a.palette) {
    const p = data.palettes[a.palette];
    if (!p) problems.push(`unknown palette "${a.palette}" (have: ${Object.keys(data.palettes).join(", ")})`);
    else {
      for (const i of checkTokens({ light: p.light, dark: p.dark })) problems.push(`palette ${a.palette}: contrast ${i.scheme} ${i.fg} on ${i.bg} is ${i.ratio}:1 (< ${AA}:1 WCAG AA)`);
      m.brand = { radius: p.radius, ...(p.fonts ? { fonts: p.fonts } : {}), light: p.light, ...(p.dark ? { dark: p.dark } : {}) };
    }
  } else if (a.tokens) {
    const issues = checkTokens({ light: a.tokens.light, dark: a.tokens.dark });
    for (const i of issues) problems.push(`contrast ${i.scheme} ${i.fg} on ${i.bg} is ${i.ratio}:1 (< ${AA}:1 WCAG AA)`);
    m.brand = {
      radius: a.tokens.radius ?? m.brand?.radius ?? "0.625rem",
      ...(a.tokens.fonts ?? m.brand?.fonts ? { fonts: a.tokens.fonts ?? m.brand.fonts } : {}),
      light: a.tokens.light,
      ...(a.tokens.dark ? { dark: a.tokens.dark } : {}),
    };
  }

  if (a.features) m.features = { ...m.features, ...a.features };
  if (a.labels) m.labels = { ...m.labels, ...a.labels };
  if (a.currency) m.settings = { ...m.settings, currency: a.currency };
  if (a.output) m.output = { ...m.output, ...a.output };

  // Extra post types on top of the archetype's required ones.
  const own: J[] = m.postTypes ?? [];
  const typeNames = new Set(own.map((p) => p.name));
  const taxNames = new Set(own.flatMap((p) => Object.keys(p.taxonomies ?? {})));
  for (const pt of a.postTypes ?? []) {
    const at = `postTypes.${pt?.name ?? "?"}`;
    if (!pt?.name) { problems.push("extra post type without name"); continue; }
    if (typeNames.has(pt.name)) problems.push(`${at}: clashes with ${own.some((p) => p.name === pt.name) ? `archetype ${a.archetype} type` : "another extra type"}`);
    if (RESERVED_TYPES.includes(pt.name)) problems.push(`${at}: reserved platform post type name`);
    typeNames.add(pt.name);
    for (const tx of Object.keys(pt.taxonomies ?? {})) {
      if (taxNames.has(tx)) problems.push(`${at}: taxonomy "${tx}" clashes with an existing taxonomy`);
      if (RESERVED_TAXONOMIES.includes(tx)) problems.push(`${at}: taxonomy "${tx}" is a reserved platform name`);
      taxNames.add(tx);
    }
    own.push(clone(pt));
  }
  m.postTypes = own;
  if (own.length && m.output?.wporg && m.output?.typesPlugin === "bundled") problems.push("wporg output needs typesPlugin=companion");

  if (a.landings) {
    const known: string[] = (base.builder?.landings ?? []).map((l: J) => l.file);
    for (const f of a.landings) if (!known.includes(f)) problems.push(`landing "${f}" is not one of ${known.join(", ")}`);
    m.builder = { ...m.builder, landings: a.landings.map((file) => ({ file })) };
    if (!a.landings.length) m.builder.enabled = m.builder.enabled && false;
  }
  if (a.engine !== undefined) {
    if (a.engine !== "latte" && a.engine !== "twig") problems.push(`engine "${a.engine}" must be latte or twig`);
    m.engine = a.engine;
  }
  if (m.engine === "twig") {
    if (a.landings?.length) problems.push("engine twig has no builder landings yet (landings must be [] or omitted)");
    else if (m.builder?.enabled) {
      m.builder = { ...m.builder, enabled: false, landings: [] };
      warnings.push("engine twig: builder landings disabled (the builder compiles Latte only)");
    }
  }
  if (problems.length) throw new ConfigureError(problems);
  if (!a.palette && !a.tokens) warnings.push(`brand: kept the ${a.archetype} reference palette`);
  return { manifest: m, warnings };
}
