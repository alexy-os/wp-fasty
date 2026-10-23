// Source gate: no raw HTML tag where a UI8Kit primitive exists; Box/Text `tag` only from the HTML5 allowlist.
// Lexical scan of the native syntax of each runtime (comments, <script>/<style> bodies, string literals of TS skipped):
//   .tsx/.jsx  JSX           .svelte  markup outside <script>/<style>     .vue  inside <template>
//   .templ     templ markup  .twig / .latte / .j2 / .jinja / .jinja2 / .html  template markup
import { readFileSync, readdirSync, statSync } from "node:fs";
import path from "node:path";

/** Element -> primitive (UI8Kit 3 rules table, ui8kit-primitives.mdc §1). */
export const PRIMITIVE_OF = {
  div: "Box", section: "Box", article: "Box", aside: "Box", header: "Box", footer: "Box", main: "Box", nav: "Box", figure: "Box", search: "Box",
  ul: "Box", ol: "Box", li: "Box", dl: "Box", dt: "Box", dd: "Box", fieldset: "Box", table: "Box", thead: "Box", tbody: "Box", tfoot: "Box",
  tr: "Box", th: "Box", td: "Box", caption: "Box", colgroup: "Box", col: "Box",
  p: "Text", span: "Text", h1: "Text", h2: "Text", h3: "Text", h4: "Text", h5: "Text", h6: "Text", blockquote: "Text", figcaption: "Text",
  pre: "Text", code: "Text", small: "Text", strong: "Text", em: "Text", time: "Text", legend: "Text",
  a: "Link|Button", button: "Button|Switch", label: "Label", form: "Form", input: "Input|Checkbox|Radio", textarea: "Textarea", select: "Select",
  option: "SelectOption", optgroup: "SelectOptGroup", img: "Image", picture: "Picture", source: "Source", dialog: "Dialog", details: "Disclosure",
  summary: "Summary", meter: "Meter", progress: "Progress", hr: "Separator",
};
export const BOX_TAGS = ["div", "section", "article", "aside", "header", "footer", "main", "nav", "figure", "search", "ul", "ol", "li", "dl", "dt", "dd", "fieldset", "table", "thead", "tbody", "tfoot", "tr", "th", "td", "caption", "colgroup", "col"];
export const TEXT_TAGS = ["p", "span", "h1", "h2", "h3", "h4", "h5", "h6", "blockquote", "figcaption", "pre", "code", "small", "strong", "em", "time", "legend"];

const EXT = { ".tsx": "jsx", ".jsx": "jsx", ".svelte": "svelte", ".vue": "vue", ".templ": "templ", ".twig": "twig", ".latte": "latte", ".j2": "jinja", ".jinja": "jinja", ".jinja2": "jinja", ".html": "html" };

const blank = (s) => s.replace(/[^\n]/g, " ");
/** Keep offsets/lines, blank out what is not markup. */
function markupOf(src, kind) {
  let s = src;
  if (kind === "jsx") s = s.replace(/\/\*[^]*?\*\/|\/\/[^\n]*/g, blank).replace(/(["'`])(?:\\.|(?!\1)[^\\\n])*\1/g, blank);
  if (kind === "svelte" || kind === "vue" || kind === "html") s = s.replace(/<!--[^]*?-->/g, blank).replace(/(<(script|style)\b[^>]*>)([^]*?)(<\/\2>)/g, (_, a, _t, body, z) => blank(a) + blank(body) + blank(z));
  if (kind === "vue") { const m = /<template\b[^>]*>([^]*)<\/template>/.exec(s); s = m ? blank(s.slice(0, m.index)) + s.slice(m.index, m.index + m[0].length) + blank(s.slice(m.index + m[0].length)) : blank(s); }
  if (kind === "templ") s = s.replace(/\/\*[^]*?\*\/|\/\/[^\n]*/g, blank);
  if (kind === "twig" || kind === "jinja") s = s.replace(/\{#[^]*?#\}/g, blank).replace(/<!--[^]*?-->/g, blank);
  if (kind === "latte") s = s.replace(/\{\*[^]*?\*\}/g, blank).replace(/<!--[^]*?-->/g, blank);
  return s;
}

/** Opening tags of HTML elements in markup; for JSX only where an element can start (not `Array<string>`). */
const OPEN = { jsx: /(?<=^|[\s(){}?:,=>&|[]|return)\s*<([a-z][a-z0-9-]*)(?=[\s/>])/gm, other: /<([a-z][a-z0-9-]*)(?=[\s/>])/g };
const TAG_ATTR = /\b(?:tag|Tag)\s*(?:=\s*\{?\s*|:\s*)["']([^"'{}$]+)["']/g;

const lineOf = (s, i) => s.slice(0, i).split("\n").length;

export function checkSourceText(src, file, { allow = [] } = {}) {
  const kind = EXT[path.extname(file)];
  if (!kind) return [];
  const m = markupOf(src, kind), out = [], allowed = new Set(allow);
  for (const x of m.matchAll(kind === "jsx" ? OPEN.jsx : OPEN.other)) {
    const tag = x[1], prim = PRIMITIVE_OF[tag];
    if (prim && !allowed.has(tag)) out.push({ file, line: lineOf(m, x.index + x[0].indexOf("<")), rule: "raw-html", message: `<${tag}>: use ${prim}` });
  }
  for (const x of src.matchAll(TAG_ATTR)) {
    const v = x[1];
    if (!BOX_TAGS.includes(v) && !TEXT_TAGS.includes(v)) out.push({ file, line: lineOf(src, x.index), rule: "tag-allowlist", message: `tag "${v}" is not an HTML5 Box/Text tag` });
  }
  return out;
}

export function listFiles(roots, { exclude = [] } = {}) {
  const out = [], ex = exclude.map((e) => path.resolve(e));
  const walk = (p) => {
    if (ex.some((e) => p === e || p.startsWith(e + path.sep)) || /node_modules|\.git$/.test(p)) return;
    const st = statSync(p);
    if (st.isDirectory()) for (const f of readdirSync(p).sort()) walk(path.join(p, f));
    else if (EXT[path.extname(p)]) out.push(p);
  };
  for (const r of roots) walk(path.resolve(r));
  return out;
}

export function checkSource(roots, opts = {}) {
  return listFiles(roots, opts).flatMap((f) => checkSourceText(readFileSync(f, "utf8"), f, opts));
}
