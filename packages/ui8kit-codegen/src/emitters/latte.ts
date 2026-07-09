/**
 * Latte emitter — one `<Part>.latte` per supported part. Typed
 * `{parameters}` signature, `{var}` preamble for derived values and dynamic
 * tags, and a single `n:attr` map per element (computed attrs first, caller
 * `...$attrs` spread last so the caller wins). Unsupported parts (forEach,
 * branched slots) are skipped — see src/domain/php-support.ts.
 */

import type { Expr } from "../domain/expr";
import {
  type AttrSpec,
  type BrickDef,
  type ClassSpec,
  type ElementNode,
  type Node,
  type PartDef,
} from "../domain/model";
import { phpPropName } from "../domain/naming";
import { phpSupported } from "../domain/php-support";
import { allowedTags, VOID_TAGS } from "../domain/tags";
import {
  BANNER,
  exprType,
  indent,
  makeEnv,
  type Emitter,
  type GeneratedFile,
  type TypeEnv,
} from "./common";
import { phpClassesMethod, phpParams, hasAttrsProp, hasChildrenProp } from "./php-common";
import { printPhpExpr, phpString, type PhpPrintCtx } from "./php-expr";
import { classSpecProps, neverEmptyString } from "./ts-common";

export class LatteEmitter implements Emitter {
  readonly runtime = "latte" as const;

  emit(brick: BrickDef): GeneratedFile[] {
    return brick.parts
      .filter((part) => phpSupported(part).ok)
      .map((part) => this.emitPart(brick, part));
  }

  private emitPart(brick: BrickDef, part: PartDef): GeneratedFile {
    const env = makeEnv(brick, part);
    const ctx: PhpPrintCtx = {
      env,
      propCode: (name) => {
        const p = part.props.find((x) => x.name === name);
        if (!p) throw new Error(`${brick.id}/${part.name}: unknown prop ${name}`);
        return "$" + phpPropName(p);
      },
      resolvedTagCode: () => "$uiTag",
      // Prefixed so derived values never shadow same-named props ($target).
      derivedCode: (name) => "$ui" + name.charAt(0).toUpperCase() + name.slice(1),
    };

    const lines: string[] = [];
    lines.push(`{* ${BANNER} *}`);
    lines.push(`{* ${brick.id} — ${part.docs.split("\n")[0]} *}`);
    lines.push(this.parametersBlock(part));

    const preamble = this.preamble(part, env, ctx);
    if (preamble.length > 0) lines.push(...preamble);

    lines.push(this.printNode(part.render, brick, part, env, ctx));

    return {
      path: `ui/${brick.dir}/${part.name}.latte`,
      contents: lines.join("\n") + "\n",
    };
  }

  private parametersBlock(part: PartDef): string {
    const entries: string[] = [];
    for (const p of phpParams(part)) {
      const type = p.type === "string" ? "string" : p.type === "bool" ? "bool" : "int";
      const def = p.type === "string" ? "''" : p.type === "bool" ? "false" : "0";
      entries.push(`${type} $${p.name} = ${def}`);
    }
    if (hasAttrsProp(part)) entries.push(`array $attrs = []`);
    if (hasChildrenProp(part)) entries.push(`string $children = ''`);
    if (entries.length === 0) return `{parameters}`;
    return `{parameters\n${entries.map((e) => `  ${e},`).join("\n")}\n}`;
  }

  private preamble(part: PartDef, env: TypeEnv, ctx: PhpPrintCtx): string[] {
    const out: string[] = [];
    const root = part.render;
    if (root.kind === "element" && root.tag.kind !== "static") {
      const tag = root.tag;
      if (tag.kind === "resolved") {
        out.push(
          `{var $uiTag = \\UI8Kit\\Rt::resolveTag(${ctx.propCode(tag.fromProp)}, ${phpString(tag.fallback)}, ${phpString(tag.group)})}`
        );
      } else if (tag.kind === "heading") {
        out.push(`{var $uiTag = \\UI8Kit\\Rt::titleTag(${ctx.propCode(tag.fromProp)})}`);
      } else {
        out.push(`{var $uiTag = ${printPhpExpr(tag.expr, ctx)}}`);
      }
    }
    for (const [name, expr] of Object.entries(part.derived ?? {})) {
      out.push(`{var ${ctx.derivedCode(name)} = ${printPhpExpr(expr, ctx)}}`);
    }
    return out;
  }

  private printNode(node: Node, brick: BrickDef, part: PartDef, env: TypeEnv, ctx: PhpPrintCtx): string {
    switch (node.kind) {
      case "element":
        return this.printElement(node, brick, part, env, ctx);
      case "slot":
        return `{$children|noescape}`;
      case "text":
        return `{${printPhpExpr(node.expr, ctx)}}`;
      case "when": {
        const test = printPhpExpr(node.test, ctx);
        const thenCode = node.then.map((c) => this.printNode(c, brick, part, env, ctx)).join("");
        if (!node.else || node.else.length === 0) {
          return `{if ${test}}${thenCode}{/if}`;
        }
        const elseCode = node.else.map((c) => this.printNode(c, brick, part, env, ctx)).join("");
        return `{if ${test}}${thenCode}{else}${elseCode}{/if}`;
      }
      case "forEach":
        throw new Error(`${brick.id}/${part.name}: forEach reached the Latte emitter (must be skipped)`);
    }
  }

  private printElement(
    node: ElementNode,
    brick: BrickDef,
    part: PartDef,
    env: TypeEnv,
    ctx: PhpPrintCtx
  ): string {
    const attrMap = this.attrEntries(node.attrs, brick, part, env, ctx);
    const tag = node.tag;

    if (tag.kind === "static") {
      return this.elementMarkup(tag.tag, undefined, attrMap, node, brick, part, env, ctx);
    }
    if (tag.kind === "mapped") {
      // n:tag breaks on void/self-closing elements — print an explicit
      // if/else chain over the closed tag set instead (br|wbr today).
      const cases = tag.tags;
      const chunks: string[] = [];
      for (let i = 0; i < cases.length - 1; i++) {
        const kw = i === 0 ? "if" : "elseif";
        chunks.push(`{${kw} $uiTag === ${phpString(cases[i]!)}}`);
        chunks.push(this.elementMarkup(cases[i]!, undefined, attrMap, node, brick, part, env, ctx));
      }
      chunks.push(`{else}`);
      chunks.push(this.elementMarkup(cases[cases.length - 1]!, undefined, attrMap, node, brick, part, env, ctx));
      chunks.push(`{/if}`);
      return chunks.join("\n");
    }
    // resolved / heading — non-void groups, n:tag swaps the tag at runtime.
    const base = tag.kind === "resolved" ? tag.fallback : "h2";
    if (tag.kind === "resolved") {
      const voidCase = allowedTags(tag.group).find((t) => VOID_TAGS.has(t));
      if (voidCase) {
        throw new Error(`${brick.id}/${part.name}: resolved group ${tag.group} contains void tag ${voidCase} (n:tag unsupported)`);
      }
    }
    return this.elementMarkup(base, `$uiTag`, attrMap, node, brick, part, env, ctx);
  }

  private elementMarkup(
    tag: string,
    nTag: string | undefined,
    attrMap: string[],
    node: ElementNode,
    brick: BrickDef,
    part: PartDef,
    env: TypeEnv,
    ctx: PhpPrintCtx
  ): string {
    const isVoid = node.void || VOID_TAGS.has(tag);
    const nTagAttr = nTag ? ` n:tag="${nTag}"` : "";
    const nAttr =
      attrMap.length === 0
        ? ""
        : attrMap.length === 1 && attrMap[0] === "...$attrs"
          ? ` n:attr="[...$attrs]"`
          : ` n:attr="[\n${indent(attrMap.map((e) => `${e},`).join("\n"), 1)}\n]"`;
    const open = `<${tag}${nTagAttr}${nAttr}>`;
    if (isVoid) return open;
    const children = node.children
      .map((c) => this.printNode(c, brick, part, env, ctx))
      .join("");
    return `${open}${children}</${tag}>`;
  }

  private attrEntries(
    attrs: AttrSpec[],
    brick: BrickDef,
    part: PartDef,
    env: TypeEnv,
    ctx: PhpPrintCtx
  ): string[] {
    const out: string[] = [];
    for (const attr of attrs) {
      switch (attr.kind) {
        case "static":
          out.push(`${phpString(attr.name)} => ${phpString(attr.value)}`);
          break;
        case "expr": {
          const t = exprType(attr.expr, env);
          const value = printPhpExpr(attr.expr, ctx);
          const strValue = t === "string" ? value : `(string) (${value})`;
          if (attr.when) {
            const test = printPhpExpr(attr.when, ctx);
            out.push(`${phpString(attr.name)} => ((${test}) ? ${strValue} : null)`);
          } else if (attr.keepEmpty || t !== "string" || neverEmptyString(attr.expr, env)) {
            out.push(`${phpString(attr.name)} => ${strValue}`);
          } else {
            out.push(`${phpString(attr.name)} => \\UI8Kit\\Rt::orNull(${value})`);
          }
          break;
        }
        case "bool":
          out.push(`${phpString(attr.name)} => ${printPhpExpr(attr.expr, ctx)}`);
          break;
        case "class": {
          const call = attr.spec
            ? this.inlineClassCall(attr.spec, part, ctx)
            : this.partClassCall(part, ctx);
          out.push(`'class' => \\UI8Kit\\Rt::orNull(${call})`);
          break;
        }
        case "rest":
          out.push(`...$attrs`);
          break;
      }
    }
    return out;
  }

  /** `\UI8Kit\Classes::buttonClasses(['variant' => $variant, 'class' => $class])` */
  private partClassCall(part: PartDef, ctx: PhpPrintCtx): string {
    const spec = part.classes;
    if (!spec) throw new Error(`${part.name}: attrClass() without classes contract`);
    const inputs: string[] = [];
    for (const name of classSpecProps(spec)) {
      const p = part.props.find((x) => x.name === name)!;
      inputs.push(`${phpString(phpPropName(p))} => ${ctx.propCode(name)}`);
    }
    if (spec.includeClassProp !== false && part.props.some((p) => p.name === "Class")) {
      inputs.push(`'class' => ${ctx.propCode("Class")}`);
    }
    return `\\UI8Kit\\Classes::${phpClassesMethod(part)}([${inputs.join(", ")}])`;
  }

  /** Inline (non-recipe) class specs print as a direct Rt::cn call. */
  private inlineClassCall(spec: ClassSpec, part: PartDef, ctx: PhpPrintCtx): string {
    if (spec.recipe) {
      throw new Error(`${part.name}: inline class specs with recipes are not supported`);
    }
    const args: string[] = [];
    if (spec.staticBase) args.push(phpString(spec.staticBase));
    for (const e of spec.extra ?? []) args.push(printPhpExpr(e, ctx));
    for (const st of spec.state ?? []) {
      args.push(`((${printPhpExpr(st.test, ctx)}) ? ${phpString(st.classes)} : '')`);
    }
    if (spec.includeClassProp !== false && part.props.some((p) => p.name === "Class")) {
      args.push(ctx.propCode("Class"));
    }
    return `\\UI8Kit\\Rt::cn(${args.join(", ")})`;
  }
}

/** Guard: generated PHP inside `n:attr="..."` must not contain double quotes. */
export function assertAttrSafe(code: string, where: string): void {
  if (code.includes('"')) {
    throw new Error(`${where}: generated n:attr expression contains a double quote`);
  }
}
