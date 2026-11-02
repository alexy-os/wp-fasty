# Definition of Done — ContextFactory refactor and audit findings

The key quality criteria for closing the ContextFactory refactor and every audit finding. An item is done when its
check runs in CI (or the stated script) and passes. Status is measured on **2026-11-02** (updated 18:40 MSK) on `main`; ✅ green,
❌ red / not in place yet, 🟡 partly in place.

## 1. Architecture

- [x] ✅ No WordPress calls in Core or ViewModel — `composer deptrac` (WordPress layer from php-stubs; 0 violations) +
  `vendor/bin/phpunit --filter ArchitectureTest` (global WP function calls, which Deptrac cannot see).
- [ ] 🟡 `ContextFactory` is a thin facade, ~100 lines, delegation only — `wc -l themes/latte/classes/Data/ContextFactory.php`.
  Now **134** lines (was 592): promoted constructor without defaults (11 dependencies, wired in
  `configs/context.php`), no WordPress conditionals (`RequestStateProviderInterface`), shared keys in
  `CommonContextBuilder`; what is left is delegation, the `wpfasty_post_data` / `wpfasty_context` filters, the
  `WP_Post` entry points (`createPageContext`, `postData`, `prime`) and the deprecated `text()`.
- [x] ✅ Constructor DI, no `Application::getInstance()` or globals in new code — review + `rg "getInstance|global \$" themes/latte/classes/Data themes/latte/classes/Core/Port themes/latte/classes/Core/Model`
  (globals only in `Adapter\WordPress`, which reads `$wp_query` by design).
- [x] ✅ Views get DTO-derived arrays checked against a JSON Schema — `schema/theme-context.schema.json` (post fields
  named after the Codex v0.4 entry: id, slug, title, content, excerpt); `vendor/bin/phpunit --filter ContextSchemaTest`
  (builders, factory contexts, parity fixtures) and, in CI, every live context of the snapshot run
  (`php tools/qa/context-schema.php`: 28 contexts, 0 invalid). Not yet cross-validated against the
  `@ui8kit/contract` package schemas themselves (they describe Codex records, not the view context).
- [ ] 🟡 HTML only as `TrustedHtml` — the_content, wp_link_pages, widget areas and comments cross the ports as
  `TrustedHtml`; the context still carries them as strings (template contract) — `rg "TrustedHtml" themes/latte/classes`.
- [ ] 🟡 Contract test rendering the same DTO via Latte and a Twig adapter — `bun run parity` renders the same frozen
  contexts through Latte and Twig (green); a test that starts from a `Post` DTO is missing.

## 2. Code quality

- [ ] ❌ PHPStan level max with an empty baseline — `composer phpstan`. Now level **8**, baseline **76** entries, 0 new errors.
- [x] ✅ PHPCS (Symfony + WordPress.Security / I18n) and PHP-CS-Fixer clean — `composer cs` (CI step `composer phpcs`).
- [x] ✅ PHPCompatibilityWP 8.2–8.5 — part of `phpcs.xml.dist`.
- [ ] ❌ Infection MSI ≥ 80 % on Core and the ViewModel builders — Infection is not installed.
- [ ] ❌ Rector PHP 8.2 set, dry-run clean — Rector is not installed.

## 3. Behavior

- [x] ✅ HTML snapshots of home / post / archive / search / author / 404 identical before and after each commit — CI
  step "HTML snapshot parity" (`tools/qa/snapshots.sh`: latte, latte-press, latte-folio, latte-shop × 7 URLs = 28
  pages, `themes/latte` of this commit vs the previous one; `[html-change]` in the commit message declares an
  intended change). Locally also 60 pages incl. a multi-page post, attachment, project/product. Normalized:
  `antispambot()` mailto links and the order of posts with equal dates.
- [x] ✅ SQL query count does not grow with the post count — CI "N+1 guard" (`wp eval-file tools/perf/queries.php
  <type|pager|macros|richtext> 2 6`, `tools/perf/guard.php`).
- [ ] ❌ All audit findings closed or moved to chaosbun issues with a reason — closed: folio contact `mailto:`
  double-encoding, missing meta description; open: PHPStan baseline, the ❌ items here (not filed as issues yet).

## 4. Performance

Environment: box dev site (`dev.sh`: `wp server` = PHP 8.4.23 built-in server, opcache on, MariaDB 11.4 on the same
host, 8 vCPU), WordPress 7.1.4, theme `latte`, no page cache, no optimization plugins. Numbers are server-dependent.

- [x] ✅ TTFB ≈ 150 ms or less — `bash tools/perf/ttfb.sh http://127.0.0.1:8080 20` (median of 20 warm runs):
  home 41 ms, post 37 ms, category 43 ms, search 43 ms, author 38 ms, 404 37 ms (p90 ≤ 47 ms).
- [x] ✅ Lighthouse mobile + desktop, every category ≥ 90 out of the box — `bash tools/perf/lighthouse.sh`
  (lighthouse 12, headless Chrome), after the theme's meta description:

  | Page | Preset | Performance | Accessibility | Best practices | SEO |
  |---|---|---|---|---|---|
  | home | mobile | 99 | 100 | 100 | 92 |
  | home | desktop | 100 | 100 | 100 | 92 |
  | post | mobile | 99 | 100 | 100 | 92 |
  | post | desktop | 100 | 100 | 100 | 92 |
  | category | mobile | 99 | 100 | 100 | 91 |
  | category | desktop | 100 | 100 | 100 | 91 |

  The remaining SEO point is `robots-txt`, an artifact of the dev site's plain permalinks (WordPress serves
  `/robots.txt` only through rewrite rules; with `?p=` links it redirects to HTML). With `/%postname%/` permalinks
  `/robots.txt` is `200 text/plain` and the home page scores **SEO 100** (mobile and desktop). Before the meta
  description: SEO 82–85.

## 5. Security and release

- [x] ✅ `composer audit` clean — no advisories.
- [x] ✅ Escaping and nonces enforced by PHPCS — `WordPress.Security.*` sniffs are errors in `composer phpcs`.
- [ ] ❌ `latte.pot` up to date, checked in CI — regenerated with `bun run i18n:pot` (2026-11-02); no CI step compares it yet.
- [x] ✅ CHANGELOG lists breaking changes — `wpfasty_post_data` receives the `Post` DTO; `ContextFactory` needs its
  dependencies (no `new ContextFactory()`); exception renames keep `class_alias` (`EngineUnavailable`,
  `CanonicalError`, @deprecated); `ContextFactory::text()` deprecated.
