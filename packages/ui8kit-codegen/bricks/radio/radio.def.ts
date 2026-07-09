import {
  attrBool,
  attrClass,
  attrRest,
  attrStatic,
  brick,
  controlPassthroughProps,
  el,
  fwd,
  pAttrs,
  pBool,
  pClass,
  pPass,
  prop,
  pSize,
  pVariant,
} from "../_dsl";
import radioVariants from "./radio.variants.json";

export default brick({
  id: "ui.radio",
  dir: "radio",
  docs: "Radio is a native single-choice control within a named group.",
  recipes: { radio: { file: "radio.variants.json", recipe: radioVariants } },
  parts: [
    {
      name: "Radio",
      docs: "Radio renders input[type=radio] with shared Name for grouping.",
      recipeId: "radio",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [
        pVariant(),
        pSize(),
        pClass(),
        pPass("Name"),
        pPass("Value"),
        pBool("Checked"),
        pBool("Disabled"),
        pBool("Required"),
        ...controlPassthroughProps(),
        pAttrs(),
      ],
      render: el(
        "input",
        [
          fwd("id", "ID"),
          attrStatic("type", "radio"),
          fwd("name", "Name"),
          fwd("value", "Value"),
          attrClass(),
          attrBool("checked", prop("Checked")),
          attrBool("disabled", prop("Disabled")),
          attrBool("required", prop("Required")),
          fwd("role", "Role"),
          fwd("tabindex", "TabIndex"),
          fwd("aria-label", "AriaLabel"),
          attrRest(),
        ],
        [],
        { void: true }
      ),
    },
  ],
});
