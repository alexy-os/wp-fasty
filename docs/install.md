# Install the WP FastY theme

## From monorepo (development)

```bash
bun run ui:primitives
bun run ui:install
composer install --working-dir=themes/wp-fasty
bun run theme:build -- wp-fasty
```

Activate **Latte** in WP Admin. Optional child skin: activate **Latte Skin** (no Composer on the child).

## From release zip

```bash
bun run theme:pack -- wp-fasty
# → dist/wp-fasty.zip
```

1. WP Admin → Appearance → Themes → Add New → Upload Theme
2. Upload `dist/wp-fasty.zip` and activate
3. Optional: upload / copy a child skin folder (no `vendor/`) and activate it

The zip includes:

- Theme PHP + views + assets
- Bundled `lib/ui8kit-latte` (`ui/` + `php/UI8Kit/`)
- Composer-installed `latte/latte` under `vendor/`

It does **not** include the TypeScript codegen engine.

## Dev vs release Composer

| Mode | Primitives source |
|------|-------------------|
| Monorepo | Path repo `../../.workspaces/ui8kit-latte` |
| Release zip | Path repo `lib/ui8kit-latte` (copied by `theme:pack`) |
