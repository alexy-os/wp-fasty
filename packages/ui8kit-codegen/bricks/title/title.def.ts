import { attrClass, attrRest, brick, el, pAttrs, pChildren, pClass, slot } from "../_dsl";
import titleVariants from "./title.variants.json";

export default brick({
  id: "ui.title",
  dir: "title",
  docs:
    "Title renders a heading (h1–h6) from As (1–6). Defaults to h2 when As is 0\n" +
    "or out of range.",
  recipes: { title: { file: "title.variants.json", recipe: titleVariants } },
  parts: [
    {
      name: "Title",
      docs: "Title renders the resolved heading element.",
      recipeId: "title",
      asChild: true,
      classes: { recipe: {} },
      props: [
        pClass(),
        {
          name: "As",
          type: "int",
          cva: false,
          enumValues: ["1", "2", "3", "4", "5", "6"],
          goType: "uiutils.HeadingLevel",
          docs: "maps to h1–h6; 0 and 2 default to h2",
        },
        pAttrs(),
        pChildren(),
      ],
      render: el(
        { kind: "heading", fromProp: "As" },
        [attrClass(), attrRest()],
        [slot()]
      ),
    },
  ],
});
