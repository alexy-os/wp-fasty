/**
 * Tag groups — the cross-runtime SSOT for allowed root tags per primitive
 * group. Mirrors ui8kit `utils/tags.json` / `utils/tags.go` / `utils/tags.ts`.
 */

export const TAG_GROUPS = {
  Layout: ["div", "section", "article", "aside", "header", "footer", "main", "nav", "figure", "search", "hgroup"],
  BoxAllowed: ["div"],
  BlockText: ["p", "blockquote", "figcaption", "address", "pre"],
  Inline: ["span", "em", "strong", "small", "abbr", "cite", "code", "kbd", "mark", "time", "data", "var", "samp", "sub", "sup", "b", "i", "u", "s", "q", "dfn", "bdo", "bdi", "ins", "del"],
  Heading: ["h1", "h2", "h3", "h4", "h5", "h6"],
  List: ["ul", "ol", "dl", "menu"],
  ListItem: ["li", "dt", "dd"],
  Form: ["form", "fieldset"],
  FormControl: ["input", "textarea", "select", "button", "option", "optgroup", "datalist", "output", "meter", "progress"],
  FormLabel: ["label", "legend"],
  Table: ["table"],
  TableSection: ["thead", "tbody", "tfoot"],
  TableRow: ["tr"],
  TableCell: ["th", "td"],
  TableColumn: ["colgroup", "col"],
  Media: ["img", "picture", "source"],
  Disclosure: ["details", "summary"],
  Container: ["div", "main", "section"],
  Stack: ["div", "ul", "ol"],
  Group: ["div", "fieldset", "dl"],
} as const;

export type TagGroupName = keyof typeof TAG_GROUPS;

export function allowedTags(group: TagGroupName): readonly string[] {
  return TAG_GROUPS[group];
}

export function isAllowedTag(tag: string, group: TagGroupName): boolean {
  return (TAG_GROUPS[group] as readonly string[]).includes(tag.trim().toLowerCase());
}

/** Returns `tag` when allowed for `group`, otherwise `fallback`. */
export function resolveTag(tag: string | undefined, fallback: string, group: TagGroupName): string {
  const t = (tag ?? "").trim().toLowerCase();
  if (t === "") return fallback;
  return isAllowedTag(t, group) ? t : fallback;
}

/** h1–h6 from a heading level; 0/out-of-range default to h2 (mirrors TitleTag). */
export function titleTag(order: number | undefined): string {
  switch (order) {
    case 1:
      return "h1";
    case 3:
      return "h3";
    case 4:
      return "h4";
    case 5:
      return "h5";
    case 6:
      return "h6";
    default:
      return "h2";
  }
}

export const VOID_TAGS = new Set([
  "area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta",
  "param", "source", "track", "wbr",
]);
