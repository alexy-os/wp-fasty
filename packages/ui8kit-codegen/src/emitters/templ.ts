/**
 * Go Templ emitter — one `<stem>.templ` per brick (props structs, exported
 * `<Part>Classes` helpers, templ components) plus `<stem>_gen.go` embedding
 * the shared `*.variants.json` recipes.
 */

import { walkExpr, type Expr } from "../domain/expr";
import {
  fileStem,
  walkNodes,
  type AttrSpec,
  type BrickDef,
  type ClassSpec,
  type ElementNode,
  type Node,
  type PartDef,
} from "../domain/model";
import { allowedTags } from "../domain/tags";
import {
  BANNER,
  exprType,
  foldResolvedTag,
  indent,
  makeEnv,
  lowerFirst,
  pascalCase,
  type Emitter,
  type GeneratedFile,
  type TypeEnv,
} from "./common";
import { goImportsFor, printGoExpr, type GoPrintCtx } from "./go-expr";
import { classSpecProps, neverEmptyString } from "./ts-common";

const HEADING_TAGS = ["h1", "h3", "h4", "h5", "h6"]; // h2 is the default case

export interface TemplOptions {
  /** Go module root for generated imports. */
  goModule: string;
}

export class TemplEmitter implements Emitter {
  readonly runtime = "templ" as const;

  constructor(private readonly options: TemplOptions) {}

  emit(brick: BrickDef): GeneratedFile[] {
    const stem = fileStem(brick);
    const pkg = brick.goPackage ?? brick.dir;

    const bodyParts: string[] = [];
    const extraImports = new Set<string>();

    // Record types.
    for (const record of brick.recordTypes ?? []) {
      const fields = record.fields.map((f) => {
        const doc = f.docs ? `\t// ${f.docs}\n` : "";
        return `${doc}\t${f.name} ${f.type === "string" ? "string" : "bool"}`;
      });
      bodyParts.push(`// ${record.name} is one ${record.name} entry.\ntype ${record.name} struct {\n${fields.join("\n")}\n}`);
    }

    for (const part of brick.parts) {
      const env: TypeEnv = makeEnv(brick, part);
      collectGoImports(brick, part, extraImports);
      bodyParts.push(this.emitPropsStruct(part));
      if (part.classes) bodyParts.push(this.emitClassesFunc(brick, part, env));
      for (const [name, expr] of Object.entries(part.derived ?? {})) {
        bodyParts.push(this.emitDerivedFunc(brick, part, env, name, expr));
      }
      bodyParts.push(this.emitComponent(brick, part, env));
    }

    const header: string[] = [];
    header.push(`// ${BANNER}`);
    header.push(``);
    header.push(`/*`);
    for (const line of brick.docs.split("\n")) header.push(line);
    header.push(`*/`);
    header.push(``);
    header.push(`package ${pkg}`);
    header.push(``);
    const imports = [...extraImports].sort().map((i) => `\t"${i}"`);
    header.push(`import (`);
    for (const imp of imports) header.push(imp);
    if (imports.length > 0) header.push(``);
    header.push(`\tuiutils "${this.options.goModule}/utils"`);
    header.push(`)`);

    const templFile: GeneratedFile = {
      path: `ui/${brick.dir}/${stem}.templ`,
      contents: [header.join("\n"), "", bodyParts.join("\n\n"), ""].join("\n"),
    };

    if (Object.keys(brick.recipeFiles).length === 0) return [templFile];
    return [templFile, this.emitGenFile(brick, pkg, stem)];
  }

  // -------------------------------------------------------------------------
  // Go declarations
  // -------------------------------------------------------------------------

  private goType(part: PartDef, propName: string): string {
    const p = part.props.find((x) => x.name === propName)!;
    if (p.goType) return p.goType;
    switch (p.type) {
      case "string":
        return "string";
      case "bool":
        return "bool";
      case "int":
        return "int";
      case "items":
        return `[]${p.itemsOf}`;
      case "attrs":
        return "templ.Attributes";
      default:
        throw new Error(`${part.name}.${propName}: no Go type`);
    }
  }

  private emitPropsStruct(part: PartDef): string {
    const fields: string[] = [];
    for (const p of part.props) {
      if (p.type === "children") continue;
      const doc = p.docs ? `\t// ${p.name} — ${p.docs}\n` : "";
      fields.push(`${doc}\t${p.name} ${this.goType(part, p.name)}`);
    }
    return `// ${part.name}Props configures ${part.name}.\ntype ${part.name}Props struct {\n${fields.join("\n")}\n}`;
  }

  private goCtx(brick: BrickDef, part: PartDef, env: TypeEnv): GoPrintCtx {
    return {
      env,
      propCode: (name) => `p.${name}`,
      itemCode: (field) => `item.${field}`,
      derivedCode: (name) => `${lowerFirst(part.name)}${pascalCase(name)}(p)`,
      resolvedTagCode: () => {
        throw new Error(`${part.name}: resolvedTag must be folded before Go printing`);
      },
    };
  }

  private classArgs(brick: BrickDef, part: PartDef, spec: ClassSpec, ctx: GoPrintCtx): string {
    const tail: string[] = [];
    if (spec.staticBase) tail.push(JSON.stringify(spec.staticBase));
    for (const e of spec.extra ?? []) tail.push(printGoExpr(e, ctx));
    for (const st of spec.state ?? []) {
      tail.push(`uiutils.If(${printGoExpr(st.test, ctx)}, ${JSON.stringify(st.classes)}, "")`);
    }
    if (spec.includeClassProp !== false && part.props.some((p) => p.name === "Class")) {
      tail.push("p.Class");
    }

    if (spec.recipe) {
      const recipeId = spec.recipeId ?? part.recipeId!;
      const recipeVar = pascalCase(recipeId) + "Variants";
      const selection = Object.entries(spec.recipe)
        .map(([key, source]) => {
          const value = typeof source === "string" ? `p.${source}` : printGoExpr(source, ctx);
          return `\t\t${JSON.stringify(key)}: ${value},`;
        })
        .join("\n");
      const sel = selection ? `map[string]string{\n${selection}\n\t}` : `map[string]string{}`;
      const tailStr = tail.length > 0 ? `, ${tail.join(", ")}` : "";
      return `uiutils.Compose(${recipeVar}, ${sel}${tailStr})`;
    }
    return `uiutils.Cn(${tail.join(", ")})`;
  }

  private emitClassesFunc(brick: BrickDef, part: PartDef, env: TypeEnv): string {
    const ctx = this.goCtx(brick, part, env);
    const call = this.classArgs(brick, part, part.classes!, ctx);
    return (
      `// ${part.name}Classes composes ${part.name} classes without rendering —\n` +
      `// the server-composition escape hatch for custom root elements.\n` +
      `func ${part.name}Classes(p ${part.name}Props) string {\n\treturn ${call}\n}`
    );
  }

  private emitDerivedFunc(
    brick: BrickDef,
    part: PartDef,
    env: TypeEnv,
    name: string,
    expr: Expr
  ): string {
    const ctx = this.goCtx(brick, part, env);
    const t = exprType(expr, env);
    const goT = t === "string" ? "string" : t === "bool" ? "bool" : "int";
    return `func ${lowerFirst(part.name)}${pascalCase(name)}(p ${part.name}Props) ${goT} {\n\treturn ${printGoExpr(expr, ctx)}\n}`;
  }

  // -------------------------------------------------------------------------
  // templ component
  // -------------------------------------------------------------------------

  private emitComponent(brick: BrickDef, part: PartDef, env: TypeEnv): string {
    const ctx = this.goCtx(brick, part, env);
    const body = this.printNode(part.render, brick, part, env, ctx, undefined);
    return `// ${part.name} renders the ${part.name} contract.\ntempl ${part.name}(p ${part.name}Props) {\n${indent(body, 1, "\t")}\n}`;
  }

  private printNode(
    node: Node,
    brick: BrickDef,
    part: PartDef,
    env: TypeEnv,
    ctx: GoPrintCtx,
    currentTag: string | undefined
  ): string {
    switch (node.kind) {
      case "element":
        return this.printElement(node, brick, part, env, ctx);
      case "slot":
        return `{ children... }`;
      case "text":
        return `{ ${printGoExpr(fold(node.expr, currentTag), ctx)} }`;
      case "when": {
        const test = printGoExpr(fold(node.test, currentTag), ctx);
        const thenCode = node.then
          .map((c) => this.printNode(c, brick, part, env, ctx, currentTag))
          .join("\n");
        if (!node.else || node.else.length === 0) {
          return `if ${test} {\n${indent(thenCode, 1, "\t")}\n}`;
        }
        // else-if chain
        if (node.else.length === 1 && node.else[0]!.kind === "when") {
          const chained = this.printNode(node.else[0]!, brick, part, env, ctx, currentTag);
          return `if ${test} {\n${indent(thenCode, 1, "\t")}\n} else ${chained}`;
        }
        const elseCode = node.else
          .map((c) => this.printNode(c, brick, part, env, ctx, currentTag))
          .join("\n");
        return `if ${test} {\n${indent(thenCode, 1, "\t")}\n} else {\n${indent(elseCode, 1, "\t")}\n}`;
      }
      case "forEach": {
        const body = node.body
          .map((c) => this.printNode(c, brick, part, env, ctx, currentTag))
          .join("\n");
        return `for _, item := range p.${node.itemsProp} {\n${indent(body, 1, "\t")}\n}`;
      }
    }
  }

  private printElement(
    node: ElementNode,
    brick: BrickDef,
    part: PartDef,
    env: TypeEnv,
    ctx: GoPrintCtx
  ): string {
    const tag = node.tag;
    if (tag.kind === "static") {
      return this.printStaticElement(node, tag.tag, brick, part, env, ctx);
    }

    // Dynamic tags: a switch with one case per allowed tag; fallback last.
    let switchExpr: string;
    let cases: string[];
    let fallback: string;
    if (tag.kind === "resolved") {
      switchExpr = `uiutils.ResolveTag(p.${tag.fromProp}, ${JSON.stringify(tag.fallback)}, uiutils.TagGroup${tag.group})`;
      cases = allowedTags(tag.group).filter((t) => t !== tag.fallback);
      fallback = tag.fallback;
    } else if (tag.kind === "heading") {
      switchExpr = `uiutils.TitleTag(p.${tag.fromProp})`;
      cases = HEADING_TAGS;
      fallback = "h2";
    } else {
      switchExpr = printGoExpr(tag.expr, ctx);
      cases = tag.tags.slice(0, -1);
      fallback = tag.tags[tag.tags.length - 1]!;
    }

    const caseBlocks = cases.map((t) => {
      const el = this.printStaticElement(node, t, brick, part, env, ctx);
      return `case ${JSON.stringify(t)}:\n${indent(el, 1, "\t")}`;
    });
    const defaultBlock = `default:\n${indent(
      this.printStaticElement(node, fallback, brick, part, env, ctx),
      1,
      "\t"
    )}`;
    return `switch ${switchExpr} {\n${indent([...caseBlocks, defaultBlock].join("\n"), 1, "\t")}\n}`;
  }

  private printStaticElement(
    node: ElementNode,
    tag: string,
    brick: BrickDef,
    part: PartDef,
    env: TypeEnv,
    ctx: GoPrintCtx
  ): string {
    const attrLines: string[] = [];
    for (const attr of node.attrs) {
      attrLines.push(this.printAttr(attr, tag, brick, part, env, ctx));
    }
    const attrs = attrLines.filter(Boolean);

    const children = node.children
      .map((c) => this.printNode(c, brick, part, env, ctx, tag))
      .join("\n");

    const compact = attrs.length <= 2 && attrs.every((a) => !a.includes("\n"));
    if (compact && (node.void || node.children.length === 0)) {
      const inline = attrs.length > 0 ? ` ${attrs.join(" ")}` : "";
      if (node.void) return `<${tag}${inline}/>`;
      return `<${tag}${inline}></${tag}>`;
    }

    if (attrs.length === 0) {
      if (node.void) return `<${tag}/>`;
      if (node.children.length === 0) return `<${tag}></${tag}>`;
      return `<${tag}>\n${indent(children, 1, "\t")}\n</${tag}>`;
    }
    const open = `<${tag}\n${indent(attrs.join("\n"), 1, "\t")}\n`;
    if (node.void) return `${open}/>`;
    if (node.children.length === 0) return `${open}></${tag}>`;
    return `${open}>\n${indent(children, 1, "\t")}\n</${tag}>`;
  }

  private printAttr(
    attr: AttrSpec,
    tag: string,
    brick: BrickDef,
    part: PartDef,
    env: TypeEnv,
    ctx: GoPrintCtx
  ): string {
    switch (attr.kind) {
      case "static":
        return `${attr.name}=${JSON.stringify(attr.value)}`;
      case "expr": {
        const expr = fold(attr.expr, tag);
        const value = printGoExpr(expr, ctx);
        const t = exprType(expr, env);
        const valueCode = t === "string" ? value : t === "int" ? `strconv.Itoa(${value})` : value;
        if (attr.when) {
          const when = fold(attr.when, tag);
          if (when.kind === "lit") {
            // Constant-folded per tag-switch case: drop or emit unconditionally.
            return when.value === false ? "" : `${attr.name}={ ${valueCode} }`;
          }
          const test = printGoExpr(when, ctx);
          return `if ${test} {\n\t${attr.name}={ ${valueCode} }\n}`;
        }
        if (attr.keepEmpty || t !== "string" || neverEmptyString(expr, env)) {
          return `${attr.name}={ ${valueCode} }`;
        }
        return `if uiutils.IsSetStr(${value}) {\n\t${attr.name}={ ${value} }\n}`;
      }
      case "bool":
        return `${attr.name}?={ ${printGoExpr(fold(attr.expr, tag), ctx)} }`;
      case "class": {
        if (!attr.spec) return `class={ ${part.name}Classes(p) }`;
        const call = this.classArgs(brick, part, { ...attr.spec, includeClassProp: false }, ctx);
        return `class={ ${call} }`;
      }
      case "rest":
        return `{ p.Attrs... }`;
    }
  }

  // -------------------------------------------------------------------------
  // _gen.go
  // -------------------------------------------------------------------------

  private emitGenFile(brick: BrickDef, pkg: string, stem: string): GeneratedFile {
    const lines: string[] = [];
    lines.push(`// ${BANNER}`);
    lines.push(``);
    lines.push(`package ${pkg}`);
    lines.push(``);
    lines.push(`import (`);
    lines.push(`\t_ "embed"`);
    lines.push(``);
    lines.push(`\tuiutils "${this.options.goModule}/utils"`);
    lines.push(`)`);
    for (const [id, file] of Object.entries(brick.recipeFiles)) {
      const varName = pascalCase(id) + "Variants";
      const jsonVar = lowerFirst(pascalCase(id)) + "VariantsJSON";
      lines.push(``);
      lines.push(`//go:embed ${file}`);
      lines.push(`var ${jsonVar} []byte`);
      lines.push(``);
      lines.push(`// ${varName} is the CVA recipe shared verbatim by all runtimes.`);
      lines.push(`var ${varName} = uiutils.MustParseVariantRecipe(${jsonVar}).ToVariants()`);
    }
    lines.push(``);
    return { path: `ui/${brick.dir}/${stem}_gen.go`, contents: lines.join("\n") };
  }
}

function fold(expr: Expr, tag: string | undefined): Expr {
  return tag === undefined ? expr : foldResolvedTag(expr, tag);
}

/** Collect Go stdlib imports needed by a part's expressions. */
function collectGoImports(brick: BrickDef, part: PartDef, out: Set<string>): void {
  const exprs: Expr[] = [];
  const pushSpec = (spec: ClassSpec | undefined) => {
    if (!spec) return;
    exprs.push(...(spec.extra ?? []));
    for (const st of spec.state ?? []) exprs.push(st.test);
    for (const source of Object.values(spec.recipe ?? {})) {
      if (typeof source !== "string") exprs.push(source);
    }
  };
  pushSpec(part.classes);
  for (const d of Object.values(part.derived ?? {})) exprs.push(d);
  walkNodes(part.render, (node) => {
    if (node.kind === "element") {
      for (const attr of node.attrs) {
        if (attr.kind === "expr") {
          exprs.push(attr.expr);
          if (attr.when) exprs.push(attr.when);
          // int-valued attrs stringify via strconv
          const env: TypeEnv = makeEnv(brick, part);
          try {
            if (exprType(attr.expr, env) === "int") out.add("strconv");
          } catch {
            // item refs outside env context — resolved during printing
          }
        }
        if (attr.kind === "bool") exprs.push(attr.expr);
        if (attr.kind === "class") pushSpec(attr.spec);
      }
      if (node.tag.kind === "mapped") exprs.push(node.tag.expr);
    }
    if (node.kind === "text") exprs.push(node.expr);
    if (node.kind === "when") exprs.push(node.test);
  });
  for (const imp of goImportsFor(exprs)) out.add(imp);
}
