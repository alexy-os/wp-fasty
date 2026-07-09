import { attrClass, attrRest, brick, el, pAttrs, pChildren, pClass, pStr, slot } from "../_dsl";
import boxVariants from "./box.variants.json";

export default brick({
  id: "ui.box",
  dir: "box",
  docs:
    "Box is a generic layout wrapper for internal sections.\n" +
    "Not for top-level landmarks — use Block for page regions.",
  recipes: { box: { file: "box.variants.json", recipe: boxVariants } },
  parts: [
    {
      name: "Box",
      docs: "Box renders a div layout wrapper.",
      recipeId: "box",
      asChild: true,
      classes: { recipe: {} },
      props: [
        pClass(),
        pStr("Tag", "div only. Use Block for landmarks (aside, section, header, nav)."),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        { kind: "resolved", fromProp: "Tag", fallback: "div", group: "BoxAllowed" },
        [attrClass(), attrRest()],
        [slot()]
      ),
    },
  ],
});
