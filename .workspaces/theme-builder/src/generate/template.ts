// Minimal logic-less templates: {{path}}, {{#if path}}…{{/if}}, {{#unless path}}…{{/unless}} (nestable).
// A line holding only a block tag is removed together with its newline, so templates keep the output layout.
type Vars = Record<string, unknown>;

const get = (vars: Vars, path: string): unknown =>
  path.split(".").reduce<unknown>((o, k) => (o && typeof o === "object" ? (o as Vars)[k] : undefined), vars);

const truthy = (v: unknown): boolean => (Array.isArray(v) ? v.length > 0 : Boolean(v));

export function render(tpl: string, vars: Vars): string {
  const lines = tpl.split("\n");
  const out: string[] = [];
  const stack: boolean[] = [];
  const on = () => stack.every(Boolean);
  for (const line of lines) {
    const block = line.trim().match(/^\{\{(#if|#unless|\/if|\/unless) ?([\w.]*)\}\}$/);
    if (block) {
      const [, tag, path] = block;
      if (tag === "#if") stack.push(truthy(get(vars, path)));
      else if (tag === "#unless") stack.push(!truthy(get(vars, path)));
      else if (stack.pop() === undefined) throw new Error(`template: unbalanced ${tag}`);
      continue;
    }
    if (!on()) continue;
    out.push(line.replace(/\{\{([\w.]+)\}\}/g, (_m, p: string) => {
      const v = get(vars, p);
      if (v === undefined || v === null) throw new Error(`template: missing value for {{${p}}}`);
      return String(v);
    }));
  }
  if (stack.length) throw new Error("template: unclosed block");
  return out.join("\n");
}
