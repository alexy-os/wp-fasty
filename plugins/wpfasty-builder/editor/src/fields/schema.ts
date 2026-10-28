/**
 * Typed fields from the catalog (Puck-style Config without React): the TS type strings of the block props
 * (`"split" | "stacked"`, `Stat[]`, `LinkActions`, `Record<string, string>`…) and the catalog `records` become
 * field definitions; the editor renders each with a component of fields/registry.ts. Pure functions (bun test).
 */
import type { BlockSpec, Json, Obj, PropSpec, RecordField, Variant } from "../types";

/** a parsed TS type */
export type TypeNode =
  | { kind: "string" }
  | { kind: "number" }
  | { kind: "boolean" }
  | { kind: "enum"; options: (string | number)[] }
  | { kind: "array"; of: TypeNode }
  | { kind: "map"; value: TypeNode }
  | { kind: "record"; name: string }
  | { kind: "object"; fields: { name: string; type: TypeNode; optional: boolean }[] }
  | { kind: "unknown"; ts: string };

export type Option = { label: string; value: string | number };
export type Field =
  | { type: "text"; label: string; docs?: string }
  | { type: "textarea"; label: string; docs?: string }
  | { type: "number"; label: string; docs?: string }
  | { type: "select"; label: string; options: Option[]; docs?: string }
  | { type: "radio"; label: string; options: Option[]; docs?: string }
  | { type: "boolean"; label: string; docs?: string }
  | { type: "array"; label: string; arrayFields: Record<string, Field>; item: Field; defaultItem: () => Json; summary: (item: Json, i: number) => string; docs?: string }
  | { type: "object"; label: string; objectFields: Record<string, Field>; docs?: string }
  | { type: "map"; label: string; docs?: string }
  | { type: "binding"; label: string; kind: string; field: Field; docs?: string }
  | { type: "json"; label: string; docs?: string };
export type FieldType = Field["type"];

export interface BlockConfig {
  label: string;
  fields: Record<string, Field>;
  /** the sample props of a variant: what an un-overridden field shows */
  defaultProps: (variant: string) => Obj;
}

/** splits on a separator outside quotes, brackets, braces and angle brackets */
function splitTop(s: string, sep: string): string[] {
  const out: string[] = [];
  let depth = 0;
  let quote = "";
  let cur = "";
  for (const ch of s) {
    if (quote) {
      cur += ch;
      if (ch === quote) quote = "";
      continue;
    }
    if (ch === '"' || ch === "'") quote = ch;
    else if ("([{<".includes(ch)) depth++;
    else if (")]}>".includes(ch)) depth--;
    if (ch === sep && depth === 0) {
      out.push(cur);
      cur = "";
    } else cur += ch;
  }
  out.push(cur);
  return out.map((x) => x.trim());
}

/** true when the "(" at 0 closes at the last character */
function wrapped(t: string): boolean {
  if (!t.startsWith("(") || !t.endsWith(")")) return false;
  let depth = 0;
  for (let i = 0; i < t.length; i++) {
    if (t[i] === "(") depth++;
    else if (t[i] === ")" && --depth === 0) return i === t.length - 1;
  }
  return false;
}

const unparen = (s: string) => {
  let t = s.trim();
  while (wrapped(t)) t = t.slice(1, -1).trim();
  return t;
};

/** parses a TS type string of the catalog */
export function parseType(input: string): TypeNode {
  const ts = unparen(input);
  const members = splitTop(ts, "|").filter((m) => m !== "undefined" && m !== "null");
  if (members.length > 1) {
    const lits = members.map(literal);
    if (lits.every((l) => l !== undefined)) return { kind: "enum", options: lits as (string | number)[] };
    if (members.every((m) => m === "true" || m === "false")) return { kind: "boolean" };
    return { kind: "unknown", ts };
  }
  const one = members[0] ?? "";
  if (one !== ts) return parseType(one);
  const lit = literal(one);
  if (lit !== undefined) return { kind: "enum", options: [lit] };
  if (one === "string") return { kind: "string" };
  if (one === "number") return { kind: "number" };
  if (one === "boolean") return { kind: "boolean" };
  if (one.endsWith("[]")) return { kind: "array", of: parseType(one.slice(0, -2)) };
  let m = one.match(/^(?:Array|ReadonlyArray)<(.+)>$/);
  if (m) return { kind: "array", of: parseType(m[1]!) };
  m = one.match(/^Record<\s*string\s*,(.+)>$/);
  if (m) return { kind: "map", value: parseType(m[1]!) };
  if (one.startsWith("{") && one.endsWith("}")) {
    const fields = splitTop(one.slice(1, -1).replace(/;/g, ","), ",").filter(Boolean).map((f) => {
      const [k, ...rest] = splitTop(f, ":");
      const name = (k ?? "").replace(/\?$/, "").trim();
      return { name, type: parseType(rest.join(":")), optional: (k ?? "").trim().endsWith("?") };
    });
    return { kind: "object", fields };
  }
  if (/^[A-Z][A-Za-z0-9]*$/.test(one)) return { kind: "record", name: one };
  return { kind: "unknown", ts: one };
}

function literal(m: string): string | number | undefined {
  const s = m.match(/^"([^"]*)"$/) ?? m.match(/^'([^']*)'$/);
  if (s) return s[1];
  if (/^-?\d+(\.\d+)?$/.test(m)) return Number(m);
  return undefined;
}

export const tsOf = (t: PropSpec["type"]): string => (typeof t === "string" ? t : t.ts ?? t.record ?? "");

export const human = (s: string) =>
  s.replace(/([a-z0-9])([A-Z])/g, "$1 $2").replace(/[._-]+/g, " ").replace(/^./, (c) => c.toUpperCase());

/** long-text props by name */
const LONG = /^(text|lead|excerpt|body|description|desc|quote|summary|note|notes|message|bio|intro|answer|content|caption|subtitle|copy|details|about)$/i;

export function textField(name: string, sample: Json | undefined, docs?: string): Field {
  const long = LONG.test(name) || (typeof sample === "string" && sample.length > 60);
  return long ? { type: "textarea", label: human(name), docs } : { type: "text", label: human(name), docs };
}

/** the field of a parsed type; `sample` (the variant sample value) only picks text vs textarea */
export function fieldOf(name: string, type: TypeNode, records: Record<string, RecordField[]>, sample?: Json, docs?: string, seen: string[] = []): Field {
  const label = human(name);
  switch (type.kind) {
    case "string":
      return textField(name, sample, docs);
    case "number":
      return { type: "number", label, docs };
    case "boolean":
      return { type: "boolean", label, docs };
    case "enum": {
      const options = type.options.map((o) => ({ label: /^[a-z][a-z0-9-]*$/i.test(String(o)) ? human(String(o)) : String(o), value: o }));
      const short = options.length <= 3 && options.every((o) => String(o.value).length <= 10);
      return { type: short ? "radio" : "select", label, options, docs };
    }
    case "map":
      return { type: "map", label, docs };
    case "array": {
      const first = Array.isArray(sample) ? (sample.length && sample.every(isObj) ? mergeSamples(sample) : sample[0]) : undefined;
      const item = fieldOf("item", type.of, records, first, undefined, seen);
      const arrayFields = item.type === "object" ? item.objectFields : { value: item };
      return {
        type: "array", label, docs, item, arrayFields,
        defaultItem: () => emptyOf(type.of, records),
        summary: (v, i) => summaryOf(v) ?? `${human(singular(name))} ${i + 1}`,
      };
    }
    case "record": {
      const rec = records[type.name];
      if (!rec || seen.includes(type.name)) return { type: "json", label, docs };
      return objectField(label, rec.map((f) => ({ name: f.name, type: parseType(f.type), docs: f.docs })), records, sample, docs, [...seen, type.name]);
    }
    case "object":
      return objectField(label, type.fields, records, sample, docs, seen);
    default:
      return { type: "json", label, docs };
  }
}

function objectField(label: string, fields: { name: string; type: TypeNode; docs?: string }[], records: Record<string, RecordField[]>, sample: Json | undefined, docs: string | undefined, seen: string[]): Field {
  const s = isObj(sample) ? sample : {};
  const objectFields: Record<string, Field> = {};
  for (const f of fields) objectFields[f.name] = fieldOf(f.name, f.type, records, s[f.name], f.docs, seen);
  // keys the sample has but the record does not declare (a record list of codegen-x behind its template): typed by
  // the sample value, so every text the block shows stays editable in a field
  for (const [k, v] of Object.entries(s)) if (!objectFields[k] && k !== "className") objectFields[k] = fieldOf(k, typeOfSample(v), records, v, undefined, seen);
  return { type: "object", label, objectFields, docs };
}

/** the type of a sample value: scalars as they are, objects by their keys, a list by the union of its items */
export function typeOfSample(v: Json | undefined): TypeNode {
  if (typeof v === "string") return { kind: "string" };
  if (typeof v === "number") return { kind: "number" };
  if (typeof v === "boolean") return { kind: "boolean" };
  if (Array.isArray(v)) return { kind: "array", of: v.length ? typeOfSample(v.every(isObj) ? mergeSamples(v) : v[0]) : { kind: "string" } };
  if (isObj(v)) return { kind: "object", fields: Object.entries(v).map(([name, x]) => ({ name, type: typeOfSample(x), optional: false })) };
  return { kind: "unknown", ts: "null" };
}

/** one sample from many (the variants of a block, the items of a list): objects merge key by key, the first value
 *  of a key wins, lists of objects merge their items */
export function mergeSamples(values: (Json | undefined)[]): Json | undefined {
  const objs = values.filter(isObj);
  if (!objs.length) return values.find((v) => v !== undefined && v !== null) ?? values[0];
  const keys = [...new Set(objs.flatMap((o) => Object.keys(o)))];
  return Object.fromEntries(keys.map((k) => {
    const vs = objs.map((o) => o[k]).filter((x) => x !== undefined);
    const lists = vs.filter(Array.isArray);
    if (lists.length && lists.every((l) => l.every(isObj)) && lists.some((l) => l.length)) return [k, [mergeSamples(lists.flat()) as Json]];
    return [k, (vs.some(isObj) ? mergeSamples(vs) : vs[0]) as Json];
  }));
}

const isObj = (v: unknown): v is Obj => typeof v === "object" && v !== null && !Array.isArray(v);
const singular = (s: string) => s.replace(/ies$/, "y").replace(/s$/, "");

let seq = 0;
/** an empty value of a type: a new array item ("id" fields get a fresh id) */
export function emptyOf(type: TypeNode, records: Record<string, RecordField[]>, name = "", seen: string[] = []): Json {
  switch (type.kind) {
    case "string":
      return name === "id" ? `item-${Date.now().toString(36)}${(seq++).toString(36)}` : "";
    case "number":
      return 0;
    case "boolean":
      return false;
    case "enum":
      return type.options[0] ?? "";
    case "array":
      return [];
    case "map":
      return {};
    case "record": {
      const rec = records[type.name];
      if (!rec || seen.includes(type.name)) return {};
      return Object.fromEntries(rec.filter((f) => !f.optional).map((f) => [f.name, f.default !== undefined ? (f.default as Json) : emptyOf(parseType(f.type), records, f.name, [...seen, type.name])]));
    }
    case "object":
      return Object.fromEntries(type.fields.filter((f) => !f.optional).map((f) => [f.name, emptyOf(f.type, records, f.name, seen)]));
    default:
      return null;
  }
}

/** a short label of an array item: its title, label, name… */
export function summaryOf(v: Json): string | undefined {
  if (typeof v === "string" || typeof v === "number") return String(v).slice(0, 48) || undefined;
  if (!isObj(v)) return undefined;
  for (const k of ["title", "label", "name", "question", "text", "value", "id"]) {
    const x = v[k];
    if ((typeof x === "string" && x) || typeof x === "number") return String(x).slice(0, 48);
  }
  return undefined;
}

/** blocks that have nothing to set on purpose (the inspector says so instead of showing fields); the gate
 *  tests/coverage.test.ts fails for any other block without typed fields */
export const NO_SETTINGS: string[] = [];

/** props the inspector never shows: the block's own id and extra classes */
export const HIDDEN_PROPS = ["id", "className"];

/** record types the WordPress adapter fills (the pager of a list: page, pages and its texts, PHP Pager); the
 *  inspector keeps only the listed fields of such a prop (the mode), like the paths of the current record */
export const ADAPTER_RECORDS: Record<string, string[]> = { PostPager: ["mode"] };

/**
 * The config of one block: a field per prop (bindable paths wrapped in binding fields; no field for a path the record
 * of the current route fills, e.g. the richtext title and content: they come from the page, nor for the adapter's
 * fields of a pager, ADAPTER_RECORDS), defaults per variant. The
 * sample of the fields is every variant's sample merged, and sample keys the props or records do not declare get a
 * field typed by their value, so a text a variant shows is never reachable only through "Advanced (JSON)".
 */
export function blockConfig(block: BlockSpec, records: Record<string, RecordField[]>): BlockConfig {
  const first = block.variants[0]?.props ?? {};
  const sample = (mergeSamples(block.variants.map((v) => v.props)) ?? {}) as Obj;
  const fields: Record<string, Field> = {};
  for (const p of block.props) {
    if (HIDDEN_PROPS.includes(p.name)) continue;
    const axis = block.axes[p.name];
    const type = axis?.length ? ({ kind: "enum", options: axis } as TypeNode) : parseType(tsOf(p.type));
    fields[p.name] = fieldOf(p.name, type, records, sample[p.name], p.docs || undefined);
  }
  for (const [k, v] of Object.entries(sample)) if (!fields[k] && !HIDDEN_PROPS.includes(k)) fields[k] = fieldOf(k, typeOfSample(v), records, v);
  for (const v of block.variants) for (const c of v.current ?? []) dropPath(fields, c.path);
  for (const p of block.props) {
    const keep = typeof p.type === "object" && p.type.record ? ADAPTER_RECORDS[p.type.record] : undefined;
    const f = keep ? fields[p.name] : undefined;
    if (!keep || !f) continue;
    if (f.type !== "object") { delete fields[p.name]; continue; }
    for (const k of Object.keys(f.objectFields)) if (!keep.includes(k)) delete f.objectFields[k];
    if (!Object.keys(f.objectFields).length) delete fields[p.name];
  }
  return {
    label: block.title,
    fields,
    defaultProps: (variant) => block.variants.find((v) => v.id === variant)?.props ?? first,
  };
}

/** removes the field of a path the current record fills (and an object field left without fields) */
function dropPath(fields: Record<string, Field>, path: string[]): void {
  const [head, ...rest] = path;
  const f = head === undefined ? undefined : fields[head];
  if (!f) return;
  if (!rest.length) { delete fields[head!]; return; }
  if (f.type !== "object") return;
  dropPath(f.objectFields, rest);
  if (!Object.keys(f.objectFields).length) delete fields[head!];
}

/** bindable paths of a variant → data kind ("posts" → "post", "filter.topics" → "topic") */
export function bindableOf(block: BlockSpec, variant: Variant | undefined): Record<string, string> {
  const out: Record<string, string> = {};
  for (const b of variant?.bindings ?? []) out[b.path.join(".")] = block.data.find((d) => d.as === b.data)?.kind ?? "";
  return out;
}

/** a Config for every block of a catalog (Puck's `Config.components`) */
export function catalogConfig(blocks: BlockSpec[], records: Record<string, RecordField[]>): Record<string, BlockConfig> {
  return Object.fromEntries(blocks.map((b) => [b.id, blockConfig(b, records)]));
}
