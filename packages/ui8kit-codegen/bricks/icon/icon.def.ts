import {
  and,
  attrClass,
  attrExpr,
  attrRest,
  brick,
  concat,
  cond,
  derived,
  el,
  eq,
  isSet,
  lit,
  mapOr,
  not,
  or,
  pAttrs,
  pBool,
  pChildren,
  pClass,
  prop,
  pSize,
  pStr,
  slot,
  text,
  when,
  type PartDef,
} from "../_dsl";
import iconVariants from "./icon.variants.json";

const typeExpr = mapOr(prop("Type"), { svg: "svg", text: "text" }, "class");

const ariaAttrs = [
  attrExpr("aria-hidden", lit("true"), { when: derived("isDecorative") }),
  attrExpr("role", lit("img"), { when: not(derived("isDecorative")) }),
  attrExpr("aria-label", prop("AriaLabel"), {
    when: and(not(derived("isDecorative")), isSet(prop("AriaLabel"))),
  }),
];

const iconPart: PartDef = {
  name: "Icon",
  docs: "Icon renders a class-based span, svg use, or text fallback host.",
  recipeId: "icon",
  classes: {
    recipe: { type: typeExpr, size: "Size" },
    extra: [
      cond(eq(typeExpr, lit("class")), prop("BaseClass"), lit("")),
      cond(
        and(eq(typeExpr, lit("class")), isSet(prop("Name"))),
        concat(prop("Prefix"), prop("Name")),
        lit("")
      ),
    ],
  },
  derived: {
    iconKind: typeExpr,
    isDecorative: or(
      prop("Decorative"),
      and(not(isSet(prop("Title"))), not(isSet(prop("AriaLabel"))))
    ),
  },
  props: [
    pStr("Name", "icon name appended to Prefix in class mode"),
    pStr("Type", "svg, text, or class (default)"),
    pStr("BaseClass", "base icon font class in class mode"),
    pStr("Prefix", "icon font prefix in class mode"),
    pSize(),
    pClass(),
    pStr("Href", "svg use href in svg mode"),
    pStr("Title", "svg title / accessible name"),
    pStr("AriaLabel", "accessible name for non-decorative icons"),
    pBool("Decorative", "hides the icon from assistive technology"),
    pAttrs(),
    pChildren(),
  ],
  render: when(
    eq(derived("iconKind"), lit("svg")),
    [
      el(
        "svg",
        [attrClass(), ...ariaAttrs, attrRest()],
        [
          when(isSet(prop("Title")), [el("title", [], [text(prop("Title"))])]),
          when(isSet(prop("Href")), [el("use", [attrExpr("href", prop("Href"))], [])]),
          slot(),
        ]
      ),
    ],
    [
      when(
        eq(derived("iconKind"), lit("text")),
        [el("span", [attrClass(), ...ariaAttrs, attrRest()], [slot()])],
        [el("span", [attrClass(), ...ariaAttrs, attrRest()], [])]
      ),
    ]
  ),
};

export default brick({
  id: "ui.icon",
  dir: "icon",
  docs:
    "Icon renders a class-based span, svg use reference, or text fallback.\n" +
    "Decorative icons are hidden from assistive technology by default.",
  recipes: { icon: { file: "icon.variants.json", recipe: iconVariants } },
  parts: [iconPart],
});
