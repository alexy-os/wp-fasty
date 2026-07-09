/**
 * React emitter — one `<stem>.tsx` per brick, forwardRef components, shared
 * classes helpers, Radix-style `asChild` via Slot where the contract allows.
 */

import type { Expr } from "../domain/expr";
import {
  fileStem,
  type AttrSpec,
  type BrickDef,
  type ElementNode,
  type Node,
  type PartDef,
  type TagSpec,
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
  type DeclaredProp,
} from "./ts-common";

const REACT_ATTR_TYPES: Record<string, [string, string]> = {
  a: ["AnchorHTMLAttributes", "HTMLAnchorElement"],
  button: ["ButtonHTMLAttributes", "HTMLButtonElement"],
  input: ["InputHTMLAttributes", "HTMLInputElement"],
  textarea: ["TextareaHTMLAttributes", "HTMLTextAreaElement"],
  select: ["SelectHTMLAttributes", "HTMLSelectElement"],
  option: ["OptionHTMLAttributes", "HTMLOptionElement"],
  optgroup: ["OptgroupHTMLAttributes", "HTMLOptGroupElement"],
  label: ["LabelHTMLAttributes", "HTMLLabelElement"],
  form: ["FormHTMLAttributes", "HTMLFormElement"],
  fieldset: ["FieldsetHTMLAttributes", "HTMLFieldSetElement"],
  legend: ["HTMLAttributes", "HTMLLegendElement"],
  img: ["ImgHTMLAttributes", "HTMLImageElement"],
  source: ["SourceHTMLAttributes", "HTMLSourceElement"],
  picture: ["HTMLAttributes", "HTMLElement"],
  dialog: ["DialogHTMLAttributes", "HTMLDialogElement"],
  details: ["DetailsHTMLAttributes", "HTMLDetailsElement"],
  summary: ["HTMLAttributes", "HTMLElement"],
  table: ["TableHTMLAttributes", "HTMLTableElement"],
  caption: ["HTMLAttributes", "HTMLTableCaptionElement"],
  thead: ["HTMLAttributes", "HTMLTableSectionElement"],
  tbody: ["HTMLAttributes", "HTMLTableSectionElement"],
  tfoot: ["HTMLAttributes", "HTMLTableSectionElement"],
  tr: ["HTMLAttributes", "HTMLTableRowElement"],
  th: ["ThHTMLAttributes", "HTMLTableCellElement"],
  td: ["TdHTMLAttributes", "HTMLTableCellElement"],
  colgroup: ["ColgroupHTMLAttributes", "HTMLTableColElement"],
  col: ["ColHTMLAttributes", "HTMLTableColElement"],
  meter: ["MeterHTMLAttributes", "HTMLMeterElement"],
  progress: ["ProgressHTMLAttributes", "HTMLProgressElement"],
  output: ["OutputHTMLAttributes", "HTMLOutputElement"],
  datalist: ["HTMLAttributes", "HTMLDataListElement"],
  hr: ["HTMLAttributes", "HTMLHRElement"],
  br: ["HTMLAttributes", "HTMLBRElement"],
  wbr: ["HTMLAttributes", "HTMLElement"],
  p: ["HTMLAttributes", "HTMLParagraphElement"],
  span: ["HTMLAttributes", "HTMLSpanElement"],
  div: ["HTMLAttributes", "HTMLDivElement"],
  li: ["LiHTMLAttributes", "HTMLLIElement"],
  ul: ["HTMLAttributes", "HTMLUListElement"],
  ol: ["OlHTMLAttributes", "HTMLOListElement"],
  nav: ["HTMLAttributes", "HTMLElement"],
  h1: ["HTMLAttributes", "HTMLHeadingElement"],
  h2: ["HTMLAttributes", "HTMLHeadingElement"],
  h3: ["HTMLAttributes", "HTMLHeadingElement"],
  h4: ["HTMLAttributes", "HTMLHeadingElement"],
  h5: ["HTMLAttributes", "HTMLHeadingElement"],
  h6: ["HTMLAttributes", "HTMLHeadingElement"],
};

/** DOM attributes that React types as numbers; printed as number expressions. */
const NUMERIC_DOM_ATTRS = new Set(["colspan", "rowspan", "span", "rows", "cols"]);

/**
 * Rewrite a string-typed expression into its numeric counterpart when it is a
 * stringified integer (React numeric DOM props render identically).
 */
function asNumberExpr(expr: Expr): Expr | undefined {
  switch (expr.kind) {
    case "intToString":
      return expr.expr;
    case "lit":
      if (typeof expr.value === "string" && /^\d+$/.test(expr.value)) {
        return { kind: "lit", value: Number(expr.value) };
      }
      return typeof expr.value === "number" ? expr : undefined;
    case "cond": {
      const thenExpr = asNumberExpr(expr.then);
      const elseExpr = asNumberExpr(expr.else);
      if (!thenExpr || !elseExpr) return undefined;
      return { ...expr, then: thenExpr, else: elseExpr };
    }
    default:
      return undefined;
  }
}

/** DOM attribute → React JSX prop name. */
function reactAttrName(name: string): string {
  const special: Record<string, string> = {
    class: "className",
    for: "htmlFor",
    tabindex: "tabIndex",
    srcset: "srcSet",
    colspan: "colSpan",
    rowspan: "rowSpan",
    fetchpriority: "fetchPriority",
    autocomplete: "autoComplete",
    enctype: "encType",
    novalidate: "noValidate",
    datetime: "dateTime",
  };
  return special[name] ?? name;
}

interface PartEmit {
  code: string;
  helperImports: Set<string>;
  usesResolveTag: boolean;
  usesTitleTag: boolean;
  usesCn: boolean;
  sharedImports: Set<string>;
  reactTypeImports: Set<string>;
  usesSlot: boolean;
  usesElementType: boolean;
  usesRefCast: boolean;
  usesSvgProps: boolean;
}

function rootElements(node: Node): ElementNode[] {
  switch (node.kind) {
    case "element":
      return [node];
    case "when":
      return [...node.then.flatMap(rootElements), ...(node.else ?? []).flatMap(rootElements)];
    default:
      return [];
  }
}

function staticTagOf(tag: TagSpec): string | undefined {
  return tag.kind === "static" ? tag.tag : undefined;
}

export class ReactEmitter implements Emitter {
  readonly runtime = "react" as const;

  constructor(private readonly utilsPath = "../../utils") {}

  emit(brick: BrickDef): GeneratedFile[] {
    const parts = brick.parts.map((part) => this.emitPart(brick, part));

    const helperImports = new Set<string>(parts.flatMap((p) => [...p.helperImports]));
    const sharedImports = new Set<string>(parts.flatMap((p) => [...p.sharedImports]));
    const reactTypeImports = new Set<string>(parts.flatMap((p) => [...p.reactTypeImports]));
    const usesResolveTag = parts.some((p) => p.usesResolveTag);
    const usesTitleTag = parts.some((p) => p.usesTitleTag);
    const usesCn = parts.some((p) => p.usesCn);
    const usesSlot = parts.some((p) => p.usesSlot);
    const usesElementType = parts.some((p) => p.usesElementType);
    const usesRefCast = parts.some((p) => p.usesRefCast);
    const usesSvgProps = parts.some((p) => p.usesSvgProps);
    if (usesTitleTag) helperImports.add("titleTag");

    const lines: string[] = [];
    lines.push(`// ${BANNER}`);
    lines.push(`/**`);
    for (const line of brick.docs.split("\n")) lines.push(` * ${line}`.trimEnd());
    lines.push(` */`);
    lines.push(``);

    const reactValueImports = ["forwardRef"];
    const reactTypes = ["type ReactNode", ...[...reactTypeImports].sort().map((t) => `type ${t}`)];
    if (usesElementType) reactTypes.push("type ElementType");
    if (usesRefCast) reactTypes.push("type Ref");
    if (usesSvgProps) reactTypes.push("type SVGProps");
    lines.push(`import { ${[...reactValueImports, ...reactTypes].join(", ")} } from "react";`);
    if (sharedImports.size > 0) {
      lines.push(
        `import { ${[...sharedImports].sort().join(", ")} } from "./${fileStem(brick)}.shared";`
      );
    }
    if (usesCn) lines.push(`import { cn } from "${this.utilsPath}";`);
    if (helperImports.size > 0) {
      lines.push(`import { ${[...helperImports].sort().join(", ")} } from "${this.utilsPath}/expr";`);
    }
    if (usesResolveTag) lines.push(`import { resolveTag, TagGroup } from "${this.utilsPath}/tags";`);
    if (usesSlot) lines.push(`import { Slot } from "../slot/slot";`);
    lines.push(``);

    for (const p of parts) {
      lines.push(p.code);
      lines.push(``);
    }

    return [
      {
        path: `ui/${brick.dir}/${fileStem(brick)}.tsx`,
        contents: lines.join("\n").replace(/\n{3,}/g, "\n\n").trimEnd() + "\n",
      },
    ];
  }

  private emitPart(brick: BrickDef, part: PartDef): PartEmit {
    const env: TypeEnv = makeEnv(brick, part);
    const decls = declaredProps(brick, part);
    const helperImports = new Set<string>();
    const sharedImports = new Set<string>();
    const reactTypeImports = new Set<string>();
    const state = {
      usesResolveTag: false,
      usesTitleTag: false,
      usesCn: false,
      usesSlot: false,
      usesElementType: false,
      usesRefCast: false,
      usesSvgProps: false,
    };

    const hasChildren = part.props.some((p) => p.type === "children");

    // Root typing.
    const roots = rootElements(part.render);
    const rootTags = roots.map((r) => staticTagOf(r.tag));
    const multiRoot = roots.length > 1;
    const hasSvgRoot = rootTags.includes("svg");
    const singleStaticTag =
      !multiRoot && rootTags.length === 1 && rootTags[0] !== undefined ? rootTags[0] : undefined;
    const isSvgRoot = singleStaticTag === "svg";
    const [attrType, elemType] =
      singleStaticTag && !isSvgRoot
        ? REACT_ATTR_TYPES[singleStaticTag] ?? ["HTMLAttributes", "HTMLElement"]
        : ["HTMLAttributes", "HTMLElement"];
    reactTypeImports.add(attrType);
    const refType = isSvgRoot
      ? "SVGSVGElement"
      : multiRoot && hasSvgRoot
        ? "HTMLElement | SVGSVGElement"
        : singleStaticTag
          ? elemType
          : "HTMLElement";
    // Casts are needed when the forwardRef generic is wider than one element.
    const needsRefCast = multiRoot || singleStaticTag === undefined || isSvgRoot;
    if (needsRefCast) state.usesRefCast = true;

    // Props type.
    for (const d of decls) {
      if (d.def.cva) sharedImports.add(`type ${variantTypeName(part, d.def.cvaKey ?? d.def.name.toLowerCase())}`);
      if (d.def.type === "items") sharedImports.add(`type ${d.def.itemsOf}`);
    }
    const omitKeys = new Set<string>(["className", "children"]);
    for (const d of decls) omitKeys.add(d.reactName);
    const propsFields: string[] = decls.map((d) => {
      const key = /^[A-Za-z_$][A-Za-z0-9_$]*$/.test(d.reactName) ? d.reactName : JSON.stringify(d.reactName);
      return `  ${key}?: ${d.tsType};`;
    });
    propsFields.push(`  className?: string;`);
    if (hasChildren) propsFields.push(`  children?: ReactNode;`);
    if (part.asChild) propsFields.push(`  asChild?: boolean;`);

    const genericAttr = attrType === "HTMLAttributes" ? `HTMLAttributes<${elemType}>` : `${attrType}<${elemType}>`;
    const omit = [...omitKeys].map((k) => JSON.stringify(k)).join(" | ");
    const propsTypeName = `${part.name}Props`;
    const propsType =
      `export type ${propsTypeName} = Omit<${genericAttr}, ${omit}> & {\n` +
      propsFields.join("\n") +
      `\n};`;

    // Destructure list.
    const destructure: string[] = decls.map((d) =>
      d.reactName === d.local || !/^[A-Za-z_$][A-Za-z0-9_$]*$/.test(d.reactName)
        ? /^[A-Za-z_$][A-Za-z0-9_$]*$/.test(d.reactName)
          ? d.reactName
          : `${JSON.stringify(d.reactName)}: ${d.local}`
        : `${d.reactName}: ${d.local}`
    );
    destructure.push("className");
    if (hasChildren) destructure.push("children");
    if (part.asChild) destructure.push("asChild");
    destructure.push("...rest");

    // Print context.
    const ctx: TsPrintCtx = {
      env,
      propCode: (name) => {
        if (name === "Class") return "className";
        const d = decls.find((x) => x.def.name === name);
        if (!d) throw new Error(`${brick.id}/${part.name}: prop ${name} not declared for React`);
        return d.local;
      },
      itemCode: (field) => `item.${localIdent(field)}`,
      derivedCode: (name) => `${name}Value`,
      resolvedTagCode: () => "resolvedTagValue",
      helpers: helperImports,
    };

    // Body statements.
    const body: string[] = [];
    if (part.classes) {
      sharedImports.add(classesHelperName(part));
      const inputs = classSpecProps(part.classes).map((n) => ctx.propCode(n));
      const args = [...inputs, "className"];
      body.push(`const cls = ${classesHelperName(part)}({ ${args.map((a) => (a === "className" ? "className" : a)).join(", ")} });`);
    }
    for (const [name, expr] of Object.entries(part.derived ?? {})) {
      if (usesItem(expr, env)) throw new Error(`${brick.id}/${part.name}: derived ${name} uses loop item`);
      body.push(`const ${name}Value = ${printTsExpr(expr, ctx)};`);
    }

    // Tag resolution for dynamic roots.
    const root = part.render;
    if (root.kind === "element" && root.tag.kind !== "static") {
      state.usesElementType = true;
      if (root.tag.kind === "resolved") {
        state.usesResolveTag = true;
        const t = root.tag;
        body.push(
          `const resolvedTagValue = resolveTag(${ctx.propCode(t.fromProp)}, ${JSON.stringify(t.fallback)}, TagGroup.${t.group});`
        );
      } else if (root.tag.kind === "heading") {
        state.usesTitleTag = true;
        body.push(`const resolvedTagValue = titleTag(${ctx.propCode(root.tag.fromProp)});`);
      } else {
        body.push(`const resolvedTagValue = ${printTsExpr(root.tag.expr, ctx)};`);
      }
      body.push(`const Tag = resolvedTagValue as ElementType;`);
    }

    // asChild branch.
    const asChildBranch = part.asChild
      ? `if (asChild) {\n` +
        `  return (\n` +
        `    <Slot ref={ref} className={${part.classes ? "cls" : "className"}} {...rest}>\n` +
        `      {children}\n` +
        `    </Slot>\n` +
        `  );\n` +
        `}\n`
      : "";
    if (part.asChild) state.usesSlot = true;

    // JSX.
    const jsx = this.printNode(root, ctx, part, state, { isRoot: true, needsRefCast });

    const fn =
      `export const ${part.name} = forwardRef<${refType}, ${propsTypeName}>(function ${part.name}(\n` +
      `  { ${destructure.join(", ")} },\n` +
      `  ref\n` +
      `) {\n` +
      indent([...body, ""].join("\n"), 1) +
      (asChildBranch ? indent(asChildBranch, 1) : "") +
      indent(`return (\n${indent(jsx, 1)}\n);`, 1) +
      `\n});\n${part.name}.displayName = ${JSON.stringify(part.name)};`;

    const docs = `/**\n${part.docs
      .split("\n")
      .map((l) => ` * ${l}`.trimEnd())
      .join("\n")}\n */`;

    return {
      code: [docs, propsType, "", fn].join("\n"),
      helperImports,
      sharedImports,
      reactTypeImports,
      usesResolveTag: state.usesResolveTag,
      usesTitleTag: state.usesTitleTag,
      usesCn: state.usesCn,
      usesSlot: state.usesSlot,
      usesElementType: state.usesElementType,
      usesRefCast: state.usesRefCast,
      usesSvgProps: state.usesSvgProps,
    };
  }

  private printAttrs(
    node: ElementNode,
    ctx: TsPrintCtx,
    part: PartDef,
    state: { usesCn: boolean; usesSvgProps: boolean },
    opts: { withRef: boolean; refCast?: string; svgCast?: boolean; inLoop: boolean }
  ): string {
    const out: string[] = [];
    if (opts.withRef) {
      out.push(opts.refCast ? `ref={ref as Ref<${opts.refCast}>}` : `ref={ref}`);
    }
    for (const attr of node.attrs) {
      out.push(this.printAttr(attr, ctx, part, state, opts));
    }
    return out.filter(Boolean).join(" ");
  }

  private printAttr(
    attr: AttrSpec,
    ctx: TsPrintCtx,
    part: PartDef,
    state: { usesCn: boolean; usesSvgProps: boolean },
    opts: { svgCast?: boolean; inLoop: boolean }
  ): string {
    switch (attr.kind) {
      case "static":
        return `${reactAttrName(attr.name)}=${JSON.stringify(attr.value)}`;
      case "expr": {
        if (isPassthroughForward(attr, part)) return "";
        const name = reactAttrName(attr.name);
        let exprToPrint = attr.expr;
        if (NUMERIC_DOM_ATTRS.has(attr.name)) {
          exprToPrint = asNumberExpr(attr.expr) ?? attr.expr;
        }
        const value = printTsExpr(exprToPrint, ctx);
        const t = exprType(exprToPrint, ctx.env);
        if (attr.when) {
          return `${name}={${printTsExpr(attr.when, ctx)} ? ${value} : undefined}`;
        }
        if (attr.keepEmpty && t === "string") {
          return `${name}={(${value}) ?? ""}`;
        }
        if (t !== "string" || neverEmptyString(attr.expr, ctx.env)) {
          return `${name}={${value}}`;
        }
        ctx.helpers.add("orUndef");
        return `${name}={orUndef(${value})}`;
      }
      case "bool":
        return `${reactAttrName(attr.name)}={${printTsExpr(attr.expr, ctx)} || undefined}`;
      case "class": {
        if (!attr.spec) return `className={cls || undefined}`;
        state.usesCn = true;
        const inline = printInlineClassSpec(attr.spec, ctx, () =>
          part.props.some((p) => p.name === "Class") && !opts.inLoop ? "className" : undefined
        );
        return `className={${inline} || undefined}`;
      }
      case "rest":
        if (opts.svgCast) {
          state.usesSvgProps = true;
          return `{...(rest as SVGProps<SVGSVGElement>)}`;
        }
        return `{...rest}`;
    }
  }

  private printNode(
    node: Node,
    ctx: TsPrintCtx,
    part: PartDef,
    state: {
      usesResolveTag: boolean;
      usesTitleTag: boolean;
      usesCn: boolean;
      usesSlot: boolean;
      usesElementType: boolean;
      usesRefCast: boolean;
      usesSvgProps: boolean;
    },
    opts: { isRoot?: boolean; needsRefCast?: boolean; inLoop?: boolean } = {}
  ): string {
    switch (node.kind) {
      case "element": {
        const tagCode =
          node.tag.kind === "static"
            ? node.tag.tag
            : opts.isRoot
              ? "Tag"
              : (() => {
                  throw new Error(`${part.name}: dynamic tags only supported at part root`);
                })();
        const isSvg = node.tag.kind === "static" && node.tag.tag === "svg";
        const refCast = opts.needsRefCast
          ? isSvg
            ? "SVGSVGElement"
            : "HTMLElement"
          : undefined;
        const attrs = this.printAttrs(node, ctx, part, state, {
          withRef: opts.isRoot === true,
          refCast,
          svgCast: isSvg,
          inLoop: opts.inLoop === true,
        });
        let open = attrs.length > 0 ? `<${tagCode} ${attrs}` : `<${tagCode}`;
        // React forbids children on <textarea>; the content contract maps to
        // defaultValue (uncontrolled initial value, same SSR output).
        if (tagCode === "textarea" && node.children.length === 1 && node.children[0]!.kind === "text") {
          const value = printTsExpr((node.children[0] as { expr: Expr }).expr, ctx);
          open += ` defaultValue={(${value}) ?? ""}`;
          return `${open} />`;
        }
        if (node.void || node.children.length === 0) return `${open} />`;
        const children = node.children
          .map((c) => this.printNode(c, ctx, part, state, { inLoop: opts.inLoop }))
          .join("\n");
        return `${open}>\n${indent(children, 1)}\n</${tagCode}>`;
      }
      case "slot":
        return `{children}`;
      case "text":
        return `{${printTsExpr(node.expr, ctx)}}`;
      case "when": {
        const test = printTsExpr(node.test, ctx);
        const inner = { ...opts, isRoot: opts.isRoot };
        const thenCode = this.wrapFragment(
          node.then.map((c) => this.printNode(c, ctx, part, state, inner))
        );
        const elseCode =
          node.else && node.else.length > 0
            ? this.wrapFragment(node.else.map((c) => this.printNode(c, ctx, part, state, inner)))
            : "null";
        const ternary = `${test} ? (\n${indent(thenCode, 1)}\n) : ${
          elseCode === "null" ? "null" : `(\n${indent(elseCode, 1)}\n)`
        }`;
        // At part root the ternary is the returned expression itself; as a
        // child it must be wrapped in JSX braces.
        return opts.isRoot ? ternary : `{${ternary}}`;
      }
      case "forEach": {
        const itemsProp = part.props.find((p) => p.name === node.itemsProp)!;
        const itemsCode = ctx.propCode(itemsProp.name);
        const body = node.body.map((c) =>
          this.printNode(c, ctx, part, state, { inLoop: true })
        );
        // Attach key to the outermost element of the loop body.
        const bodyCode = this.wrapFragment(body).replace(/^<([a-zA-Z0-9]+)(\s|>)/, `<$1 key={index}$2`);
        return `{(${itemsCode} ?? []).map((item, index) => (\n${indent(bodyCode, 1)}\n))}`;
      }
    }
  }

  private wrapFragment(parts: string[]): string {
    if (parts.length === 1) return parts[0]!;
    return `<>\n${indent(parts.join("\n"), 1)}\n</>`;
  }
}
