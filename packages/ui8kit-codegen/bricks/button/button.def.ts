import {
  attrBool,
  attrClass,
  attrExpr,
  attrRest,
  brick,
  controlPassthroughProps,
  el,
  fwd,
  oneOfOr,
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
} from "../_dsl";
import buttonVariants from "./button.variants.json";

export default brick({
  id: "ui.button",
  dir: "button",
  docs:
    "Button triggers an action on click.\n" +
    "For navigation, render an anchor styled via the ButtonClasses helper\n" +
    "(Templ/Svelte/Vue) or use asChild (React).",
  recipes: { button: { file: "button.variants.json", recipe: buttonVariants } },
  parts: [
    {
      name: "Button",
      docs: "Button renders a button element with variant classes.",
      recipeId: "button",
      asChild: true,
      classes: {
        recipe: { variant: "Variant", size: "Size" },
        state: [{ test: prop("Disabled"), classes: "pointer-events-none opacity-50" }],
      },
      props: [
        pVariant("appearance preset: default, secondary, destructive, outline, ghost, link"),
        pSize("density preset: default, sm, lg, icon"),
        pClass(),
        pStr("Type", "button type: button, submit, reset"),
        pPass("Form", "links button to a form id"),
        pBool("Disabled", "blocks interaction"),
        ...controlPassthroughProps(),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "button",
        [
          attrExpr("type", oneOfOr(prop("Type"), ["submit", "reset"], "button")),
          attrClass(),
          attrBool("disabled", prop("Disabled")),
          fwd("id", "ID"),
          fwd("form", "Form"),
          fwd("role", "Role"),
          fwd("tabindex", "TabIndex"),
          fwd("aria-label", "AriaLabel"),
          attrRest(),
        ],
        [slot()]
      ),
    },
  ],
});
