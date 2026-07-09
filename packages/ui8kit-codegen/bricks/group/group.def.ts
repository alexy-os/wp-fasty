import { attrClass, attrRest, brick, el, pAttrs, pChildren, pClass, pStr, slot } from "../_dsl";
import groupVariants from "./group.variants.json";

export default brick({
  id: "ui.group",
  dir: "group",
  docs:
    "Group arranges children in a horizontal flex row.\n" +
    "Supports fieldset for related form controls.",
  recipes: { group: { file: "group.variants.json", recipe: groupVariants } },
  parts: [
    {
      name: "Group",
      docs: "Group renders a horizontal flex container.",
      recipeId: "group",
      asChild: true,
      classes: { recipe: {} },
      props: [
        pClass(),
        pStr("Tag", "div, fieldset, or dl. Use fieldset for related form controls."),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        { kind: "resolved", fromProp: "Tag", fallback: "div", group: "Group" },
        [attrClass(), attrRest()],
        [slot()]
      ),
    },
  ],
});
