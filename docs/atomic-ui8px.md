# Atomic UI8px discipline

From Templ `templ-atomic-ui8px` + Codegen recipe model.

## Styling model (three channels)

| Kind | Mechanism | Source of truth |
|------|-----------|-----------------|
| Visual presets (`Variant`, `Size`, …) | CVA recipe | `*.variants.json` only |
| Layout / spacing / responsive | Raw Tailwind utilities | Caller `Class` / `$class` prop only |
| Design tokens | CSS variables | Theme / `presets/styles/*.css` |

## MUST

- Pass visual appearance through recipe keys (`variant`, `size`, …)
- Put layout utilities on `class` / `Class` (e.g. `gap-4`, `md:flex`, `w-64`)
- Keep tokens in CSS (`--primary`, …), not hard-coded hex in bricks
- Regenerate Latte after brick/recipe changes (`ui:primitives`)
- Tailwind `@source` generated `ui/**/*.latte` and theme/preset views

## MUST NOT

- Expose semantic layout props as API (`gap=`, `flex=`, `items=` as component props)
- Duplicate variant maps in PHP/Latte by hand
- Use empty-string variant keys or model defaults as `""`
- Provide an `unstyled` escape hatch — add a named variant instead
- Ship a second “semantic CSS extraction” pipeline (`data-slot` → `.button-primary`)
- Deep-import internals when a facade/include path exists

## Class merge order (all runtimes)

1. Recipe base  
2. Variant / size classes  
3. Static base  
4. Computed extra fragments  
5. State classes  
6. Caller `class` (wins)

PHP uses `Rt::cn()` (concatenation). Parity compares effective utility sets after conflict resolution on the TS/canonical side.

## `.ui8px` policy (Templ era → wp-fasty)

Older Templ enforced Tailwind allow-lists via `.ui8px/policy/` + `lint:ui8px`.

For wp-fasty / Codegen:

- Prefer the same **spirit**: no random utility soup outside recipes + intentional `class`
- Theme-builder should eventually lint preset Latte against a policy (port or reuse)
- Until then: compose via primitives; put layout utilities only on `class`

## Tokens

- Demo/reference: shadcn-compatible variables (see Codegen `examples/web/static/css/tokens.css`, wp-fasty `presets/styles/default.css`)
- Themes pick a style preset; they do not redefine Button recipes
