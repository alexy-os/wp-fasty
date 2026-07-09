import {
  attrClass,
  attrExpr,
  attrRest,
  brick,
  el,
  intToString,
  isSet,
  oneOfOr,
  pAttrs,
  pChildren,
  pClass,
  pInt,
  pPass,
  prop,
  pStr,
  slot,
  type AttrSpec,
  type PartDef,
} from "../_dsl";
import tableVariants from "./table.variants.json";

function plainPart(name: string, docs: string, tag: string, extraAttrs: AttrSpec[] = []): PartDef {
  return {
    name,
    docs,
    classes: {},
    props: [pClass(), pAttrs(), pChildren()],
    render: el(tag, [attrClass(), ...extraAttrs, attrRest()], [slot()]),
  };
}

const spanAttr = attrExpr("span", intToString(prop("Span")), { when: isSet(prop("Span")) });

function cellProps() {
  return [
    pClass(),
    pStr("Scope", "th scope: col, row, colgroup, rowgroup"),
    pInt("ColSpan"),
    pInt("RowSpan"),
    pPass("Headers", "ids of related header cells"),
    pStr("Abbr", "abbreviated header text for th"),
    pAttrs(),
    pChildren(),
  ];
}

function cellAttrs(heading: boolean): AttrSpec[] {
  const attrs: AttrSpec[] = [attrClass()];
  if (heading) {
    attrs.push(
      attrExpr("scope", oneOfOr(prop("Scope"), ["col", "colgroup", "row", "rowgroup"], "", { lower: true })),
      attrExpr("abbr", prop("Abbr"))
    );
  }
  attrs.push(
    attrExpr("colspan", intToString(prop("ColSpan")), { when: isSet(prop("ColSpan")) }),
    attrExpr("rowspan", intToString(prop("RowSpan")), { when: isSet(prop("RowSpan")) }),
    fwdHeaders(),
    attrRest()
  );
  return attrs;
}

function fwdHeaders(): AttrSpec {
  return attrExpr("headers", prop("Headers"));
}

export default brick({
  id: "ui.table",
  dir: "table",
  docs:
    "Table composes semantic table, caption, sections, rows, and cells.\n" +
    "Supports scope, colspan, and headers attrs on head cells.",
  recipes: { table: { file: "table.variants.json", recipe: tableVariants } },
  parts: [
    {
      name: "Table",
      docs: "Table renders the root table element.",
      recipeId: "table",
      classes: { recipe: {} },
      props: [pClass(), pAttrs(), pChildren()],
      render: el("table", [attrClass(), attrRest()], [slot()]),
    },
    plainPart("TableCaption", "TableCaption renders the table caption.", "caption"),
    plainPart("TableHead", "TableHead renders the thead section.", "thead"),
    plainPart("TableBody", "TableBody renders the tbody section.", "tbody"),
    plainPart("TableFoot", "TableFoot renders the tfoot section.", "tfoot"),
    plainPart("TableRow", "TableRow renders one tr row.", "tr"),
    {
      name: "TableHeadCell",
      docs: "TableHeadCell renders th with optional scope and abbr.",
      classes: {},
      props: cellProps(),
      render: el("th", cellAttrs(true), [slot()]),
    },
    {
      name: "TableCell",
      docs: "TableCell renders td with colspan and rowspan.",
      classes: {},
      props: cellProps(),
      render: el("td", cellAttrs(false), [slot()]),
    },
    {
      name: "TableColGroup",
      docs: "TableColGroup renders colgroup with optional span.",
      classes: {},
      props: [pClass(), pInt("Span"), pAttrs(), pChildren()],
      render: el("colgroup", [attrClass(), spanAttr, attrRest()], [slot()]),
    },
    {
      name: "TableCol",
      docs: "TableCol renders a single col definition.",
      classes: {},
      props: [pClass(), pInt("Span"), pAttrs()],
      render: el("col", [attrClass(), spanAttr, attrRest()], [], { void: true }),
    },
  ],
});
