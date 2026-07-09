# Layout: classic

Top navigation shell — header + main + footer.

## Artifacts

| File | Role |
|------|------|
| `shell.latte` | Document shell (optional override of theme `views/layout/default.latte`) |
| Theme parts | `views/layout/parts/header.latte`, `footer.latte` |

## Features

- Skip link to `#content`
- Desktop primary nav
- Mobile sheet trigger + panel (twin IDs via `sheet-ids.php`)
- Enqueues parent CSS + `ui8kit.js`
