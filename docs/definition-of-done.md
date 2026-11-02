# Definition of Done — ContextFactory refactor and audit findings

The key quality criteria for closing the ContextFactory refactor and every audit finding. An item is done when its
check runs in CI (or the stated script) and passes. Status is measured on **2026-11-02** on `main`; ✅ green,
❌ red / not in place yet, 🟡 partly in place.

## 1. Architecture

- [x] ✅ No WordPress calls in Core or ViewModel — `composer deptrac` (WordPress layer from php-stubs; 0 violations) +
  `vendor/bin/phpunit --filter ArchitectureTest` (global WP function calls, which Deptrac cannot see).
- [ ] ❌ `ContextFactory` is a thin facade, ~100 lines, delegation only — `wc -l themes/latte/classes/Data/ContextFactory.php`.
  Now **220** lines: constructor defaults for 15 dependencies, `common()`, request reads (`is_search`, `is_front_page`,
  `wpfasty_shell`), the `wpfasty_post_data` / `wpfasty_context` filters.
- [x] ✅ Constructor DI, no `Application::getInstance()` or globals in new code — review + `rg "getInstance|global \$" themes/latte/classes/Data themes/latte/classes/Core/Port themes/latte/classes/Core/Model`
  (globals only in `Adapter\WordPress`, which reads `$wp_query` by design).
- [ ] ❌ Views get only DTO-derived arrays aligned with ui8kit/contract, checked by a JSON Schema test — no schema test
  for the theme context yet (`Post` uses Codex v0.4 names: id, kind, slug, title, content, excerpt).
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

- [x] ✅ HTML snapshots of home / post / archive / search / author / 404 identical before and after each refactor
  commit — local script (stash, render, compare) over 60 pages (latte, latte-press, latte-folio, latte-shop; plus a
  multi-page post, an attachment, project/product singles and archives, a paged category). Normalized for two
  non-deterministic parts: `antispambot()` mailto links and the order of posts with equal dates. Not in CI yet.
- [x] ✅ SQL query count does not grow with the post count — CI "N+1 guard" (`wp eval-file tools/perf/queries.php
  <type|pager|macros|richtext> 2 6`, `tools/perf/guard.php`).
- [ ] ❌ All audit findings closed or moved to chaosbun issues with a reason — open: SEO items below, folio contact
  `mailto:` links are double-encoded (`&amp;#100;…`), PHPStan baseline, items marked ❌ here.

## 4. Performance

Environment: box dev site (`dev.sh`: `wp server` = PHP 8.4.23 built-in server, opcache on, MariaDB 11.4 on the same
host, 8 vCPU), WordPress 7.1.4, theme `latte`, no page cache, no optimization plugins. Numbers are server-dependent.

- [x] ✅ TTFB ≈ 150 ms or less — `bash tools/perf/ttfb.sh http://127.0.0.1:8080 20` (median of 20 warm runs):
  home 36 ms, post 33 ms, category 36 ms, search 37 ms, author 33 ms, 404 31 ms (p90 ≤ 41 ms).
- [ ] 🟡 Lighthouse mobile + desktop, every category ≥ 90 out of the box — `bash tools/perf/lighthouse.sh`
  (lighthouse 12, headless Chrome):

  | Page | Preset | Performance | Accessibility | Best practices | SEO |
  |---|---|---|---|---|---|
  | home | mobile | 99 | 100 | 100 | **85** |
  | home | desktop | 100 | 100 | 100 | **85** |
  | post | mobile | 99 | 100 | 100 | **83** |
  | post | desktop | 100 | 100 | 100 | **83** |
  | category | mobile | 99 | 100 | 100 | **82** |
  | category | desktop | 100 | 100 | 100 | **82** |

  SEO fails on `meta-description` (the theme prints none) and `robots-txt` (the dev server answers `/robots.txt`
  with a 301 to HTML; check again behind a real web server).

## 5. Security and release

- [x] ✅ `composer audit` clean — no advisories.
- [x] ✅ Escaping and nonces enforced by PHPCS — `WordPress.Security.*` sniffs are errors in `composer phpcs`.
- [ ] ❌ `latte.pot` up to date, checked in CI — regenerated with `bun run i18n:pot` (2026-11-02); no CI step compares it yet.
- [x] ✅ CHANGELOG lists breaking changes — `wpfasty_post_data` receives the `Post` DTO; exception renames keep
  `class_alias` (`EngineUnavailable`, `CanonicalError`, @deprecated); `ContextFactory::text()` deprecated.
