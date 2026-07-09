import {
  attrBool,
  attrClass,
  attrRest,
  brick,
  el,
  fwd,
  pAttrs,
  pBool,
  pChildren,
  pClass,
  pPass,
  prop,
  pSize,
  pVariant,
  slot,
} from "../_dsl";
import dialogVariants from "./dialog.variants.json";

export default brick({
  id: "ui.dialog",
  dir: "dialog",
  docs:
    "Dialog renders a native dialog root with an optional behavior hook.\n" +
    "The data-ui8kit hook defaults off; the registry ships no client JS.",
  recipes: { dialog: { file: "dialog.variants.json", recipe: dialogVariants } },
  parts: [
    {
      name: "Dialog",
      docs: "Dialog renders a native dialog element.",
      recipeId: "dialog",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [
        pVariant(),
        pSize(),
        pClass(),
        pPass("ID"),
        pBool("Open", "initial open state (static attribute only)"),
        pPass("DataUI8Kit", "opt-in behavior hook; empty emits nothing"),
        pPass("AriaLabel"),
        pPass("AriaLabelledBy"),
        pPass("AriaDescribedBy"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "dialog",
        [
          attrClass(),
          attrBool("open", prop("Open")),
          fwd("id", "ID"),
          fwd("data-ui8kit", "DataUI8Kit"),
          fwd("aria-label", "AriaLabel"),
          fwd("aria-labelledby", "AriaLabelledBy"),
          fwd("aria-describedby", "AriaDescribedBy"),
          attrRest(),
        ],
        [slot()]
      ),
    },
  ],
});
