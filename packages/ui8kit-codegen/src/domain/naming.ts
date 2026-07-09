/**
 * Naming conventions per runtime family — decided once, applied to every
 * brick (brainstorm §5.1). Canonical prop names are Go PascalCase.
 *
 * - Templ:  PascalCase struct fields (`Variant`, `Class`, `AriaLabel`, `HTMLFor`).
 * - React:  camelCase DOM-ish (`variant`, `className`, `htmlFor`); literal
 *           `aria-*` keys stay hyphenated.
 * - Svelte/Vue: HTML-native attribute names (`class`, `for`, `aria-label`) —
 *           closer to Go than to React, per the documented 3rd convention.
 */

import type { PropDef } from "./model";

const REACT_SPECIAL: Record<string, string> = {
  Class: "className",
  HTMLFor: "htmlFor",
  For: "htmlFor",
  ID: "id",
  TabIndex: "tabIndex",
  NoValidate: "noValidate",
  ColSpan: "colSpan",
  RowSpan: "rowSpan",
  SrcSet: "srcSet",
  FetchPriority: "fetchPriority",
  Autocomplete: "autoComplete",
  Enctype: "encType",
  DataUI8Kit: "dataUi8kit",
  AsChild: "asChild",
};

const HTML_SPECIAL: Record<string, string> = {
  Class: "class",
  HTMLFor: "for",
  For: "for",
  ID: "id",
  TabIndex: "tabindex",
  NoValidate: "novalidate",
  ColSpan: "colspan",
  RowSpan: "rowspan",
  SrcSet: "srcset",
  FetchPriority: "fetchpriority",
  Autocomplete: "autocomplete",
  Enctype: "enctype",
  DataUI8Kit: "data-ui8kit",
};

function hyphenateAria(name: string): string | undefined {
  if (name.startsWith("Aria")) {
    return "aria-" + name.slice(4).toLowerCase();
  }
  return undefined;
}

export function camelCase(name: string): string {
  return name.charAt(0).toLowerCase() + name.slice(1);
}

/** React prop name for a canonical prop. */
export function reactPropName(p: PropDef): string {
  const special = REACT_SPECIAL[p.name];
  if (special) return special;
  const aria = hyphenateAria(p.name);
  if (aria) return aria;
  return camelCase(p.name);
}

/** Svelte/Vue prop name (HTML-native attribute casing). */
export function htmlPropName(p: PropDef): string {
  const special = HTML_SPECIAL[p.name];
  if (special) return special;
  const aria = hyphenateAria(p.name);
  if (aria) return aria;
  return camelCase(p.name);
}

/**
 * Latte/Twig prop name — PHP-compatible identifiers (no hyphens), HTML-native
 * casing where unambiguous (`class`, `for`, `id`, `tabindex`), camelCase for
 * ARIA (`ariaLabel`) since `aria-label` is not a valid PHP/Twig variable.
 * Mirrored at runtime by `UI8Kit\Rt::params()` in `runtime/php/ui8kit.php`.
 */
export function phpPropName(p: PropDef): string {
  if (p.name.startsWith("Aria")) return camelCase(p.name); // ariaLabel
  if (p.name === "DataUI8Kit") return "dataUi8kit";
  return htmlPropName(p);
}

/** Latte/Twig loop-item field key (mirrors `Rt::params` item conversion). */
export function phpItemKey(field: string): string {
  return camelCase(field);
}

/**
 * Safe local identifier for a prop inside generated TS/Svelte/Vue code
 * (hyphenated and reserved names need a destructure rename).
 */
export function localIdent(name: string): string {
  const aria = hyphenateAria(name);
  if (aria) return camelCase(name); // AriaLabel -> ariaLabel
  const special: Record<string, string> = {
    Class: "className",
    HTMLFor: "htmlFor",
    For: "htmlFor",
    DataUI8Kit: "dataUi8kit",
  };
  if (special[name]) return special[name];
  return camelCase(name);
}

const RESERVED_JS = new Set(["for", "class", "var", "new", "return", "default", "switch", "case"]);

export function needsRename(runtimeName: string, local: string): boolean {
  return runtimeName !== local || RESERVED_JS.has(runtimeName) || runtimeName.includes("-");
}
