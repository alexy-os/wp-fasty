import { attrClass, attrRest, brick, el, pAttrs, pChildren, pClass, pStr, slot } from "../_dsl";
import containerVariants from "./container.variants.json";

export default brick({
  id: "ui.container",
  dir: "container",
  docs:
    "Container wraps page content with a constrained width shell.\n" +
    "Optional main or section tag for inner layout regions.",
  recipes: { container: { file: "container.variants.json", recipe: containerVariants } },
  parts: [
    {
      name: "Container",
      docs: "Container renders the outer content shell.",
      recipeId: "container",
      asChild: true,
      classes: { recipe: {} },
      props: [pClass(), pStr("Tag", "div, main, or section"), pAttrs(), pChildren()],
      render: el(
        { kind: "resolved", fromProp: "Tag", fallback: "div", group: "Container" },
        [attrClass(), attrRest()],
        [slot()]
      ),
    },
  ],
});
