# QA baseline — 2026-10-19

**Scope:** measurement only (no fixes). Commit measured: `aafc543` (branch `improved`).  
**Run at:** 2026-10-19 ~11:37–11:45 (UTC+3) on the agent box.  
**Environment:** PHP 8.4.26 CLI, PHPUnit 10.5.66, WP-CLI 2.12.0, WordPress 7.1.3, Theme Check plugin 20260901, MariaDB (local, throwaway DB).

Box setup needed for this run (was missing): `php8.4-xml` (PHPUnit needs `dom`/`xmlwriter`), `php8.4-mysql`, `mariadb-server`, WP-CLI phar. The earlier site `/workspace/sites/std` had no running DB server any more, so a throwaway site `/workspace/sites/qa` was created (themes/plugins copied in, not symlinked).

## Summary

| Tool | Command | Result |
|------|---------|--------|
| PHPUnit | `bun run test` | **OK — 60 tests, 193 assertions, 0 failures** |
| PHPCS | `bun run phpcs` (`phpcs.xml`) | **0 errors, 49 warnings** in 20 of 71 files; exit 1 (warnings) |
| Design lint | `bun run lint:design` | **OK** (0 violations) |
| Pack | `bun run theme:pack -- latte` | **OK** → `dist/latte.zip` (516 files, ~1.1 MB) |
| Theme Check | `wp theme-check run <slug>` | latte **R0** · press **R0** · folio **R0** · shop **R0** · skin **R1** |

## PHPUnit

`OK (60 tests, 193 assertions)` in 0.37 s. No failing or skipped tests.

## PHPCS

Ruleset: PSR2 + selected Squiz/Slevomat rules + `WordPress.WP.I18n`, `WordPress.Security.EscapeOutput`, `WordPress.Security.NonceVerification`, `WordPress.WP.EnqueuedResources`. Not full WPCS.

| Package | Files scanned | Errors | Warnings |
|---------|---------------|--------|----------|
| `themes/latte` | 38 | 0 | 13 |
| `themes/latte-press` | 1 | 0 | 0 |
| `themes/latte-folio` | 3 | 0 | 8 |
| `themes/latte-shop` | 9 | 0 | 22 |
| `plugins/wpfasty-types` | 2 | 0 | 6 |
| `plugins/wpfasty-builder` | 18 | 0 | 0 |
| **Total** | **71** | **0** | **49** |

| Sniff | Count |
|-------|-------|
| `Generic.Files.LineLength.TooLong` (>120 chars) | 46 |
| `PSR1.Files.SideEffects.FoundWithSymbols` | 3 (`themes/latte/functions.php`, `themes/latte-shop/inc/catalog.php`, `plugins/wpfasty-types/src/Plugin.php`) |

Worst files: `latte-shop/inc/orders.php` (8), `latte-folio/inc/projects.php` (6), `latte-shop/inc/front.php` (5), `wpfasty-types/src/Plugin.php` (5).  
CI (`.github/workflows/ci.yml`) still runs PHPCS with `continue-on-error: true`; `phpcs` exits 1 on warnings, so dropping it needs 0 warnings (or `--warning-severity=0`).

## Design lint

`design lint: OK` — no half-steps, arbitrary px or non-token colors found.

## Theme Check

Parent checked from the **unpacked `dist/latte.zip`** (not the repo folder). Child themes copied from `themes/` without `~cache/`. `wpfasty-types` active for all runs (latte also checked without it: same result). Theme Check includes the parent's files when it scans a child, so parent items show up in every child.

| Theme | REQUIRED | WARNING | RECOMMENDED | INFO |
|-------|----------|---------|-------------|------|
| `latte` (from zip) | 0 | 0 | 10 | 2 |
| `latte-press` | 0 | 1 | 9 | 0 |
| `latte-folio` | 0 | 1 | 10 | 1 |
| `latte-shop` | 0 | 1 | 10 | 1 |
| `latte-skin` | **1** | 1 | 10 | 2 |

Matches the earlier recorded result (`docs/agent/tooling.md`: latte R0 W0 Rec10; children R0 W1). `latte-skin` was not checked before.

**REQUIRED**
- `latte-skin`: text domain `latte` does not match slug `latte-skin`.

**WARNING**
- press / folio / shop: more than one text domain (`<child>` + `latte`).
- `latte-skin`: no copyright notice (it has no `readme.txt`).

**RECOMMENDED** (same set from the parent; press doesn't get custom-background)
1. No `register_block_pattern`
2. No `register_block_style`
3. No comment pagination (`paginate_comments_links` / `the_comments_navigation`, …)
4. No `comments_template`
5. Variable `$text` in a translation call — `classes/Template/TwigRenderer.php:85-86` (Twig `t()` helper)
6. No core post pagination (`the_posts_pagination` / `paginate_links`, …); the theme paginates with its own code
7. No `the_post_thumbnail()` (the theme builds images/srcset in `ContextFactory`)
8. No `add_theme_support('custom-header')`
9. No `add_theme_support('custom-background')` (not in press)
10. No `add_theme_support('wp-block-styles')`

**INFO**
- `comment-reply` is referenced (in `ContextFactory`) but the static check can't see it enqueued.
- latte / skin: only one text domain (`latte`).

## Not measured / caveats

- Theme Unit Test import and keyboard sweep: not run (manual).
- Docker smoke A7/B9: not run (no Docker on the box).
- Theme Check is a static scan; it can't follow Latte templates, so items 3, 4, 6, 7 should be checked against the real output before deciding on a fix.
