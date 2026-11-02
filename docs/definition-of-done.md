# Definition of Done — ContextFactory refactor and audit findings

The key quality criteria for closing the ContextFactory refactor and every audit finding. An item is done when its
check runs in CI (or the stated script) and passes. Status is measured on **2026-11-02** (updated 21:30 MSK) on `main`; ✅ green,
❌ red / not in place yet, 🟡 partly in place.

## Status table (measured 2026-11-02, 21:30 MSK)

| Item | Check | Measured | Status |
|---|---|---|---|
| No WordPress in Core / ViewModel | `composer deptrac`, ArchitectureTest | 0 violations | ✅ |
| ContextFactory ~100 lines | `wc -l` | 101 (was 592) | ✅ |
| Contexts vs JSON Schema | ContextSchemaTest, `tools/qa/context-schema.php` | 28 live contexts, 0 invalid | ✅ |
| Post DTO vs @ui8kit/contract entry | PostContractSchemaTest | 6 fields × 4 posts valid; gap → #121 | 🟡 |
| HTML only as TrustedHtml | review | ports yes, context strings → #118 | 🟡 |
| DTO through Latte + Twig | PostRenderContractTest, `bun run parity` | same DOM | ✅ |
| PHPStan max, empty baseline | `composer phpstan` | 0 errors, 0 baseline, 2 targeted ignores | ✅ |
| PHPCS / CS-Fixer / PHPCompatibility | `composer cs` | 0 | ✅ |
| Infection MSI ≥ 80 % | `composer infection` | 97.9 % (229/234), gate 95 | ✅ |
| Rector dry-run clean | `composer rector` | 0 files | ✅ |
| PHPUnit | `composer test` | 459 tests, 7114 assertions | ✅ |
| HTML snapshots | CI 28 pages; local 60 pages | 0 differ | ✅ |
| N+1 guard | CI `tools/perf/queries.php` | flat | ✅ |
| TTFB ≤ 150 ms | `tools/perf/ttfb.sh` | 37–43 ms median | ✅ |
| Lighthouse ≥ 90 | `tools/perf/lighthouse.sh` | ≥ 91 every category | ✅ |
| `composer audit` | CI | 0 advisories | ✅ |
| .pot up to date | `tools/qa/pot-check.sh` | 6/6 | ✅ |
| WordPress ≥ 7.1.4 in CI | `tools/qa/wp-min-version.sh` | 7.1.4 | ✅ |
| Audit findings closed or filed | chaosbun #118–#121 | 4 filed | ✅ |

## 1. Architecture

- [x] ✅ No WordPress calls in Core or ViewModel — `composer deptrac` (WordPress layer from php-stubs; 0 violations) +
  `vendor/bin/phpunit --filter ArchitectureTest` (global WP function calls, which Deptrac cannot see).
- [x] ✅ `ContextFactory` is a thin facade, ~100 lines, delegation only — `wc -l themes/latte/classes/Data/ContextFactory.php`:
  **101** lines (was 592): 12 constructor dependencies wired in `configs/context.php`, filters behind
  `Adapter\WordPress\ContextFilters`, one `viewModels()` path; `text()` removed (use `PlainText::from()`).
- [x] ✅ Constructor DI, no `Application::getInstance()` or globals in new code — review + `rg "getInstance|global \$" themes/latte/classes/Data themes/latte/classes/Core/Port themes/latte/classes/Core/Model`
  (globals only in `Adapter\WordPress`, which reads `$wp_query` by design).
- [x] ✅ Views get DTO-derived arrays checked against a JSON Schema — `schema/theme-context.schema.json` (post fields
  named after the Codex v0.4 entry: id, slug, title, content, excerpt); `vendor/bin/phpunit --filter ContextSchemaTest`
  (builders, factory contexts, parity fixtures) and, in CI, every live context of the snapshot run
  (`php tools/qa/context-schema.php`: 28 contexts, 0 invalid). Against `@ui8kit/contract` itself:
  `tests/PostContractSchemaTest.php` validates the Codex-named Post DTO fields (`id` as `<kind>-<id>`, kind, slug,
  title, content, excerpt) with `entry.schema.json#/properties/*`. **Contract mapping gap:** the contract has
  schemas for Codex records only, none for a view context; `locale`, `status`, `visibility`, `version`,
  `created_at`, `updated_at` and the id references are not in the view-model — filed for XGenBot:
  [chaosbun#121](https://github.com/alexy-os/chaosbun/issues/121).
- [ ] 🟡 HTML only as `TrustedHtml` — the_content, wp_link_pages, widget areas and comments cross the ports as
  `TrustedHtml`; the context still carries them as strings (template contract) — `rg "TrustedHtml" themes/latte/classes`.
  Filed: [chaosbun#118](https://github.com/alexy-os/chaosbun/issues/118) (PHPIngineer; template contract change).
- [x] ✅ Contract test rendering the same DTO via Latte and a Twig adapter — `vendor/bin/phpunit --filter
  PostRenderContractTest`: one `Post` DTO → `PostViewModelBuilder` → `pages/single` in `layout/default`, same DOM
  through `LatteRenderer` and `TwigRenderer` (`tools/parity/compare-classes.php`), DTO values escaped alike;
  `bun run parity` covers the frozen contexts (green).

## 2. Code quality

- [x] ✅ PHPStan level max with an empty baseline — `composer phpstan`: level **max**, no baseline file, **0** errors;
  2 targeted `@phpstan-ignore argument.type` (`plugins/wpfasty-types/src/Plugin.php`: `register_post_type` /
  `register_taxonomy` args built from module arrays, typed by the stubs as exact shapes).
- [x] ✅ PHPCS (Symfony + WordPress.Security / I18n) and PHP-CS-Fixer clean — `composer cs` (CI step `composer phpcs`).
- [x] ✅ PHPCompatibilityWP 8.2–8.5 — part of `phpcs.xml.dist`.
- [x] ✅ Infection MSI ≥ 80 % on Core and the ViewModel builders — `composer infection` (infection.json5:
  `classes/Core` + `classes/Data` without the ContextFactory facade), CI job `mutation` (pcov): **234** mutants,
  229 killed, 5 escaped (equivalent / fast-path), MSI and covered MSI **97.9 %**; gate `minMsi` / `minCoveredMsi` 95.
- [x] ✅ Rector PHP 8.2 set, dry-run clean — `composer rector` (CI step in `verify`): PHP 8.2 set + dead code, code
  quality, early return; **0** pending files. Applied: readonly / promoted properties, `??=`, previous exception
  kept, redundant casts and private params. Skipped with reasons in `rector.php` (BC of the public theme / plugin API:
  readonly classes, first-class callables in hooks, unused public params, static → instance, `::class` on optional
  classes; house style: `null ===` over `instanceof` FQCN, `dirname(__DIR__)`, explicit `||`).

## 3. Behavior

- [x] ✅ HTML snapshots of home / post / archive / search / author / 404 identical before and after each commit — CI
  step "HTML snapshot parity" (`tools/qa/snapshots.sh`: latte, latte-press, latte-folio, latte-shop × 7 URLs = 28
  pages, `themes/latte` of this commit vs the previous one; `[html-change]` in the commit message declares an
  intended change). Locally also 60 pages incl. a multi-page post, attachment, project/product. Normalized:
  `antispambot()` mailto links and the order of posts with equal dates.
- [x] ✅ SQL query count does not grow with the post count — CI "N+1 guard" (`wp eval-file tools/perf/queries.php
  <type|pager|macros|richtext> 2 6`, `tools/perf/guard.php`).
- [x] ✅ All audit findings closed or moved to chaosbun issues with a reason — closed: folio contact `mailto:`
  double-encoding, missing meta description, PHPStan baseline, Infection, Rector, `latte.pot` check, DTO contract
  test, ContextFactory size. Filed: [#118](https://github.com/alexy-os/chaosbun/issues/118) TrustedHtml in the context
  (PHPIngineer), [#119](https://github.com/alexy-os/chaosbun/issues/119) latte-press «Pages» nav of the multi-page
  post 69 (PHPIngineer), [#120](https://github.com/alexy-os/chaosbun/issues/120) `php -S` segfault on PHP 8.2.35 with
  opcache, worked around in CI (PHPIngineer), [#121](https://github.com/alexy-os/chaosbun/issues/121) no view-context
  schema in `@ui8kit/contract` (XGenBot).

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
- [x] ✅ `latte.pot` up to date, checked in CI — `bash tools/qa/pot-check.sh` (CI step in `verify`): regenerates
  latte.pot, the child theme .pot files and wpfasty-builder.pot and compares strings (not `#:` line refs): 6/6 up to date.
- [x] ✅ CI WordPress ≥ 7.1.4 (CVE-2026-119977) — `tools/qa/wp-min-version.sh` fails an older core in both workflows;
  `Requires at least` of the themes unchanged; README → Security.
- [x] ✅ CHANGELOG lists breaking changes — `wpfasty_post_data` receives the `Post` DTO; `ContextFactory` needs its
  dependencies (no `new ContextFactory()`); exception renames keep `class_alias` (`EngineUnavailable`,
  `CanonicalError`, @deprecated); `ContextFactory::text()` removed (`PlainText::from()`).
