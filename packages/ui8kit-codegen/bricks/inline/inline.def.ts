import { attrClass, attrRest, brick, el, pAttrs, pChildren, pClass, slot } from "../_dsl";
import inlineVariants from "./inline.variants.json";

export default brick({
  id: "ui.inline",
  dir: "inline",
  docs:
    "Inline renders inline copy as a span. Use Text for paragraph blocks and\n" +
    "Title (as 1–6) for headings.",
  recipes: { inline: { file: "inline.variants.json", recipe: inlineVariants } },
  parts: [
    {
      name: "Inline",
      docs: "Inline renders a span element with inline copy classes.",
      recipeId: "inline",
      asChild: true,
      classes: { recipe: {} },
      props: [pClass(), pAttrs(), pChildren()],
      render: el("span", [attrClass(), attrRest()], [slot()]),
    },
  ],
});
