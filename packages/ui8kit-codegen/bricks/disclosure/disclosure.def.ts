import {
  attrBool,
  attrClass,
  attrRest,
  brick,
  el,
  pAttrs,
  pBool,
  pChildren,
  pClass,
  prop,
  pSize,
  pVariant,
  slot,
} from "../_dsl";
import disclosureVariants from "./disclosure.variants.json";
import summaryVariants from "./summary.variants.json";

export default brick({
  id: "ui.disclosure",
  dir: "disclosure",
  docs:
    "Disclosure renders native details and summary elements.\n" +
    "Open is a static attribute only — no controlled-state contract.",
  recipes: {
    disclosure: { file: "disclosure.variants.json", recipe: disclosureVariants },
    summary: { file: "summary.variants.json", recipe: summaryVariants },
  },
  parts: [
    {
      name: "Disclosure",
      docs: "Disclosure renders native details.",
      recipeId: "disclosure",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [
        pVariant(),
        pSize(),
        pClass(),
        pBool("Open", "initial open state (static attribute only)"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "details",
        [attrClass(), attrBool("open", prop("Open")), attrRest()],
        [slot()]
      ),
    },
    {
      name: "Summary",
      docs: "Summary renders native summary.",
      recipeId: "summary",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [pVariant(), pSize(), pClass(), pAttrs(), pChildren()],
      render: el("summary", [attrClass(), attrRest()], [slot()]),
    },
  ],
});
