/**
 * Twig emitter — one `<Part>.html.twig` per supported part. Attributes print
 * through `ui8kit_attr_str(computed, attrs)` (registered by
 * `UI8Kit\TwigExtension`): computed entries first, caller attrs merged last
 * so the caller wins. Dynamic tags use `{% set uiTag %}` interpolation, which
 * Twig supports for void and non-void elements alike. Unsupported parts are
 * skipped — see src/domain/php-support.ts.
 */

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
import { VOID_TAGS } from "../domain/tags";
import {
  BANNER,
  exprType,
  makeEnv,
  type Emitter,
  type GeneratedFile,
  type TypeEnv,
} from "./common";
import { phpClassesMethod } from "./php-common";
import { phpString } from "./php-expr";
import { printTwigExpr, type TwigPrintCtx } from "./twig-expr";
import { classSpecProps, neverEmptyString } from "./ts-common";

export class TwigEmitter implements Emitter {
  readonly runtime = "twig" as const;

  emit(brick: BrickDef): GeneratedFile[] {
    return brick.parts
      .filter((part) => phpSupported(part).ok)
      .map((part) => this.emitPart(brick, part));
  }

  private emitPart(brick: BrickDef, part: PartDef): GeneratedFile {
    const env = makeEnv(brick, part);
    const ctx: TwigPrintCtx = {
      env,
      propCode: (name) => {
        const p = part.props.find((x) => x.name === name);
        if (!p) throw new Error(`${brick.id}/${part.name}: unknown prop ${name}`);
        const v = phpPropName(p);
        const def = p.type === "bool" ? "false" : p.type === "int" ? "0" : "''";
        return `(${v}|default(${def}))`;
      },
      resolvedTagCode: () => "uiTag",
      // Prefixed so derived values never shadow same-named props (target).
      derivedCode: (name) => "ui" + name.charAt(0).toUpperCase() + name.slice(1),
    };

    const lines: string[] = [];
    lines.push(`{# ${BANNER} #}`);
    lines.push(`{# ${brick.id} — ${part.docs.split("\n")[0]} #}`);

    const root = part.render;
    if (root.kind === "element" && root.tag.kind !== "static") {
      const tag = root.tag;
      if (tag.kind === "resolved") {
        lines.push(
          `{% set uiTag = ui8kit_resolve_tag(${ctx.propCode(tag.fromProp)}, ${phpString(tag.fallback)}, ${phpString(tag.group)}) %}`
        );
      } else if (tag.kind === "heading") {
        lines.push(`{% set uiTag = ui8kit_title_tag(${ctx.propCode(tag.fromProp)}) %}`);
      } else {
        lines.push(`{% set uiTag = ${printTwigExpr(tag.expr, ctx)} %}`);
      }
    }
    for (const [name, expr] of Object.entries(part.derived ?? {})) {
      lines.push(`{% set ${ctx.derivedCode(name)} = ${printTwigExpr(expr, ctx)} %}`);
    }

    lines.push(this.printNode(part.render, brick, part, env, ctx));

    return {
      path: `ui/${brick.dir}/${part.name}.html.twig`,
      contents: lines.join("\n") + "\n",
    };
  }

  private printNode(node: Node, brick: BrickDef, part: PartDef, env: TypeEnv, ctx: TwigPrintCtx): string {
    switch (node.kind) {
      case "element":
        return this.printElement(node, brick, part, env, ctx);
      case "slot":
        return `{{ children|default('')|raw }}`;
      case "text":
        return `{{ ${printTwigExpr(node.expr, ctx)} }}`;
      case "when": {
        const test = printTwigExpr(node.test, ctx);
        const thenCode = node.then.map((c) => this.printNode(c, brick, part, env, ctx)).join("");
        if (!node.else || node.else.length === 0) {
          return `{% if ${test} %}${thenCode}{% endif %}`;
        }
        const elseCode = node.else.map((c) => this.printNode(c, brick, part, env, ctx)).join("");
        return `{% if ${test} %}${thenCode}{% else %}${elseCode}{% endif %}`;
      }
      case "forEach":
        throw new Error(`${brick.id}/${part.name}: forEach reached the Twig emitter (must be skipped)`);
    }
  }

  private printElement(
    node: ElementNode,
    brick: BrickDef,
    part: PartDef,
    env: TypeEnv,
    ctx: TwigPrintCtx
  ): string {
    const entries = this.attrEntries(node.attrs, part, env, ctx);
    const hasRest = node.attrs.some((a) => a.kind === "rest");
    const attrCall =
      entries.length === 0 && !hasRest
        ? ""
        : `{{ ui8kit_attr_str({ ${entries.join(", ")} }, ${hasRest ? "attrs|default({})" : "{}"}) }}`;

    const tag = node.tag;
    const dynamic = tag.kind !== "static";
    const openTag = dynamic ? `{{ uiTag }}` : tag.tag;
    const isVoid =
      node.void || (tag.kind === "static" && VOID_TAGS.has(tag.tag));

    const open = `<${openTag}${attrCall}>`;
    if (isVoid) return open;
    const children = node.children
      .map((c) => this.printNode(c, brick, part, env, ctx))
      .join("");
    return `${open}${children}</${openTag}>`;
  }

  private attrEntries(
    attrs: AttrSpec[],
    part: PartDef,
    env: TypeEnv,
    ctx: TwigPrintCtx
  ): string[] {
    const out: string[] = [];
    for (const attr of attrs) {
      switch (attr.kind) {
        case "static":
          out.push(`${phpString(attr.name)}: ${phpString(attr.value)}`);
          break;
        case "expr": {
          const t = exprType(attr.expr, env);
          const value = printTwigExpr(attr.expr, ctx);
          const strValue = t === "string" ? value : `((${value}) ~ '')`;
          if (attr.when) {
            const test = printTwigExpr(attr.when, ctx);
            out.push(`${phpString(attr.name)}: ((${test}) ? ${strValue} : null)`);
          } else if (attr.keepEmpty || t !== "string" || neverEmptyString(attr.expr, env)) {
            out.push(`${phpString(attr.name)}: ${strValue}`);
          } else {
            out.push(`${phpString(attr.name)}: ui8kit_or_null(${value})`);
          }
          break;
        }
        case "bool":
          out.push(`${phpString(attr.name)}: ${printTwigExpr(attr.expr, ctx)}`);
          break;
        case "class": {
          const call = attr.spec
            ? this.inlineClassCall(attr.spec, part, ctx)
            : this.partClassCall(part, ctx);
          out.push(`'class': ui8kit_or_null(${call})`);
          break;
        }
        case "rest":
          // Handled by the second ui8kit_attr_str argument.
          break;
      }
    }
    return out;
  }

  private partClassCall(part: PartDef, ctx: TwigPrintCtx): string {
    const spec = part.classes;
    if (!spec) throw new Error(`${part.name}: attrClass() without classes contract`);
    const inputs: string[] = [];
    for (const name of classSpecProps(spec)) {
      const p = part.props.find((x) => x.name === name)!;
      inputs.push(`${phpString(phpPropName(p))}: ${ctx.propCode(name)}`);
    }
    if (spec.includeClassProp !== false && part.props.some((p) => p.name === "Class")) {
      inputs.push(`'class': ${ctx.propCode("Class")}`);
    }
    return `ui8kit_classes(${phpString(phpClassesMethod(part))}, { ${inputs.join(", ")} })`;
  }

  private inlineClassCall(spec: ClassSpec, part: PartDef, ctx: TwigPrintCtx): string {
    if (spec.recipe) {
      throw new Error(`${part.name}: inline class specs with recipes are not supported`);
    }
    const args: string[] = [];
    if (spec.staticBase) args.push(phpString(spec.staticBase));
    for (const e of spec.extra ?? []) args.push(printTwigExpr(e, ctx));
    for (const st of spec.state ?? []) {
      args.push(`((${printTwigExpr(st.test, ctx)}) ? ${phpString(st.classes)} : '')`);
    }
    if (spec.includeClassProp !== false && part.props.some((p) => p.name === "Class")) {
      args.push(ctx.propCode("Class"));
    }
    return `ui8kit_cn([${args.join(", ")}])`;
  }
}
