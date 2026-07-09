import { attrClass, attrRest, brick, el, mapOr, pAttrs, pClass, prop, pStr } from "../_dsl";
import linebreakVariants from "./linebreak.variants.json";

export default brick({
  id: "ui.linebreak",
  dir: "linebreak",
  docs: "Break renders br or wbr for controlled inline wrapping.",
  recipes: { linebreak: { file: "linebreak.variants.json", recipe: linebreakVariants } },
  parts: [
    {
      name: "Break",
      docs: "Break renders br by default, or wbr when Type is wbr/word.",
      recipeId: "linebreak",
      classes: { recipe: {} },
      props: [pStr("Type", "br (default) or wbr/word"), pClass(), pAttrs()],
      render: el(
        {
          kind: "mapped",
          expr: mapOr(prop("Type"), { wbr: "wbr", word: "wbr" }, "br"),
          tags: ["wbr", "br"],
        },
        [attrClass(), attrRest()],
        [],
        { void: true }
      ),
    },
  ],
});
