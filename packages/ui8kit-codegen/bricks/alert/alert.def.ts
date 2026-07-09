import {
  attrClass,
  attrExpr,
  attrRest,
  brick,
  defaultIfEmpty,
  el,
  oneOfOr,
  pAttrs,
  pChildren,
  pClass,
  prop,
  pStr,
  pVariant,
  slot,
} from "../_dsl";
import alertVariants from "./alert.variants.json";

export default brick({
  id: "ui.alert",
  dir: "alert",
  docs:
    "Alert displays a static status or informational message.\n" +
    "Renders a live region with role=status by default; children carry the message body.",
  recipes: { alert: { file: "alert.variants.json", recipe: alertVariants } },
  parts: [
    {
      name: "Alert",
      docs: "Alert renders a div with alert or status semantics.",
      recipeId: "alert",
      asChild: true,
      classes: { recipe: { variant: "Variant" } },
      props: [
        pClass(),
        pVariant("default, destructive, or wireframe preset"),
        {
          name: "Role",
          type: "string",
          cva: false,
          enumValues: ["status", "alert"],
          docs: "status or alert (defaults to status)",
        },
        {
          name: "AriaLive",
          type: "string",
          cva: false,
          enumValues: ["off", "polite", "assertive"],
          docs: "polite or assertive (defaults to polite)",
        },
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "div",
        [
          attrClass(),
          attrExpr("role", oneOfOr(prop("Role"), ["status", "alert"], "status")),
          attrExpr("aria-live", defaultIfEmpty(prop("AriaLive"), "polite")),
          attrRest(),
        ],
        [slot()]
      ),
    },
  ],
});
