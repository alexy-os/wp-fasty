# Layout: app-shell

Sidebar + header + mobile sheet — see `.project/constructor/shell-composition.md`.

## Artifacts

| File | Role |
|------|------|
| `shell.latte` | Document shell with sidebar column |
| `parts/sidebar.latte` | Desktop aside nav |
| `parts/header.latte` | Top bar with sheet trigger |
| `parts/mobile-sheet.latte` | Mobile nav sheet (SSR-valid without JS) |
| `sheet-ids.php` | Twin ID constants |

## Enable

Set `"layout": "layouts/app-shell"` in a theme or skin preset, then `bun run theme:build`.
Demo skin: `themes/latte-skin` can opt in via filter / future builder copy.
