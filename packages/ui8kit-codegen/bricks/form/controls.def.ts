import {
  attrBool,
  attrClass,
  attrRest,
  brick,
  el,
  fwd,
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
import fieldsetVariants from "./fieldset.variants.json";
import legendVariants from "./legend.variants.json";
import meterVariants from "./meter.variants.json";
import progressVariants from "./progress.variants.json";

export default brick({
  id: "ui.form.controls",
  dir: "form",
  file: "controls",
  goPackage: "form",
  docs: "Form controls covers fieldset, legend, datalist, output, meter, and progress.",
  recipes: {
    fieldset: { file: "fieldset.variants.json", recipe: fieldsetVariants },
    legend: { file: "legend.variants.json", recipe: legendVariants },
    meter: { file: "meter.variants.json", recipe: meterVariants },
    progress: { file: "progress.variants.json", recipe: progressVariants },
  },
  parts: [
    {
      name: "Fieldset",
      docs: "Fieldset renders a native fieldset.",
      recipeId: "fieldset",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [
        pVariant(),
        pSize(),
        pClass(),
        pPass("Name"),
        pPass("Form"),
        pBool("Disabled"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "fieldset",
        [
          attrClass(),
          fwd("name", "Name"),
          fwd("form", "Form"),
          attrBool("disabled", prop("Disabled")),
          attrRest(),
        ],
        [slot()]
      ),
    },
    {
      name: "Legend",
      docs: "Legend renders a native legend.",
      recipeId: "legend",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [pVariant(), pSize(), pClass(), pAttrs(), pChildren()],
      render: el("legend", [attrClass(), attrRest()], [slot()]),
    },
    {
      name: "DataList",
      docs: "DataList renders a native datalist.",
      classes: {},
      props: [pPass("ID"), pClass(), pAttrs(), pChildren()],
      render: el("datalist", [fwd("id", "ID"), attrClass(), attrRest()], [slot()]),
    },
    {
      name: "DataOption",
      docs: "DataOption renders an option inside a datalist.",
      props: [pPass("Value"), pPass("Label"), pAttrs()],
      render: el("option", [fwd("value", "Value"), fwd("label", "Label"), attrRest()], []),
    },
    {
      name: "Output",
      docs: "Output renders a native output element.",
      classes: {},
      props: [
        pPass("ID"),
        pClass(),
        pPass("Name"),
        pPass("For"),
        pStr("Value", "initial output text"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "output",
        [fwd("id", "ID"), attrClass(), fwd("name", "Name"), fwd("for", "For"), attrRest()],
        [text(prop("Value")), slot()]
      ),
    },
    {
      name: "Meter",
      docs: "Meter renders a native meter.",
      recipeId: "meter",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [
        pVariant(),
        pSize(),
        pPass("ID"),
        pClass(),
        pPass("Value"),
        pPass("Min"),
        pPass("Max"),
        pPass("Low"),
        pPass("High"),
        pPass("Optimum"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "meter",
        [
          fwd("id", "ID"),
          attrClass(),
          fwd("value", "Value"),
          fwd("min", "Min"),
          fwd("max", "Max"),
          fwd("low", "Low"),
          fwd("high", "High"),
          fwd("optimum", "Optimum"),
          attrRest(),
        ],
        [slot()]
      ),
    },
    {
      name: "Progress",
      docs: "Progress renders a native progress element.",
      recipeId: "progress",
      classes: { recipe: { variant: "Variant", size: "Size" } },
      props: [
        pVariant(),
        pSize(),
        pPass("ID"),
        pClass(),
        pPass("Value"),
        pPass("Max"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "progress",
        [fwd("id", "ID"), attrClass(), fwd("value", "Value"), fwd("max", "Max"), attrRest()],
        [slot()]
      ),
    },
  ],
});
