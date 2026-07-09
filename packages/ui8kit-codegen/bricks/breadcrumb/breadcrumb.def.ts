import {
  and,
  attrClass,
  attrExpr,
  attrRest,
  attrStatic,
  brick,
  cond,
  el,
  forEach,
  fwd,
  isSet,
  item,
  lit,
  not,
  pAttrs,
  pClass,
  pPass,
  text,
  when,
} from "../_dsl";

const itemClasses = {
  staticBase: "text-sm",
  extra: [
    cond(
      item("Current"),
      lit("font-medium"),
      cond(
        item("Disabled"),
        lit("text-muted-foreground"),
        lit("text-primary underline-offset-4 hover:underline")
      )
    ),
  ],
  includeClassProp: false,
};

const itemAttrs = [
  attrClass(itemClasses),
  attrExpr("aria-current", lit("page"), { when: item("Current") }),
];

export default brick({
  id: "ui.breadcrumb",
  dir: "breadcrumb",
  docs:
    "Breadcrumb shows the current page location within a hierarchy.\n" +
    "Renders nav with ordered links and aria-current on the active item.",
  recordTypes: [
    {
      name: "BreadcrumbItem",
      fields: [
        { name: "Label", type: "string" },
        { name: "Href", type: "string" },
        { name: "Current", type: "bool", docs: "marks the active page (aria-current=page)" },
        { name: "Disabled", type: "bool" },
      ],
    },
  ],
  parts: [
    {
      name: "Breadcrumb",
      docs: "Breadcrumb renders the full navigation trail.",
      classes: { staticBase: "text-sm" },
      props: [
        pClass(),
        { name: "Items", type: "items", cva: false, itemsOf: "BreadcrumbItem" },
        pPass("AriaLabel"),
        pPass("DataUI8Kit", "opt-in behavior hook; empty emits nothing"),
        pAttrs(),
      ],
      render: el(
        "nav",
        [attrClass(), fwd("aria-label", "AriaLabel"), fwd("data-ui8kit", "DataUI8Kit"), attrRest()],
        [
          el(
            "ol",
            [attrStatic("class", "flex flex-wrap items-center gap-2")],
            [
              forEach("Items", [
                el(
                  "li",
                  [attrStatic("class", "inline-flex items-center")],
                  [
                    when(
                      and(isSet(item("Href")), not(item("Disabled"))),
                      [
                        el(
                          "a",
                          [attrExpr("href", item("Href")), ...itemAttrs],
                          [text(item("Label"))]
                        ),
                      ],
                      [el("span", itemAttrs, [text(item("Label"))])]
                    ),
                  ]
                ),
              ]),
            ]
          ),
        ]
      ),
    },
  ],
});
