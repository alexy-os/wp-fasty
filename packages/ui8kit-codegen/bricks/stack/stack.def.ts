import { attrClass, attrRest, brick, el, pAttrs, pChildren, pClass, pStr, slot } from "../_dsl";
import stackVariants from "./stack.variants.json";

export default brick({
  id: "ui.stack",
  dir: "stack",
  docs:
    "Stack arranges children vertically in a flex column.\n" +
    "Default layout primitive for spacing sections and form fields.",
  recipes: { stack: { file: "stack.variants.json", recipe: stackVariants } },
  parts: [
    {
      name: "Stack",
      docs: "Stack renders a vertical flex container.",
      recipeId: "stack",
      asChild: true,
      classes: { recipe: {} },
      props: [
        pClass(),
        pStr("Tag", "div, ul, or ol. Use Block for landmarks (aside, section, header, nav)."),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        { kind: "resolved", fromProp: "Tag", fallback: "div", group: "Stack" },
        [attrClass(), attrRest()],
        [slot()]
      ),
    },
  ],
});
