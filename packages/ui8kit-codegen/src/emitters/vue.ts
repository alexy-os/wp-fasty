/**
 * Vue 3 emitter — one PascalCase `.vue` SFC per part (`<script setup lang="ts">`,
 * default slot, `<component :is>` for resolved tags). Caller classes arrive
 * via the `class` attribute and are merged Tailwind-aware through `cn`
 * (inheritAttrs is disabled so merge order stays deterministic).
 */

import type { Expr } from "../domain/expr";
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

export class VueEmitter implements Emitter {
  readonly runtime = "vue" as const;

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
    const state = { usesResolveTag: false, usesCn: false };
    const hasChildren = part.props.some((p) => p.type === "children");

    for (const d of decls) {
      if (d.def.cva) sharedTypeImports.add(variantTypeName(part, d.def.cvaKey ?? d.def.name.toLowerCase()));
      if (d.def.type === "items") sharedTypeImports.add(d.def.itemsOf!);
    }

    // Script-context print ctx (computed refs need `.value`).
    const scriptCtx: TsPrintCtx = {
      env,
      propCode: (name) => {
        if (name === "Class") return "callerClass.value";
        const d = decls.find((x) => x.def.name === name);
        if (!d) throw new Error(`${brick.id}/${part.name}: prop ${name} not declared for Vue`);
        return `props.${d.local}`;
      },
      itemCode: () => {
        throw new Error(`${part.name}: loop items are template-scope only`);
      },
      derivedCode: (name) => `${name}Value.value`,
      resolvedTagCode: () => "resolvedTagValue.value",
      helpers: helperImports,
    };
    // Template-context print ctx (computed refs auto-unwrap).
    const templateCtx: TsPrintCtx = {
      env,
      propCode: (name) => {
        if (name === "Class") return "callerClass";
        const d = decls.find((x) => x.def.name === name);
        if (!d) throw new Error(`${brick.id}/${part.name}: prop ${name} not declared for Vue`);
        return `props.${d.local}`;
      },
      itemCode: (field) => `item.${localIdent(field)}`,
      derivedCode: (name) => `${name}Value`,
      resolvedTagCode: () => "resolvedTagValue",
      helpers: helperImports,
    };

    // Props declaration.
    const propsFields = decls.map((d) => `  ${d.local}?: ${d.tsType};`);

    const body: string[] = [];
    body.push(`const attrs = useAttrs();`);
    body.push(`const callerClass = computed(() => attrs.class as string | undefined);`);
    body.push(`const restAttrs = computed(() => {`);
    body.push(`  const { class: _class, ...restEntries } = attrs;`);
    body.push(`  return restEntries;`);
    body.push(`});`);

    if (part.classes) {
      sharedValueImports.add(classesHelperName(part));
      const inputs = classSpecProps(part.classes).map((n) => {
        const d = decls.find((x) => x.def.name === n)!;
        return `${d.local}: props.${d.local}`;
      });
      body.push(
        `const cls = computed(() => ${classesHelperName(part)}({ ${[...inputs, "className: callerClass.value"].join(", ")} }));`
      );
    }
    for (const [name, expr] of Object.entries(part.derived ?? {})) {
      if (usesItem(expr, env)) throw new Error(`${brick.id}/${part.name}: derived ${name} uses loop item`);
      body.push(`const ${name}Value = computed(() => ${printTsExpr(expr, scriptCtx)});`);
    }

    const root = part.render;
    if (root.kind === "element" && root.tag.kind !== "static") {
      if (root.tag.kind === "resolved") {
        state.usesResolveTag = true;
        const t = root.tag;
        body.push(
          `const resolvedTagValue = computed(() => resolveTag(props.${localIdent(t.fromProp)}, ${JSON.stringify(t.fallback)}, TagGroup.${t.group}));`
        );
      } else if (root.tag.kind === "heading") {
        helperImports.add("titleTag");
        body.push(
          `const resolvedTagValue = computed(() => titleTag(props.${localIdent(root.tag.fromProp)}));`
        );
      } else {
        body.push(`const resolvedTagValue = computed(() => ${printTsExpr(root.tag.expr, scriptCtx)});`);
      }
    }

    const markup = this.printNode(root, templateCtx, part, state, { isRoot: true });

    // Assemble imports.
    const imports: string[] = [`import { computed, useAttrs } from "vue";`];
    const sharedBits: string[] = [
      ...[...sharedValueImports].sort(),
      ...[...sharedTypeImports].sort().map((t) => `type ${t}`),
    ];
    if (sharedBits.length > 0) {
      imports.push(`import { ${sharedBits.join(", ")} } from "./${fileStem(brick)}.shared";`);
    }
    if (state.usesCn) imports.push(`import { cn } from "${this.utilsPath}";`);
    if (helperImports.size > 0) {
      imports.push(`import { ${[...helperImports].sort().join(", ")} } from "${this.utilsPath}/expr";`);
    }
    if (state.usesResolveTag) {
      imports.push(`import { resolveTag, TagGroup } from "${this.utilsPath}/tags";`);
    }

    const docs = part.docs
      .split("\n")
      .map((l) => `  ${l}`.trimEnd())
      .join("\n");

    const lines: string[] = [];
    lines.push(`<!-- ${BANNER} -->`);
    lines.push(`<!--\n${docs}\n-->`);
    lines.push(`<script setup lang="ts">`);
    for (const imp of imports) lines.push(imp);
    lines.push(``);
    lines.push(`defineOptions({ name: ${JSON.stringify(part.name)}, inheritAttrs: false });`);
    lines.push(``);
    if (propsFields.length > 0) {
      lines.push(`const props = defineProps<{\n${propsFields.join("\n")}\n}>();`);
      lines.push(``);
    }
    for (const stmt of body) lines.push(stmt);
    lines.push(`</script>`);
    lines.push(``);
    lines.push(`<template>`);
    lines.push(indent(markup, 1));
    lines.push(`</template>`);

    return {
      path: `ui/${brick.dir}/${part.name}.vue`,
      contents: lines.join("\n").replace(/\n{3,}/g, "\n\n").trimEnd() + "\n",
    };
  }

  private printAttrs(
    node: ElementNode,
    ctx: TsPrintCtx,
    part: PartDef,
    state: { usesCn: boolean },
    opts: { inLoop: boolean }
  ): string[] {
    return node.attrs.map((attr) => this.printAttr(attr, ctx, part, state, opts)).filter(Boolean);
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
          return `:${attr.name}="${escapeVue(`${printTsExpr(attr.when, ctx)} ? ${value} : undefined`)}"`;
        }
        if (attr.keepEmpty && t === "string") {
          return `:${attr.name}="${escapeVue(`(${value}) ?? ''`)}"`;
        }
        if (t !== "string" || neverEmptyString(attr.expr, ctx.env)) {
          return `:${attr.name}="${escapeVue(value)}"`;
        }
        ctx.helpers.add("orUndef");
        return `:${attr.name}="${escapeVue(`orUndef(${value})`)}"`;
      }
      case "bool":
        return `:${attr.name}="${escapeVue(`${printTsExpr(attr.expr, ctx)} || undefined`)}"`;
      case "class": {
        if (!attr.spec) return `:class="cls || undefined"`;
        state.usesCn = true;
        const inline = printInlineClassSpec(attr.spec, ctx, () =>
          part.props.some((p) => p.name === "Class") && !opts.inLoop ? "callerClass" : undefined
        );
        return `:class="${escapeVue(`${inline} || undefined`)}"`;
      }
      case "rest":
        return `v-bind="restAttrs"`;
    }
  }

  private printNode(
    node: Node,
    ctx: TsPrintCtx,
    part: PartDef,
    state: { usesResolveTag: boolean; usesCn: boolean },
    opts: { isRoot?: boolean; inLoop?: boolean; extraDirectives?: string[] } = {}
  ): string {
    switch (node.kind) {
      case "element": {
        const dynamic = node.tag.kind !== "static";
        if (dynamic && !opts.isRoot) {
          throw new Error(`${part.name}: dynamic tags only supported at part root`);
        }
        const tagCode = dynamic ? "component" : node.tag.kind === "static" ? node.tag.tag : "";
        const attrs = this.printAttrs(node, ctx, part, state, { inLoop: opts.inLoop === true });
        const openBits = [
          tagCode,
          ...(dynamic ? [`:is="resolvedTagValue"`] : []),
          ...(opts.extraDirectives ?? []),
          ...attrs,
        ].join(" ");
        // Vue ignores interpolation children on <textarea>; the content
        // contract maps to :value (rendered as content during SSR).
        if (tagCode === "textarea" && node.children.length === 1 && node.children[0]!.kind === "text") {
          const value = printTsExpr((node.children[0] as { expr: Expr }).expr, ctx);
          return `<${openBits} :value="${escapeVue(`(${value}) ?? ''`)}" />`;
        }
        if (node.void || node.children.length === 0) {
          return `<${openBits} />`;
        }
        const children = node.children
          .map((c) => this.printNode(c, ctx, part, state, { inLoop: opts.inLoop }))
          .join("\n");
        return `<${openBits}>\n${indent(children, 1)}\n</${dynamic ? "component" : tagCode}>`;
      }
      case "slot":
        return `<slot />`;
      case "text":
        return `{{ ${printTsExpr(node.expr, ctx)} }}`;
      case "when": {
        const test = escapeVue(printTsExpr(node.test, ctx));
        const printBranch = (nodes: Node[], directive: string): string => {
          if (nodes.length === 1 && nodes[0]!.kind === "element") {
            return this.printNode(nodes[0]!, ctx, part, state, {
              ...opts,
              extraDirectives: [directive],
            });
          }
          const inner = nodes.map((c) => this.printNode(c, ctx, part, state, opts)).join("\n");
          const dirName = directive.split("=")[0];
          const attr = directive.includes("=") ? directive : dirName;
          return `<template ${attr}>\n${indent(inner, 1)}\n</template>`;
        };
        const parts: string[] = [printBranch(node.then, `v-if="${test}"`)];
        if (node.else && node.else.length === 1 && node.else[0]!.kind === "when") {
          // else-if chain
          const chained = this.printNode(node.else[0]!, ctx, part, state, opts)
            .replace(/v-if="/, `v-else-if="`);
          parts.push(chained);
        } else if (node.else && node.else.length > 0) {
          parts.push(printBranch(node.else, `v-else`));
        }
        return parts.join("\n");
      }
      case "forEach": {
        const itemsCode = ctx.propCode(node.itemsProp);
        const loop = `v-for="(item, index) in (${itemsCode} ?? [])"`;
        if (node.body.length === 1 && node.body[0]!.kind === "element") {
          return this.printNode(node.body[0]!, ctx, part, state, {
            inLoop: true,
            extraDirectives: [escapeVueAttr(loop), `:key="index"`],
          });
        }
        const inner = node.body
          .map((c) => this.printNode(c, ctx, part, state, { inLoop: true }))
          .join("\n");
        return `<template ${escapeVueAttr(loop)}>\n${indent(inner, 1)}\n</template>`;
      }
    }
  }
}

/** Escape a JS expression for use inside a double-quoted Vue directive. */
function escapeVue(code: string): string {
  return code.replace(/"/g, "'");
}

function escapeVueAttr(code: string): string {
  return code;
}
