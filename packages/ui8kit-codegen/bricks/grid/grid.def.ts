import {
  attrClass,
  attrRest,
  brick,
  cond,
  contains,
  defaultIfEmpty,
  el,
  intMapOr,
  lit,
  mapOr,
  pAttrs,
  pChildren,
  pClass,
  pInt,
  prop,
  pStr,
  slot,
} from "../_dsl";
import gridVariants from "./grid.variants.json";

const GRID_COLS: Record<string, string> = {
  "1": "grid-cols-1",
  "2": "grid-cols-2",
  "3": "grid-cols-3",
  "4": "grid-cols-4",
  "5": "grid-cols-5",
  "6": "grid-cols-6",
  "7": "grid-cols-7",
  "8": "grid-cols-8",
  "9": "grid-cols-9",
  "10": "grid-cols-10",
  "11": "grid-cols-11",
  "12": "grid-cols-12",
  "1-2": "grid-cols-1 md:grid-cols-2",
  "1-3": "grid-cols-1 md:grid-cols-2 xl:grid-cols-3",
  "1-4": "grid-cols-1 md:grid-cols-2 xl:grid-cols-4",
};

const intMap = (prefix: string): Record<number, string> =>
  Object.fromEntries(Array.from({ length: 12 }, (_, i) => [i + 1, `${prefix}-${i + 1}`]));

export default brick({
  id: "ui.grid",
  dir: "grid",
  docs: "Grid lays out children in a CSS grid with optional column wrappers.",
  recipes: { grid: { file: "grid.variants.json", recipe: gridVariants } },
  parts: [
    {
      name: "Grid",
      docs: "Grid renders a display:grid container.",
      recipeId: "grid",
      asChild: true,
      classes: {
        recipe: {},
        extra: [
          cond(
            contains(prop("Class"), "grid-cols-"),
            lit(""),
            mapOr(defaultIfEmpty(prop("Cols"), "1"), GRID_COLS, "")
          ),
        ],
      },
      props: [
        pClass(),
        pStr("Cols", "column preset: 1–12 or responsive 1-2, 1-3, 1-4"),
        pAttrs(),
        pChildren(),
      ],
      render: el("div", [attrClass(), attrRest()], [slot()]),
    },
    {
      name: "GridCol",
      docs: "GridCol renders one grid column child.",
      asChild: true,
      classes: {
        extra: [
          intMapOr(prop("Span"), intMap("col-span"), ""),
          intMapOr(prop("Start"), intMap("col-start"), ""),
          intMapOr(prop("End"), intMap("col-end"), ""),
          intMapOr(prop("Order"), intMap("order"), ""),
        ],
      },
      props: [
        pClass(),
        pInt("Span", "col-span-1..12"),
        pInt("Start", "col-start-1..12"),
        pInt("End", "col-end-1..12"),
        pInt("Order", "order-1..12"),
        pAttrs(),
        pChildren(),
      ],
      render: el("div", [attrClass(), attrRest()], [slot()]),
    },
  ],
});
