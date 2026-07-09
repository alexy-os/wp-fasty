/**
 * Emits `php/UI8Kit/Classes.php` — one static `<part>Classes(array $in)`
 * helper per part with a classes contract, plus the embedded variant recipes
 * (PHP array constants generated from the shared `*.variants.json`, the
 * PHP equivalent of Go's `//go:embed`). Helpers are emitted for every part —
 * including parts the template emitters skip — because class composition is
 * the documented app-level escape hatch.
 */

import type { Expr } from "../domain/expr";
import { fileStem, type BrickDef, type ClassSpec, type PartDef } from "../domain/model";
import { phpPropName } from "../domain/naming";
import type { VariantRecipe } from "../domain/recipe";
import { BANNER, makeEnv, type GeneratedFile } from "./common";
import { phpClassesMethod } from "./php-common";
import { printPhpExpr, phpString, type PhpPrintCtx } from "./php-expr";
import { classSpecProps } from "./ts-common";

function phpValue(value: unknown): string {
  if (typeof value === "string") return phpString(value);
  if (typeof value === "number" || typeof value === "boolean") return String(value);
  if (Array.isArray(value)) return `[${value.map(phpValue).join(", ")}]`;
  if (value && typeof value === "object") {
    const entries = Object.entries(value as Record<string, unknown>)
      .map(([k, v]) => `${phpString(k)} => ${phpValue(v)}`)
      .join(", ");
    return `[${entries}]`;
  }
  return "null";
}

function recipeConstName(brick: BrickDef, recipeId: string): string {
  return `${fileStem(brick)}_${recipeId}`.replace(/[^A-Za-z0-9]/g, "_").toUpperCase();
}

function recipeConst(brick: BrickDef, recipeId: string, recipe: VariantRecipe): string {
  const value = phpValue({
    base: recipe.base,
    keys: recipe.keys,
    defaults: recipe.defaults ?? {},
    byKey: recipe.byKey,
  });
  return `    private const ${recipeConstName(brick, recipeId)} = ${value};`;
}

function inputCode(part: PartDef, name: string): string {
  const p = part.props.find((x) => x.name === name);
  if (!p) throw new Error(`${part.name}: classes spec references undeclared prop ${name}`);
  const key = p.name === "Class" ? "class" : phpPropName(p);
  switch (p.type) {
    case "bool":
      return `(bool) ($in[${phpString(key)}] ?? false)`;
    case "int":
      return `(int) ($in[${phpString(key)}] ?? 0)`;
    default:
      return `(string) ($in[${phpString(key)}] ?? '')`;
  }
}

function classesMethod(brick: BrickDef, part: PartDef, spec: ClassSpec): string {
  const ctx: PhpPrintCtx = {
    env: makeEnv(brick, part),
    propCode: (name) => inputCode(part, name),
    resolvedTagCode: () => {
      throw new Error(`${part.name}: classes helpers cannot reference the resolved tag`);
    },
    derivedCode: () => {
      throw new Error(`${part.name}: classes helpers cannot reference derived values`);
    },
  };

  const tail: string[] = [];
  if (spec.staticBase) tail.push(phpString(spec.staticBase));
  for (const e of spec.extra ?? []) tail.push(printPhpExpr(e, ctx));
  for (const st of spec.state ?? []) {
    tail.push(`((${printPhpExpr(st.test, ctx)}) ? ${phpString(st.classes)} : '')`);
  }
  if (spec.includeClassProp !== false && part.props.some((p) => p.name === "Class")) {
    tail.push(`(string) ($in['class'] ?? '')`);
  }

  let body: string;
  if (spec.recipe) {
    const recipeId = spec.recipeId ?? part.recipeId;
    if (!recipeId) throw new Error(`${part.name}: recipe class spec without recipeId`);
    const selection = Object.entries(spec.recipe)
      .map(([key, source]) => {
        const value = typeof source === "string" ? inputCode(part, source) : printPhpExpr(source, ctx);
        return `${phpString(key)} => ${value}`;
      })
      .join(", ");
    const tailStr = tail.length > 0 ? `, ${tail.join(", ")}` : "";
    body = `Rt::compose(self::${recipeConstName(brick, recipeId)}, [${selection}]${tailStr})`;
  } else {
    body = `Rt::cn(${tail.join(", ")})`;
  }

  return [
    `    /** ${part.name} class composition — the server-side asChild substitute. */`,
    `    public static function ${phpClassesMethod(part)}(array $in = []): string`,
    `    {`,
    `        return ${body};`,
    `    }`,
  ].join("\n");
}

export function emitPhpClasses(bricks: BrickDef[]): GeneratedFile {
  const consts: string[] = [];
  const methods: string[] = [];
  const seen = new Set<string>();

  for (const brick of bricks) {
    for (const [id, recipe] of Object.entries(brick.recipes)) {
      consts.push(recipeConst(brick, id, recipe));
    }
    for (const part of brick.parts) {
      if (!part.classes) continue;
      const method = phpClassesMethod(part);
      if (seen.has(method)) throw new Error(`duplicate PHP classes method: ${method}`);
      seen.add(method);
      methods.push(classesMethod(brick, part, part.classes));
    }
  }

  const contents = [
    `<?php`,
    ``,
    `// ${BANNER}`,
    ``,
    `declare(strict_types=1);`,
    ``,
    `namespace UI8Kit;`,
    ``,
    `final class Classes`,
    `{`,
    consts.join("\n"),
    ``,
    methods.join("\n\n"),
    `}`,
    ``,
  ].join("\n");

  return { path: "php/UI8Kit/Classes.php", contents };
}
