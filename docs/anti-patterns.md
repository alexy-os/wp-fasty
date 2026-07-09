# Anti-patterns — retired and forbidden

If a PR reintroduces any of these, reject it.

## Presentation pipelines (dead)

| Anti-pattern | Why it died |
|--------------|-------------|
| React / Babel → Latte transpile | Broke parity; two sources of truth |
| `data-slot` → semantic CSS extractor | Second styling system beside Tailwind recipes |
| Parallel kits (`@semantic`, `n3shadcn`, …) as second codegen | Forked the design system |
| Hand-written `button button-primary` CSS without UI8Kit | Drift from brick contract |
| Shipping TypeScript codegen inside theme zip | Themes consume Latte bundle only |

## Layout & markup

| Anti-pattern | Do instead |
|--------------|------------|
| Raw `<div class="flex…">` in catalog blocks | `Stack` / `Group` / `Box` / `Block` |
| Multiple landmark Blocks in one file | One Block; nest Box/Stack/Group |
| Nested Block wrapping a child that already has Block | Parent uses Box/Group |
| Duplicate site header in every page view | One layout shell + content slots |
| React `asChild` in Latte | `Classes::buttonClasses` on `<a>` |

## Data & WordPress

| Anti-pattern | Do instead |
|--------------|------------|
| `get_the_title()` / loops inside Latte | `ContextFactory` → typed context |
| Dumping all post meta into context | Explicit schema keys |
| Product copy hard-coded in generated `ui/` | Fixtures / context / preset data |
| Logging template inventories every request | Dev-only tooling |

## ARIA & behavior

| Anti-pattern | Do instead |
|--------------|------------|
| Custom widget JS inside bricks | `@ui8kit/aria` + `data-ui8kit-*` |
| Controlled React-style `open` with `behavior="ui8kit"` | `open` = SSR initial only |
| Requiring JS for valid HTML | Static semantics first; hooks opt-in |
| Mismatched sheet trigger/panel IDs | Twin constants file |

## Variants & CSS

| Anti-pattern | Do instead |
|--------------|------------|
| Twin variant maps in PHP/Latte | Regenerate `Classes.php` |
| Empty-string variant keys / default `""` | Named keys only |
| Semantic layout props (`gap=`, `flex=`) on brick API | Caller `class` |
| `unstyled` escape hatch | Named variant |
| Hard-coded hex in bricks | CSS tokens in style presets |

## Repo hygiene

| Anti-pattern | Do instead |
|--------------|------------|
| Hand-edit `.workspaces/ui8kit-latte` | Change codegen bricks → `ui:primitives` |
| Treat `themes/wp-fasty-ym` views as the design system | Rebuild from presets + grammar |
| Copy Templ `examples/` as production theme | Extract **recipes** into `presets/` |
| Docker rebuild for every Latte edit | Bind-mount + clear `~cache` |

## What we keep from the YM experiment

- DI `Container`, bootable services
- `ContextFactory` / typed context idea
- `LatteEngine` + `WordPressExtension`
- `MultiRootLoader` (views + primitives)

Everything else in that theme’s views is disposable.
