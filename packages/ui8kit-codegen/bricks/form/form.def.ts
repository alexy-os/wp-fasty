import {
  attrBool,
  attrClass,
  attrRest,
  attrStatic,
  brick,
  el,
  fwd,
  pAttrs,
  pBool,
  pChildren,
  pClass,
  pPass,
  prop,
  slot,
  type PartDef,
} from "../_dsl";
import formVariants from "./form.variants.json";
import formItemVariants from "./form-item.variants.json";
import formDescriptionVariants from "./form-description.variants.json";
import formMessageVariants from "./form-message.variants.json";

function recipePart(
  name: string,
  docs: string,
  tag: string,
  recipeId: string,
  extraAttrs: ReturnType<typeof attrStatic>[] = []
): PartDef {
  return {
    name,
    docs,
    recipeId,
    classes: { recipe: {} },
    props: [pClass(), pAttrs(), pChildren()],
    render: el(tag, [attrClass(), ...extraAttrs, attrRest()], [slot()]),
  };
}

export default brick({
  id: "ui.form",
  dir: "form",
  docs:
    "Form primitives: form wrapper, field item, description, and validation message.\n" +
    "Composes with Label, Input, and other controls.",
  recipes: {
    form: { file: "form.variants.json", recipe: formVariants },
    "form-item": { file: "form-item.variants.json", recipe: formItemVariants },
    "form-description": { file: "form-description.variants.json", recipe: formDescriptionVariants },
    "form-message": { file: "form-message.variants.json", recipe: formMessageVariants },
  },
  parts: [
    {
      name: "Form",
      docs: "Form renders a vertical grid form container.",
      recipeId: "form",
      classes: { recipe: {} },
      props: [
        pPass("ID"),
        pClass(),
        pPass("Action"),
        pPass("Method"),
        pPass("Enctype"),
        pPass("Autocomplete"),
        pPass("Name"),
        pPass("Target"),
        pBool("NoValidate"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "form",
        [
          fwd("id", "ID"),
          attrClass(),
          fwd("action", "Action"),
          fwd("method", "Method"),
          fwd("enctype", "Enctype"),
          fwd("autocomplete", "Autocomplete"),
          fwd("name", "Name"),
          fwd("target", "Target"),
          attrBool("novalidate", prop("NoValidate")),
          attrRest(),
        ],
        [slot()]
      ),
    },
    recipePart(
      "FormItem",
      "FormItem groups a single field with spacing for label and control.",
      "div",
      "form-item"
    ),
    recipePart(
      "FormDescription",
      "FormDescription renders muted helper copy below a control.",
      "p",
      "form-description"
    ),
    recipePart(
      "FormMessage",
      "FormMessage renders destructive validation feedback.",
      "p",
      "form-message",
      [attrStatic("role", "alert")]
    ),
  ],
});
