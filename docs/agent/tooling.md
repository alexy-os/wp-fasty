# Tooling & verification commands

All commands from repo root (`/workspace/wpfasty`). "CI" = runs in `.github/workflows/ci.yml`.

| Command | What it checks / does | CI |
|---|---|---|
| `bun run ui:primitives` | runs ui8kit/codegen (`packages/ui8kit-codegen`) → Latte primitives in `.workspaces/ui8kit-latte` | yes |
| `bun run ui:install` | copies primitives into `themes/latte/views/ui` | yes |
| `bun run codegen:verify` | codegen typecheck + generation (also produces `generated/ui/**/*.html.twig`) | yes |
| `bun run theme:build -- latte` | builds views from `presets/themes/latte.json` (skips empty templates) | yes |
| `bun run lint:design` | `tools/lint-design.ts`: token-only colors, 8/4 spacing, no half steps/arbitrary px, no raw `children` | yes |
| `bun run css:build` | Tailwind v4 (`.workspaces/css-build`) → `themes/latte/assets/css/app.css` | yes |
| `bun run icons` | copies used Lucide SVGs (`tools/icons.ts`) | – |
| `bun run verify` | ui:primitives + theme:build + lint:design + css:build + test | – |
| `composer test` / `bun run test` | PHPUnit (`tests/`, bootstrap stubs WP functions) | yes |
| `composer phpcs` / `bun run phpcs` | WPCS via `phpcs.xml` (all themes) | yes |
| `composer audit` | known-vulnerability check of PHP deps | yes |
| `bash tools/playground-build.sh` | builds `playground/*.zip` (themes + demo seeders + images) | – |
| `wp eval-file tools/perf/queries.php` | SAVEQUERIES: DB query count on archive must not grow with N posts | – (needs WP+DB) |
| Theme Check | see [wporg-theme-review.md](wporg-theme-review.md#running-theme-check-locally-verified) | – (needs WP) |

## Previews
`playground/{press,folio,shop}.json` blueprints; zips are published base64 in the secret gist
`alexy-os/ce9f531eeb05bb8aee9dadcd3b0c1ecc`; links in `playground/urls.txt`. See `playground/README.md`.

## Theme Check
Latest results are appended here by whoever runs it (date, WP version, counts per theme).
