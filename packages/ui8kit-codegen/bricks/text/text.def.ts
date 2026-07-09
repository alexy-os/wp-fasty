import { attrClass, attrRest, brick, el, pAttrs, pChildren, pClass, slot } from "../_dsl";
import textVariants from "./text.variants.json";

export default brick({
  id: "ui.text",
  dir: "text",
  docs:
    "Text renders a paragraph of body copy. Use Inline for inline span copy\n" +
    "and Title (as 1–6) for headings.",
  recipes: { text: { file: "text.variants.json", recipe: textVariants } },
  parts: [
    {
      name: "Text",
      docs: "Text renders a p element with body copy classes.",
      recipeId: "text",
      asChild: true,
      classes: { recipe: {} },
      props: [pClass(), pAttrs(), pChildren()],
      render: el("p", [attrClass(), attrRest()], [slot()]),
    },
  ],
});
