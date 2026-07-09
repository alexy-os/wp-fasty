import {
  attrBool,
  attrClass,
  attrExpr,
  attrRest,
  brick,
  controlPassthroughProps,
  el,
  eq,
  forEach,
  fwd,
  item,
  pAttrs,
  pBool,
  pChildren,
  pClass,
  pPass,
  prop,
  pSize,
  pStr,
  pVariant,
  slot,
  text,
} from "../_dsl";
import selectVariants from "./select.variants.json";

export default brick({
  id: "ui.select",
  dir: "select",
  goPackage: "selectfield",
  docs: "Select is a native dropdown built from Option items.",
  recordTypes: [
    {
      name: "SelectOptionItem",
      fields: [
        { name: "Value", type: "string" },
        { name: "Label", type: "string" },
      ],
    },
  ],
  recipes: { select: { file: "select.variants.json", recipe: selectVariants } },
  parts: [
    {
      name: "Select",
      docs: "Select renders option children from the Options list plus manual children.",
      recipeId: "select",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [
        pVariant(),
        pSize(),
        pClass(),
        pPass("Name"),
        pStr("Value", "currently selected option value"),
        pBool("Disabled"),
        pBool("Required"),
        { name: "Options", type: "items", cva: false, itemsOf: "SelectOptionItem" },
        ...controlPassthroughProps(),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "select",
        [
          fwd("id", "ID"),
          fwd("name", "Name"),
          attrClass(),
          attrBool("disabled", prop("Disabled")),
          attrBool("required", prop("Required")),
          fwd("role", "Role"),
          fwd("tabindex", "TabIndex"),
          fwd("aria-label", "AriaLabel"),
          attrRest(),
        ],
        [
          forEach("Options", [
            el(
              "option",
              [
                attrExpr("value", item("Value"), { keepEmpty: true }),
                attrBool("selected", eq(item("Value"), prop("Value"))),
              ],
              [text(item("Label"))]
            ),
          ]),
          slot(),
        ]
      ),
    },
    {
      name: "SelectOption",
      docs: "SelectOption renders an option for custom Select children.",
      props: [
        pPass("Value"),
        pPass("Label"),
        pBool("Selected"),
        pBool("Disabled"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "option",
        [
          fwd("value", "Value"),
          fwd("label", "Label"),
          attrBool("selected", prop("Selected")),
          attrBool("disabled", prop("Disabled")),
          attrRest(),
        ],
        [slot()]
      ),
    },
    {
      name: "OptGroup",
      docs: "OptGroup renders a native optgroup around option children.",
      classes: {},
      props: [pClass(), pPass("Label"), pBool("Disabled"), pAttrs(), pChildren()],
      render: el(
        "optgroup",
        [
          attrClass(),
          fwd("label", "Label"),
          attrBool("disabled", prop("Disabled")),
          attrRest(),
        ],
        [slot()]
      ),
    },
  ],
});
