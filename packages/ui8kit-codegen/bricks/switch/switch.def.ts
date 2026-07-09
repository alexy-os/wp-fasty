import {
  attrBool,
  attrClass,
  attrExpr,
  attrRest,
  attrStatic,
  boolToString,
  brick,
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
import switchVariants from "./switch.variants.json";

export default brick({
  id: "ui.switch",
  dir: "switch",
  goPackage: "formswitch",
  docs:
    "Switch is a toggle control (checkbox with switch ARIA semantics).\n" +
    "Use for on/off settings; wireframe uses native checkbox styling.",
  recipes: { switch: { file: "switch.variants.json", recipe: switchVariants } },
  parts: [
    {
      name: "Switch",
      docs: "Switch renders input[type=checkbox] with switch role attrs.",
      recipeId: "switch",
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
        pPass("ID"),
        pPass("TabIndex"),
        pPass("AriaLabel"),
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
          fwd("tabindex", "TabIndex"),
          fwd("aria-label", "AriaLabel"),
          attrStatic("role", "switch"),
          attrExpr("aria-checked", boolToString(prop("Checked"))),
          attrRest(),
        ],
        [],
        { void: true }
      ),
    },
  ],
});
