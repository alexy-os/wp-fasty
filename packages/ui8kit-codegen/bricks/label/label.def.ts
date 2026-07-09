import { attrClass, attrRest, brick, el, fwd, pAttrs, pChildren, pClass, pPass, slot } from "../_dsl";
import labelVariants from "./label.variants.json";

export default brick({
  id: "ui.label",
  dir: "label",
  docs: "Label associates visible text with a form control via the for attribute.",
  recipes: { label: { file: "label.variants.json", recipe: labelVariants } },
  parts: [
    {
      name: "Label",
      docs: "Label renders accessible text linked to a control.",
      recipeId: "label",
      classes: { recipe: {} },
      props: [pClass(), pPass("HTMLFor", "id of the labeled control"), pAttrs(), pChildren()],
      render: el("label", [attrClass(), fwd("for", "HTMLFor"), attrRest()], [slot()]),
    },
  ],
});
