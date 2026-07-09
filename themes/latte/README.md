# Latte theme

Clean WordPress theme for the wp-fasty monorepo.

- **UI:** UI8Kit Codegen Latte primitives (`.workspaces/ui8kit-latte`)
- **PHP:** slim DI (`Application` → `Container` → services)
- **Data:** `ContextFactory` only — no WP queries in Latte
- **Views:** layout grammar + `{include 'button/Button.latte', …}`

## Activate

1. From monorepo root:

```bash
bun run ui:primitives
bun run ui:install
composer install --working-dir=themes/latte
```

Or with Docker:

```bash
docker compose --profile tools run --rm composer
```

2. In WP Admin → Appearance → Themes → activate **Latte**.

## Structure

```
themes/latte/
  classes/     Core, Data, Hooks, Template, Theme
  configs/     services.php
  views/       layout + pages + blocks
  assets/css/  theme.min.css + tokens.css
```
