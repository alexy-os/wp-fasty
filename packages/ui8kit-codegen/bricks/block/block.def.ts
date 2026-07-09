import {
  attrClass,
  attrRest,
  brick,
  el,
  fwd,
  pAttrs,
  pChildren,
  pClass,
  pPass,
  pStr,
  slot,
} from "../_dsl";
import blockVariants from "./block.variants.json";

export default brick({
  id: "ui.block",
  dir: "block",
  docs:
    "Block is a top-level page landmark (main, header, section, nav, …).\n" +
    "Use for document structure; do not nest Block inside Block.",
  recipes: { block: { file: "block.variants.json", recipe: blockVariants } },
  parts: [
    {
      name: "Block",
      docs: "Block renders a resolved landmark element with children.",
      recipeId: "block",
      asChild: true,
      classes: { recipe: {} },
      props: [
        pPass("ID"),
        pClass(),
        pStr("Tag", "semantic landmark: main, header, section, footer, nav, article, aside, figure"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        { kind: "resolved", fromProp: "Tag", fallback: "div", group: "Layout" },
        [attrClass(), fwd("id", "ID"), attrRest()],
        [slot()]
      ),
    },
  ],
});
