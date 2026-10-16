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
| `wp plugin activate wpfasty-types` | companion plugin (post types/taxonomies/meta/metaboxes) needed by folio/shop | – |
| `bun run i18n:pot` | regenerates `themes/*/languages/*.pot` (needs wp-cli) | – |
| `composer audit` | known-vulnerability check of PHP deps | yes |
| `bash tools/playground-build.sh` | builds `playground/*.zip` (themes + demo seeders + images) | – |
| `wp eval-file tools/perf/queries.php` | SAVEQUERIES: DB query count on archive must not grow with N posts | – (needs WP+DB) |
| Theme Check | see [wporg-theme-review.md](wporg-theme-review.md#running-theme-check-locally-verified) | – (needs WP) |

## Previews
`playground/{press,folio,shop}.json` blueprints; zips are published base64 in the secret gist
`alexy-os/ce9f531eeb05bb8aee9dadcd3b0c1ecc`; links in `playground/urls.txt`. See `playground/README.md`.

## Theme Check
Latest results are appended here by whoever runs it (date, WP version, counts per theme).
Counting: `grep -c REQUIRED|WARNING|RECOMMENDED` on the CLI output.

| date (UTC+3) | WP | latte | latte-press | latte-folio | latte-shop |
|---|---|---|---|---|---|
| 2026-10-16 baseline (4895c4d) | 7.1.3 | R1 W4 Rec17 | R1 W5 Rec16 | R3 W5 Rec17 | R6 W5 Rec17 |
| 2026-10-16 after ADR-001 work | 7.1.3 | R0 W0 Rec10 | R0 W1 Rec9 | R0 W1 Rec10 | R0 W1 Rec10 |

Remaining WARNING in children: "more than one text-domain" (child domain + parent `latte` — allowed for a
framework/parent per the Required rules, still reported). Folio/shop pass only because post types moved to the
companion plugin `plugins/wpfasty-types` (ADR-001 §3a).
