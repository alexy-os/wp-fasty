/**
 * Shared analysis for the three TypeScript runtimes (React, Svelte, Vue) and
 * the emitter for the per-brick shared contract module
 * (`<stem>.shared.ts`): recipe literal-union types, record types, base prop
 * types, and the classes helpers every runtime calls.
 */

import { walkExpr, type Expr } from "../domain/expr";
import {
  fileStem,
  type BrickDef,
  type ClassSpec,
  type PartDef,
  type PropDef,
} from "../domain/model";
import { htmlPropName, localIdent, reactPropName } from "../domain/naming";
import { BANNER, exprType, lowerFirst, makeEnv, pascalCase, type GeneratedFile, type TypeEnv } from "./common";
import { printTsExpr, type TsPrintCtx } from "./ts-expr";

// ---------------------------------------------------------------------------
// Declared props
// ---------------------------------------------------------------------------

export interface DeclaredProp {
  def: PropDef;
  /** React prop name (camelCase / className / aria-*). */
  reactName: string;
  /** Svelte/Vue prop name (HTML-native). */
  htmlName: string;
  /** Safe local identifier in generated code. */
  local: string;
  /** TS type reference. */
  tsType: string;
}

/**
 * Props that appear in TS runtime signatures (passthrough flows via rest;
 * `Class` is handled uniformly by each runtime: `className` in React,
 * `class` in Svelte, the class attr in Vue).
 */
export function declaredProps(brick: BrickDef, part: PartDef): DeclaredProp[] {
  return part.props
    .filter(
      (p) => p.type !== "attrs" && p.type !== "children" && !p.passthrough && p.name !== "Class"
    )
    .map((p) => ({
      def: p,
      reactName: reactPropName(p),
      htmlName: htmlPropName(p),
      local: localIdent(p.name),
      tsType: tsTypeOf(brick, part, p),
    }));
}

export function variantTypeName(part: PartDef, cvaKey: string): string {
  return part.name + pascalCase(cvaKey);
}

export function tsTypeOf(brick: BrickDef, part: PartDef, p: PropDef): string {
  if (p.cva) return variantTypeName(part, p.cvaKey ?? p.name.toLowerCase());
  switch (p.type) {
    case "string":
      return p.enumValues ? p.enumValues.map((v) => JSON.stringify(v)).join(" | ") : "string";
    case "bool":
      return "boolean";
    case "int":
      return p.enumValues ? p.enumValues.join(" | ") : "number";
    case "items":
      return `${p.itemsOf}[]`;
    default:
      throw new Error(`${part.name}.${p.name}: type ${p.type} has no TS representation`);
  }
}

// ---------------------------------------------------------------------------
// Classes helpers
// ---------------------------------------------------------------------------

export function classesHelperName(part: PartDef): string {
  return lowerFirst(part.name) + "Classes";
}

export function classesInputTypeName(part: PartDef): string {
  return part.name + "ClassesInput";
}

/** Canonical prop names referenced by a class spec (excluding Class). */
export function classSpecProps(spec: ClassSpec): string[] {
  const names = new Set<string>();
  const fromExpr = (e: Expr) =>
    walkExpr(e, (x) => {
      if (x.kind === "prop") names.add(x.name);
    });
  for (const source of Object.values(spec.recipe ?? {})) {
    if (typeof source === "string") names.add(source);
    else fromExpr(source);
  }
  for (const e of spec.extra ?? []) fromExpr(e);
  for (const st of spec.state ?? []) fromExpr(st.test);
  names.delete("Class");
  return [...names];
}

export function recipeVarFor(recipeId: string): string {
  return lowerFirst(pascalCase(recipeId)) + "Recipe";
}

/**
 * Print an inline ClassSpec as a `cn(...)` expression (used for non-root
 * class attrs, e.g. per-item classes inside loops).
 */
export function printInlineClassSpec(
  spec: ClassSpec,
  ctx: TsPrintCtx,
  classPropCode: () => string | undefined
): string {
  const args: string[] = [];
  if (spec.recipe) {
    throw new Error("inline class specs with recipes are not supported; use the part classes contract");
  }
  if (spec.staticBase) args.push(JSON.stringify(spec.staticBase));
  for (const e of spec.extra ?? []) args.push(printTsExpr(e, ctx));
  for (const st of spec.state ?? []) {
    args.push(`(${printTsExpr(st.test, ctx)} ? ${JSON.stringify(st.classes)} : "")`);
  }
  if (spec.includeClassProp !== false) {
    const cp = classPropCode();
    if (cp) args.push(cp);
  }
  return `cn(${args.join(", ")})`;
}

// ---------------------------------------------------------------------------
// Shared module emitter (`<stem>.shared.ts`)
// ---------------------------------------------------------------------------

export function sharedModulePath(brick: BrickDef): string {
  return `ui/${brick.dir}/${fileStem(brick)}.shared.ts`;
}

export function sharedModuleName(brick: BrickDef): string {
  return `./${fileStem(brick)}.shared`;
}

export function basePropsTypeName(part: PartDef): string {
  return part.name + "BaseProps";
}

export function emitSharedModule(brick: BrickDef, utilsPath: string): GeneratedFile {
  const lines: string[] = [];
  lines.push(`// ${BANNER}`);
  lines.push(`// ${brick.id} — shared TypeScript contract (types + classes helpers)`);
  lines.push(``);

  const helperImports = new Set<string>();
  const bodies: string[] = [];

  // Recipe imports.
  const recipeIds = Object.keys(brick.recipes);
  for (const id of recipeIds) {
    const file = brick.recipeFiles[id];
    bodies.push(`import ${recipeVarFor(id)}Json from "./${file}";`);
  }
  if (recipeIds.length > 0) {
    bodies.push(``);
    for (const id of recipeIds) {
      bodies.push(`export const ${recipeVarFor(id)} = ${recipeVarFor(id)}Json;`);
    }
  }
  bodies.push(``);

  // Record types.
  for (const record of brick.recordTypes ?? []) {
    const fields = record.fields
      .map((f) => `  ${localIdent(f.name)}${f.type === "string" ? "?: string" : "?: boolean"};`)
      .join("\n");
    bodies.push(`export type ${record.name} = {\n${fields}\n};`);
    bodies.push(``);
  }

  // Variant literal-union types + base props types + classes helpers.
  const emittedTypes = new Set<string>();
  let usesRecipeKey = false;
  let usesComposeRecipe = false;
  let usesCn = false;

  for (const part of brick.parts) {
    const decls = declaredProps(brick, part);

    for (const d of decls) {
      if (!d.def.cva) continue;
      const key = d.def.cvaKey ?? d.def.name.toLowerCase();
      const typeName = variantTypeName(part, key);
      if (emittedTypes.has(typeName)) continue;
      emittedTypes.add(typeName);
      usesRecipeKey = true;
      const recipeId = part.recipeId!;
      bodies.push(
        `export type ${typeName} = RecipeKey<typeof ${recipeVarFor(recipeId)}, ${JSON.stringify(key)}>;`
      );
    }

    // Base props type (framework-neutral; class/children/rest added per runtime).
    const fields = decls.map((d) => `  ${quoteIfNeeded(d.local)}?: ${d.tsType};`);
    bodies.push(``);
    bodies.push(`/** ${part.docs.split("\n")[0]} */`);
    bodies.push(`export type ${basePropsTypeName(part)} = {\n${fields.join("\n")}\n};`);

    // Classes helper.
    if (part.classes) {
      const spec = part.classes;
      const inputProps = classSpecProps(spec);
      const inputFields: string[] = [];
      for (const name of inputProps) {
        const d = decls.find((x) => x.def.name === name);
        if (!d) throw new Error(`${brick.id}/${part.name}: classes spec references undeclared prop ${name}`);
        inputFields.push(`  ${quoteIfNeeded(d.local)}?: ${d.tsType};`);
      }
      inputFields.push(`  className?: string;`);

      const env: TypeEnv = makeEnv(brick, part);
      const ctx: TsPrintCtx = {
        env,
        propCode: (name) => (name === "Class" ? "input.className" : `input.${localIdent(name)}`),
        itemCode: () => {
          throw new Error("classes helpers cannot reference loop items");
        },
        derivedCode: () => {
          throw new Error("classes helpers cannot reference derived values");
        },
        resolvedTagCode: () => {
          throw new Error("classes helpers cannot reference the resolved tag");
        },
        helpers: helperImports,
      };

      const args: string[] = [];
      if (spec.recipe) {
        usesComposeRecipe = true;
        const recipeId = spec.recipeId ?? part.recipeId!;
        const selection = Object.entries(spec.recipe)
          .map(([key, source]) => {
            if (typeof source === "string") {
              return `${quoteIfNeeded(key)}: ${source === "Class" ? "input.className" : `input.${localIdent(source)}`}`;
            }
            usesRecipeKey = true;
            return `${quoteIfNeeded(key)}: ${printTsExpr(source, ctx)} as RecipeKey<typeof ${recipeVarFor(recipeId)}, ${JSON.stringify(key)}>`;
          })
          .join(", ");
        args.push(`composeRecipe(${recipeVarFor(recipeId)}, { ${selection} })`);
      }
      if (spec.staticBase) args.push(JSON.stringify(spec.staticBase));
      for (const e of spec.extra ?? []) args.push(printTsExpr(e, ctx));
      for (const st of spec.state ?? []) {
        args.push(`(${printTsExpr(st.test, ctx)} ? ${JSON.stringify(st.classes)} : "")`);
      }
      if (spec.includeClassProp !== false) args.push("input.className");
      usesCn = true;

      bodies.push(``);
      bodies.push(`export type ${classesInputTypeName(part)} = {\n${inputFields.join("\n")}\n};`);
      bodies.push(``);
      bodies.push(
        `/** Compose ${part.name} classes without rendering — the cross-runtime \`asChild\` substitute. */`
      );
      bodies.push(
        `export function ${classesHelperName(part)}(input: ${classesInputTypeName(part)} = {}): string {\n` +
          `  return ${args.length === 1 && args[0]!.startsWith("cn(") ? args[0] : `cn(${args.join(", ")})`};\n` +
          `}`
      );
    }
    bodies.push(``);
  }

  // Header imports (after analysis so we know what is used).
  const utilImports: string[] = [];
  if (usesCn) utilImports.push("cn");
  if (usesComposeRecipe) utilImports.push("composeRecipe");
  const typeImports: string[] = [];
  if (usesRecipeKey) typeImports.push("type RecipeKey");
  if (utilImports.length + typeImports.length > 0) {
    lines.push(`import { ${[...utilImports, ...typeImports].join(", ")} } from "${utilsPath}";`);
  }
  if (helperImports.size > 0) {
    lines.push(`import { ${[...helperImports].sort().join(", ")} } from "${utilsPath}/expr";`);
  }

  const contents = [...lines, ...bodies].join("\n").replace(/\n{3,}/g, "\n\n").trimEnd() + "\n";
  return { path: sharedModulePath(brick), contents };
}

/**
 * True when an attr is a plain conditional forward of a passthrough prop —
 * TS runtimes skip it because rest props deliver the identical attribute.
 */
export function isPassthroughForward(
  attr: { kind: string; expr?: Expr; when?: Expr },
  part: PartDef
): boolean {
  if (attr.kind !== "expr" || !attr.expr || attr.when) return false;
  if (attr.expr.kind !== "prop") return false;
  const p = part.props.find((x) => x.name === (attr.expr as { name: string }).name);
  return p?.passthrough === true;
}

export function quoteIfNeeded(name: string): string {
  return /^[A-Za-z_$][A-Za-z0-9_$]*$/.test(name) ? name : JSON.stringify(name);
}

/** True when a string-typed expression can never print empty (attr always kept). */
export function neverEmptyString(expr: Expr, env: TypeEnv): boolean {
  switch (expr.kind) {
    case "lit":
      return typeof expr.value === "string" ? expr.value !== "" : true;
    case "defaultIfEmpty":
      return expr.fallback !== "";
    case "mapOr":
      return expr.fallback !== "" && Object.values(expr.mapping).every((v) => v !== "");
    case "intMapOr":
      return expr.fallback !== "" && Object.values(expr.mapping).every((v) => v !== "");
    case "boolToString":
    case "intToString":
    case "resolvedTag":
      return true;
    case "cond":
      return neverEmptyString(expr.then, env) && neverEmptyString(expr.else, env);
    default:
      return exprType(expr, env) === "bool" || exprType(expr, env) === "int" ? false : false;
  }
}
