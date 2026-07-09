import {
  attrClass,
  attrRest,
  brick,
  el,
  pAttrs,
  pChildren,
  pClass,
  pVariant,
  slot,
  type PartDef,
} from "../_dsl";
import cardVariants from "./card.variants.json";

function staticPart(name: string, docs: string, tag: string, base: string): PartDef {
  return {
    name,
    docs,
    classes: { staticBase: base },
    props: [pClass(), pAttrs(), pChildren()],
    render: el(tag, [attrClass(), attrRest()], [slot()]),
  };
}

export default brick({
  id: "ui.card",
  dir: "card",
  docs:
    "Card groups related content in one bordered surface.\n" +
    "Card uses header, content, and footer slots.\n" +
    "For semantic root elements (section, article), use CardClasses on a manual wrapper.",
  recipes: { card: { file: "card.variants.json", recipe: cardVariants } },
  parts: [
    {
      name: "Card",
      docs: "Card renders the bordered surface wrapper.",
      recipeId: "card",
      asChild: true,
      classes: { recipe: { variant: "Variant" } },
      props: [
        pClass(),
        pVariant("surface preset: default, raised, kpi, muted, ghost, compact, flat, accent"),
        pAttrs(),
        pChildren(),
      ],
      render: el("div", [attrClass(), attrRest()], [slot()]),
    },
    staticPart(
      "CardHeader",
      "CardHeader renders the top slot with bottom border.",
      "div",
      "border-b border-border px-4 py-2"
    ),
    {
      name: "CardTitle",
      docs: "CardTitle renders heading text at the As level (default h2).",
      classes: { staticBase: "text-sm font-semibold" },
      props: [
        pClass(),
        {
          name: "As",
          type: "int",
          cva: false,
          enumValues: ["1", "2", "3", "4", "5", "6"],
          goType: "uiutils.HeadingLevel",
          docs: "maps to h1-h6 (default h2)",
        },
        pAttrs(),
        pChildren(),
      ],
      render: el({ kind: "heading", fromProp: "As" }, [attrClass(), attrRest()], [slot()]),
    },
    staticPart(
      "CardDescription",
      "CardDescription renders a muted subtitle in a p element.",
      "p",
      "text-sm text-muted-foreground"
    ),
    staticPart("CardContent", "CardContent renders the padded main body slot.", "div", "p-4"),
    staticPart(
      "CardFooter",
      "CardFooter renders the bottom action slot with top border.",
      "div",
      "border-t border-border px-4 py-3"
    ),
  ],
});
