import {
  and,
  attrClass,
  attrExpr,
  attrRest,
  brick,
  el,
  eq,
  intToString,
  isSet,
  lit,
  pAttrs,
  pChildren,
  pClass,
  pInt,
  prop,
  pStr,
  resolvedTag,
  slot,
} from "../_dsl";
import listVariants from "./list.variants.json";

export default brick({
  id: "ui.list",
  dir: "list",
  docs: "List renders semantic ul, ol, dl, or menu lists with ListItem children.",
  recipes: { list: { file: "list.variants.json", recipe: listVariants } },
  parts: [
    {
      name: "List",
      docs: "List renders a list container (ul, ol, dl, or menu).",
      recipeId: "list",
      classes: { recipe: {} },
      props: [pClass(), pStr("Tag", "ul, ol, dl, or menu"), pAttrs(), pChildren()],
      render: el(
        { kind: "resolved", fromProp: "Tag", fallback: "ul", group: "List" },
        [attrClass(), attrRest()],
        [slot()]
      ),
    },
    {
      name: "ListItem",
      docs: "ListItem renders one li with optional value attribute.",
      recipeId: "list",
      classes: { recipe: {} },
      props: [
        pClass(),
        pStr("Tag", "li, dt, or dd; defaults to li"),
        pInt("Value", "optional li value for ordered contexts"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        { kind: "resolved", fromProp: "Tag", fallback: "li", group: "ListItem" },
        [
          attrClass(),
          attrExpr("value", intToString(prop("Value")), {
            when: and(isSet(prop("Value")), eq(resolvedTag(), lit("li"))),
          }),
          attrRest(),
        ],
        [slot()]
      ),
    },
  ],
});
