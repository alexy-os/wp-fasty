import {
  attrClass,
  attrRest,
  brick,
  el,
  pAttrs,
  pChildren,
  pClass,
  pSize,
  pVariant,
  slot,
} from "../_dsl";
import iconbadgeVariants from "./iconbadge.variants.json";

export default brick({
  id: "ui.iconbadge",
  dir: "iconbadge",
  docs:
    "IconBadge renders a small icon surface for nav rows, cards, and status blocks.\n" +
    "Pass the letter, glyph, or Icon node as children.",
  recipes: { iconbadge: { file: "iconbadge.variants.json", recipe: iconbadgeVariants } },
  parts: [
    {
      name: "IconBadge",
      docs: "IconBadge renders children as the badge content.",
      recipeId: "iconbadge",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [pVariant(), pSize(), pClass(), pAttrs(), pChildren()],
      render: el("span", [attrClass(), attrRest()], [slot()]),
    },
  ],
});
