// Mirrors src/domain/tags.ts (the SSOT). DO NOT EDIT in generated output.

import { isDevEnv } from "./env";

export const BOX_TAGS = [
  "div", "section", "article", "aside", "header", "footer", "main", "nav", "figure", "search",
  "ul", "ol", "li", "dl", "dt", "dd", "fieldset",
  "table", "thead", "tbody", "tfoot", "tr", "th", "td", "caption", "colgroup", "col",
] as const;
export const TEXT_TAGS = [
  "p", "span", "h1", "h2", "h3", "h4", "h5", "h6", "blockquote", "figcaption", "pre", "code",
  "small", "strong", "em", "time", "legend", "del", "ins",
] as const;

export type BoxTag = (typeof BOX_TAGS)[number];
export type TextTag = (typeof TEXT_TAGS)[number];

export const TagGroup = { Box: "Box", Text: "Text" } as const;
export type TagGroup = (typeof TagGroup)[keyof typeof TagGroup];

const groups: Record<TagGroup, ReadonlySet<string>> = {
  Box: new Set<string>(BOX_TAGS),
  Text: new Set<string>(TEXT_TAGS),
};

const warned = new Set<string>();

export function isAllowedTag(tag: string, group: TagGroup): boolean {
  return groups[group]?.has(tag.trim().toLowerCase()) ?? false;
}

export function resolveTag(tag: string | undefined, fallback: string, group: TagGroup): string {
  const t = tag?.trim().toLowerCase() ?? "";
  if (!t) return fallback;
  if (isAllowedTag(t, group)) return t;
  if (isDevEnv()) {
    const key = `${group}:${t}`;
    if (!warned.has(key)) {
      warned.add(key);
      console.warn(`[resolveTag] "${t}" is not an allowed ${group} tag; using "${fallback}".`);
    }
  }
  return fallback;
}
