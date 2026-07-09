/**
 * Svelte 5 emitter — one PascalCase `.svelte` file per part (runes mode,
 * snippets for children, `<svelte:element>` for resolved tags, HTML-native
 * prop names: `class`, `for`, `aria-label`).
 */

import {
  fileStem,
  type AttrSpec,
  type BrickDef,
  type ElementNode,
  type Node,
  type PartDef,
} from "../domain/model";
import { localIdent } from "../domain/naming";
import {
  BANNER,
  exprType,
  indent,
  makeEnv,
  usesItem,
  type Emitter,
  type GeneratedFile,
  type TypeEnv,
} from "./common";
import { printTsExpr, type TsPrintCtx } from "./ts-expr";
import {
  classesHelperName,
  classSpecProps,
  declaredProps,
  neverEmptyString,
  printInlineClassSpec,
  isPassthroughForward,
  variantTypeName,
} from "./ts-common";

const IDENT_RE = /^[A-Za-z_$][A-Za-z0-9_$]*$/;

export class SvelteEmitter implements Emitter {
  readonly runtime = "svelte" as const;

  constructor(private readonly utilsPath = "../../utils") {}

  emit(brick: BrickDef): GeneratedFile[] {
    return brick.parts.map((part) => this.emitPart(brick, part));
  }

  private emitPart(brick: BrickDef, part: PartDef): GeneratedFile {
    const env: TypeEnv = makeEnv(brick, part);
    const decls = declaredProps(brick, part);
    const helperImports = new Set<string>();
    const sharedValueImports = new Set<string>();
    const sharedTypeImports = new Set<string>();
    const state = { usesResolveTag: false, usesTitleTag: false, usesCn: false };
    const hasChildren = part.props.some((p) => p.type === "children");

    for (const d of decls) {
      if (d.def.cva) sharedTypeImports.add(variantTypeName(part, d.def.cvaKey ?? d.def.name.toLowerCase()));
      if (d.def.type === "items") sharedTypeImports.add(d.def.itemsOf!);
    }

    // Props type (module script).
    const typeFields: string[] = decls.map((d) => {
      const key = IDENT_RE.test(d.htmlName) ? d.htmlName : JSON.stringify(d.htmlName);
      return `  ${key}?: ${d.tsType};`;
    });
    typeFields.push(`  class?: string;`);
    if (hasChildren) typeFields.push(`  children?: Snippet;`);
    typeFields.push(`  [key: string]: unknown;`);

    // Destructure.
    const destructure: string[] = decls.map((d) => {
      if (!IDENT_RE.test(d.htmlName)) return `${JSON.stringify(d.htmlName)}: ${d.local}`;
      return d.htmlName === d.local ? d.htmlName : `${d.htmlName}: ${d.local}`;
    });
    destructure.push(`class: className`);
    if (hasChildren) destructure.push("children");
    destructure.push("...rest");

    const ctx: TsPrintCtx = {
      env,
      propCode: (name) => {
        if (name === "Class") return "className";
        const d = decls.find((x) => x.def.name === name);
        if (!d) throw new Error(`${brick.id}/${part.name}: prop ${name} not declared for Svelte`);
        return d.local;
      },
      itemCode: (field) => `item.${localIdent(field)}`,
      derivedCode: (name) => `${name}Value`,
      resolvedTagCode: () => "resolvedTagValue",
      helpers: helperImports,
    };

    const body: string[] = [];
    if (part.classes) {
      sharedValueImports.add(classesHelperName(part));
      const inputs = classSpecProps(part.classes).map((n) => ctx.propCode(n));
      body.push(
        `const cls = $derived(${classesHelperName(part)}({ ${[...inputs, "className"].join(", ")} }));`
      );
    }
    for (const [name, expr] of Object.entries(part.derived ?? {})) {
      if (usesItem(expr, env)) throw new Error(`${brick.id}/${part.name}: derived ${name} uses loop item`);
      body.push(`const ${name}Value = $derived(${printTsExpr(expr, ctx)});`);
    }

    const root = part.render;
    if (root.kind === "element" && root.tag.kind !== "static") {
      if (root.tag.kind === "resolved") {
        state.usesResolveTag = true;
        body.push(
          `const resolvedTagValue = $derived(resolveTag(${ctx.propCode(root.tag.fromProp)}, ${JSON.stringify(root.tag.fallback)}, TagGroup.${root.tag.group}));`
        );
      } else if (root.tag.kind === "heading") {
        state.usesTitleTag = true;
        helperImports.add("titleTag");
        body.push(`const resolvedTagValue = $derived(titleTag(${ctx.propCode(root.tag.fromProp)}));`);
      } else {
        body.push(`const resolvedTagValue = $derived(${printTsExpr(root.tag.expr, ctx)});`);
      }
    }

    const markup = this.printNode(root, ctx, part, state, { isRoot: true });

    // Assemble file.
    const moduleImports: string[] = [];
    if (hasChildren) moduleImports.push(`import type { Snippet } from "svelte";`);
    if (sharedTypeImports.size > 0) {
      moduleImports.push(
        `import type { ${[...sharedTypeImports].sort().join(", ")} } from "./${fileStem(brick)}.shared";`
      );
    }

    const scriptImports: string[] = [];
    if (sharedValueImports.size > 0) {
      scriptImports.push(
        `import { ${[...sharedValueImports].sort().join(", ")} } from "./${fileStem(brick)}.shared";`
      );
    }
    if (state.usesCn) scriptImports.push(`import { cn } from "${this.utilsPath}";`);
    if (helperImports.size > 0) {
      scriptImports.push(
        `import { ${[...helperImports].sort().join(", ")} } from "${this.utilsPath}/expr";`
      );
    }
    if (state.usesResolveTag) {
      scriptImports.push(`import { resolveTag, TagGroup } from "${this.utilsPath}/tags";`);
    }

    const docs = part.docs
      .split("\n")
      .map((l) => `  ${l}`.trimEnd())
      .join("\n");

    const lines: string[] = [];
    lines.push(`<!-- ${BANNER} -->`);
    lines.push(`<!--\n${docs}\n-->`);
    lines.push(`<script lang="ts" module>`);
    for (const imp of moduleImports) lines.push(indent(imp, 1));
    lines.push(``);
    lines.push(indent(`export type ${part.name}Props = {\n${typeFields.join("\n")}\n};`, 1));
    lines.push(`</script>`);
    lines.push(``);
    lines.push(`<script lang="ts">`);
    for (const imp of scriptImports) lines.push(indent(imp, 1));
    if (scriptImports.length > 0) lines.push(``);
    lines.push(
      indent(
        `let {\n${destructure.map((d) => `  ${d}`).join(",\n")}\n}: ${part.name}Props = $props();`,
        1
      )
    );
    if (body.length > 0) {
      lines.push(``);
      for (const stmt of body) lines.push(indent(stmt, 1));
    }
    lines.push(`</script>`);
    lines.push(``);
    lines.push(markup);

    return {
      path: `ui/${brick.dir}/${part.name}.svelte`,
      contents: lines.join("\n").replace(/\n{3,}/g, "\n\n").trimEnd() + "\n",
    };
  }

  private printAttrs(
    node: ElementNode,
    ctx: TsPrintCtx,
    part: PartDef,
    state: { usesCn: boolean },
    opts: { inLoop: boolean }
  ): string {
    const out: string[] = [];
    for (const attr of node.attrs) {
      out.push(this.printAttr(attr, ctx, part, state, opts));
    }
    return out.filter(Boolean).join(" ");
  }

  private printAttr(
    attr: AttrSpec,
    ctx: TsPrintCtx,
    part: PartDef,
    state: { usesCn: boolean },
    opts: { inLoop: boolean }
  ): string {
    switch (attr.kind) {
      case "static":
        return `${attr.name}=${JSON.stringify(attr.value)}`;
      case "expr": {
        if (isPassthroughForward(attr, part)) return "";
        const value = printTsExpr(attr.expr, ctx);
        const t = exprType(attr.expr, ctx.env);
        if (attr.when) {
          return `${attr.name}={${printTsExpr(attr.when, ctx)} ? ${value} : undefined}`;
        }
        if (attr.keepEmpty && t === "string") {
          return `${attr.name}={(${value}) ?? ""}`;
        }
        if (t !== "string" || neverEmptyString(attr.expr, ctx.env)) {
          return `${attr.name}={${value}}`;
        }
        ctx.helpers.add("orUndef");
        return `${attr.name}={orUndef(${value})}`;
      }
      case "bool":
        return `${attr.name}={${printTsExpr(attr.expr, ctx)} || undefined}`;
      case "class": {
        // With spread attributes Svelte SSR prints `class=""` even for
        // undefined; a conditional spread omits the attr entirely.
        if (!attr.spec) return `{...(cls ? { class: cls } : {})}`;
        state.usesCn = true;
        const inline = printInlineClassSpec(attr.spec, ctx, () =>
          part.props.some((p) => p.name === "Class") && !opts.inLoop ? "className" : undefined
        );
        return `{...((${inline}) ? { class: ${inline} } : {})}`;
      }
      case "rest":
        return `{...rest}`;
    }
  }

  private printNode(
    node: Node,
    ctx: TsPrintCtx,
    part: PartDef,
    state: { usesResolveTag: boolean; usesTitleTag: boolean; usesCn: boolean },
    opts: { isRoot?: boolean; inLoop?: boolean } = {}
  ): string {
    switch (node.kind) {
      case "element": {
        const dynamic = node.tag.kind !== "static";
        if (dynamic && !opts.isRoot) {
          throw new Error(`${part.name}: dynamic tags only supported at part root`);
        }
        const tagCode = dynamic ? "svelte:element" : node.tag.kind === "static" ? node.tag.tag : "";
        const attrs = this.printAttrs(node, ctx, part, state, { inLoop: opts.inLoop === true });
        const thisAttr = dynamic ? `this={resolvedTagValue}` : "";
        const openParts = [tagCode, thisAttr, attrs].filter(Boolean).join(" ");
        if (node.void || node.children.length === 0) {
          return node.void ? `<${openParts} />` : `<${openParts}></${tagCode}>`;
        }
        const rendered = node.children.map((c) =>
          this.printNode(c, ctx, part, state, { inLoop: opts.inLoop })
        );
        // Mixed inline content (text + slot) must not gain whitespace text
        // nodes — join on one line to keep the DOM contract byte-equal.
        if (node.children.some((c) => c.kind === "text")) {
          return `<${openParts}>${rendered.join("")}</${tagCode}>`;
        }
        return `<${openParts}>\n${indent(rendered.join("\n"), 1)}\n</${tagCode}>`;
      }
      case "slot":
        return `{@render children?.()}`;
      case "text":
        return `{${printTsExpr(node.expr, ctx)}}`;
      case "when": {
        const test = printTsExpr(node.test, ctx);
        const thenCode = node.then
          .map((c) => this.printNode(c, ctx, part, state, opts))
          .join("\n");
        if (!node.else || node.else.length === 0) {
          return `{#if ${test}}\n${indent(thenCode, 1)}\n{/if}`;
        }
        // Collapse else-if chains for readability.
        if (node.else.length === 1 && node.else[0]!.kind === "when") {
          const elseWhen = this.printNode(node.else[0]!, ctx, part, state, opts);
          const chained = elseWhen.replace(/^\{#if /, "{:else if ").replace(/\n\{\/if\}$/, "\n{/if}");
          return `{#if ${test}}\n${indent(thenCode, 1)}\n${chained}`;
        }
        const elseCode = node.else
          .map((c) => this.printNode(c, ctx, part, state, opts))
          .join("\n");
        return `{#if ${test}}\n${indent(thenCode, 1)}\n{:else}\n${indent(elseCode, 1)}\n{/if}`;
      }
      case "forEach": {
        const itemsCode = ctx.propCode(node.itemsProp);
        const body = node.body
          .map((c) => this.printNode(c, ctx, part, state, { inLoop: true }))
          .join("\n");
        return `{#each ${itemsCode} ?? [] as item}\n${indent(body, 1)}\n{/each}`;
      }
    }
  }
}
