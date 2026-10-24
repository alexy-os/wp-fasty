import type { BlockSpec, Item, Json, Obj } from "./types";

export const isObj = (v: unknown): v is Obj => typeof v === "object" && v !== null && !Array.isArray(v);
export const clone = <T>(v: T): T => JSON.parse(JSON.stringify(v));

/** variant sample props with the overrides on top (objects merge, lists and scalars replace), as the server does */
export function merge(base: Obj, over: Obj): Obj {
  const out: Obj = { ...base };
  for (const [k, v] of Object.entries(over)) out[k] = isObj(v) && isObj(out[k]) ? merge(out[k] as Obj, v) : v;
  return out;
}

export function setPath(o: Obj, path: string[], value: Json | undefined): Obj {
  const out = clone(o);
  let cur: Obj = out;
  for (const k of path.slice(0, -1)) {
    if (!isObj(cur[k])) cur[k] = {};
    cur = cur[k] as Obj;
  }
  const last = path[path.length - 1];
  if (value === undefined) delete cur[last];
  else cur[last] = value;
  return out;
}

export function getPath(o: Json | undefined, path: string[]): Json | undefined {
  let cur: Json | undefined = o;
  for (const k of path) cur = isObj(cur) ? cur[k] : undefined;
  return cur;
}

export function kindOf(block: BlockSpec, data: string): string {
  return block.data.find((d) => d.as === data)?.kind ?? "";
}

let n = 0;
export function newId(items: Item[], block: string): string {
  const base = block.replace(/[^a-z0-9]/g, "").slice(0, 20) || "b";
  let id = base;
  while (items.some((i) => i.id === id)) id = `${base}${++n}`;
  return id;
}

export const human = (s: string) => s.replace(/([a-z])([A-Z])/g, "$1 $2").replace(/[._-]/g, " ").replace(/^./, (c) => c.toUpperCase());
