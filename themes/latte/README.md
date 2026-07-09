# Latte theme

Clean WordPress parent theme for the wp-fasty monorepo.

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
  assets/js/   ui8kit.js (sheet / dialog behavior)
  languages/   latte.pot
```

## Child skins (no Composer)

A child theme can restyle and override Latte views **without** `composer.json` or `vendor/`.

| Child may | Child must not |
|-----------|----------------|
| Override `views/**` | Boot `Application` |
| Ship `assets/css/tokens.css` | Ship `vendor/` / `classes/` |
| Filter `wpfasty_context` | Hand-edit UI8Kit primitives |
| Filter `wpfasty_latte_roots` | Run `composer install` |

Loader order: **child views → parent views → UI8Kit `ui/`**.

Example skin: [`themes/latte-skin/`](../latte-skin/). Full contract: [`.project/constructor/child-skins.md`](../../.project/constructor/child-skins.md).

## Install from zip (release)

```bash
bun run theme:pack -- latte
```

Upload `dist/latte.zip` in WP Admin → Appearance → Themes → Add New. The zip includes vendored Latte + UI8Kit PHP/ui (no codegen TypeScript).

## Commands

```bash
bun run theme:build -- latte   # sync views from presets
bun run theme:pack -- latte    # release zip
bun run test                   # PHPUnit
bun run phpcs                  # coding standards
```
