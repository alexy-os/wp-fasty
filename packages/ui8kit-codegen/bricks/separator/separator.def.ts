import {
  attrClass,
  attrExpr,
  attrRest,
  brick,
  el,
  eq,
  lit,
  pAttrs,
  pBool,
  pClass,
  pCva,
  prop,
  pVariant,
} from "../_dsl";
import separatorVariants from "./separator.variants.json";

export default brick({
  id: "ui.separator",
  dir: "separator",
  docs: "Separator renders a semantic or decorative hr.",
  recipes: { separator: { file: "separator.variants.json", recipe: separatorVariants } },
  parts: [
    {
      name: "Separator",
      docs: "Separator renders hr with optional decorative semantics.",
      recipeId: "separator",
      classes: { recipe: { variant: "Variant", orientation: "Orientation" } },
      props: [
        pVariant(),
        pCva("Orientation", "orientation", "horizontal or vertical"),
        pClass(),
        pBool("Decorative", "hides the rule from assistive technology"),
        pAttrs(),
      ],
      render: el(
        "hr",
        [
          attrClass(),
          attrExpr("role", lit("presentation"), { when: prop("Decorative") }),
          attrExpr("aria-hidden", lit("true"), { when: prop("Decorative") }),
          attrExpr("aria-orientation", lit("vertical"), {
            when: eq(prop("Orientation"), lit("vertical")),
          }),
          attrRest(),
        ],
        [],
        { void: true }
      ),
    },
  ],
});
