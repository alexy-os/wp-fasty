import {
  attrClass,
  attrExpr,
  attrRest,
  brick,
  cond,
  derived,
  el,
  eq,
  fwd,
  isSet,
  lit,
  or,
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
import linkVariants from "./link.variants.json";

export default brick({
  id: "ui.link",
  dir: "link",
  docs:
    "Link renders a semantic anchor for navigation and prose.\n" +
    "Use Button for actions; use Link for destinations.",
  recipes: { link: { file: "link.variants.json", recipe: linkVariants } },
  parts: [
    {
      name: "Link",
      docs: "Link renders a semantic anchor element.",
      recipeId: "link",
      classes: {
        recipe: { variant: "Variant", size: "Size" },
        state: [{ test: prop("Disabled"), classes: "pointer-events-none opacity-50" }],
      },
      derived: {
        target: cond(isSet(prop("Target")), prop("Target"), cond(prop("External"), lit("_blank"), lit(""))),
        rel: cond(
          isSet(prop("Rel")),
          prop("Rel"),
          cond(
            or(prop("External"), eq(derived("target"), lit("_blank"))),
            lit("noopener noreferrer"),
            lit("")
          )
        ),
      },
      props: [
        pVariant(),
        pSize(),
        pClass(),
        pPass("Href"),
        pStr("Target", "explicit target; External implies _blank"),
        pStr("Rel", "explicit rel; external links default to noopener noreferrer"),
        pPass("Download"),
        pBool("External", "opens in a new tab with safe rel defaults"),
        pBool("Disabled", "visually and semantically disables the link"),
        pPass("AriaCurrent"),
        pPass("AriaLabel"),
        pPass("ID"),
        pPass("Role"),
        pAttrs(),
        pChildren(),
      ],
      render: el(
        "a",
        [
          fwd("href", "Href"),
          attrClass(),
          fwd("id", "ID"),
          attrExpr("target", derived("target")),
          attrExpr("rel", derived("rel")),
          fwd("download", "Download"),
          fwd("role", "Role"),
          fwd("aria-label", "AriaLabel"),
          fwd("aria-current", "AriaCurrent"),
          attrExpr("aria-disabled", lit("true"), { when: prop("Disabled") }),
          attrExpr("tabindex", lit(-1), { when: prop("Disabled") }),
          attrRest(),
        ],
        [slot()]
      ),
    },
  ],
});
