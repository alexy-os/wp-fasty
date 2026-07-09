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
import badgeVariants from "./badge.variants.json";

export default brick({
  id: "ui.badge",
  dir: "badge",
  docs: "Badge shows a short status label.\nBadge uses variant and size presets.",
  recipes: { badge: { file: "badge.variants.json", recipe: badgeVariants } },
  parts: [
    {
      name: "Badge",
      docs: "Badge renders label text in a div wrapper.",
      recipeId: "badge",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [
        pVariant("color preset: default, secondary, destructive, outline"),
        pSize("density preset: default, sm, lg"),
        pClass(),
        pAttrs(),
        pChildren(),
      ],
      render: el("div", [attrClass(), attrRest()], [slot()]),
    },
  ],
});
