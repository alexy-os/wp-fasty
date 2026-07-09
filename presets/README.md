# Presets

Catalog for `theme-builder` — the Underscores-style “questions”, expanded.

| Path | Role |
|------|------|
| `themes/*.json` | Theme manifests (id, layout, style, template → blocks) |
| `layouts/` | Shell contracts (classic, magazine, landing, …) |
| `blocks/*.latte` | Reusable page sections (compose UI8Kit primitives) |
| `styles/*.css` | Color / token presets |

## Example manifest

See `themes/wp-fasty-ym.json`.

## Adding a theme preset

1. Copy `themes/wp-fasty-ym.json` → `themes/<id>.json`
2. Point `layout` / `style` / `templates` at existing or new presets
3. `bun run theme:build -- <id>`
