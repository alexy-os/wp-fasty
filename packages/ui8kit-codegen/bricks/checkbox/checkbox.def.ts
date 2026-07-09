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
import checkboxVariants from "./checkbox.variants.json";

export default brick({
  id: "ui.checkbox",
  dir: "checkbox",
  docs:
    "Checkbox is a native boolean form control.\n" +
    "Wireframe styling via Variant and Size; supports standard ARIA attrs.",
  recipes: { checkbox: { file: "checkbox.variants.json", recipe: checkboxVariants } },
  parts: [
    {
      name: "Checkbox",
      docs: "Checkbox renders a styled input[type=checkbox].",
      recipeId: "checkbox",
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
          attrStatic("type", "checkbox"),
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
