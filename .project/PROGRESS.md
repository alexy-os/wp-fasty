# WP FastY — Full Progress & Continuation Plan

**Purpose:** Single handoff document for agents (including Cursor browser).  
Read this first, then execute the next open cycle. Do not invent a parallel roadmap.

**Last updated:** 2026-10-28 (macros 2 and 4: record tokens, price and stock; playground zips out of git)
**Active theme:** `themes/latte` · demo skin: `themes/latte-skin` · child themes: `latte-press`, `latte-folio`, `latte-shop`  
**Plugins:** `plugins/wpfasty-types` (data registration) · `plugins/wpfasty-builder` (landing builder)  
**Repo:** `alexy-os/wp-fasty` · monorepo with UI8Kit Codegen Latte primitives  
**Monorepo version:** `0.3.0` (`package.json`)

---

## How to use this file (agent protocol)

1. Read **Current state** and **Open cycle**.
2. Execute only the tasks in that cycle (or the next open follow-up).
3. After finishing work: mark checkboxes, bump **Last updated**.
4. Prefer small PRs / commits per cycle.
5. Discipline SSOT: `docs/` · agent rules: `.cursor/rules/` · constructor KB: `.project/constructor/`.

### Session start checklist

```
[x] bun / PHP available (Composer via tools/composer.sh or composer.phar)
[x] themes/latte + themes/latte-skin (child has no composer/vendor)
[x] .workspaces/ui8kit-latte exists
[x] PHPUnit 26/26 green (Cycle H)
[x] theme:pack → dist/latte.zip works
[x] readme.txt + LICENSE present (WP.org packaging)
[x] plugins/wpfasty-types + plugins/wpfasty-builder present (Cycles I–J)
[ ] PHPUnit re-run after Cycles I–J (tests/ has 53 test methods; not re-run in the 2026-10-19 doc update)
```

---

## Verification pass (2026-07-09)

Audited claimed Cycles A–G against the tree and ran tooling.

### Matches the plan (implemented)

| Cycle | Evidence |
|-------|----------|
| **A** | `ThemeService::resolveView()`, `404.php`, `search.php`, `createErrorContext`, cache `0755`, `PrimitivesLocator` |
| **B** | Loader roots child→parent→primitives, `wpfasty_latte_roots`, `themes/latte-skin` (no Composer), `Template: latte` |
| **C** | `theme:pack` bundles `lib/ui8kit-latte`, release composer path, `docs/install.md`, `dist/latte.zip` |
| **D** | Builder syncs `presets/blocks` → theme, emits page views, scaffolds skins |
| **E** | `languages/latte.pot`, skip/mobile nav, PHPUnit suite, PHPCS scripts, hero URLs via context |
| **F** | `presets/layouts/app-shell`, app-shell Latte parts, `ui8kit.js`, skin sets `wpfasty_shell` |
| **G** | `theme.json`, custom-logo / embeds, comments off, srcset, CI workflow, screenshots |
| **H** | WP.org completeness: entities, chrome, org hooks, starter content, packaging, tests |

### Gaps found & fixed in this pass

| Issue | Fix |
|-------|-----|
| PHPUnit Latte smoke: `UI8Kit\Rt` not found (Windows junction) | `tests/bootstrap.php` fallback require; theme `composer.json` `symlink: false` |
| `theme:pack` failed without system `composer` | Prefer `php composer.phar`; `tools/composer.sh` |
| `theme:pack` zip/tar failed on Windows (`tar` D: bug) | PowerShell `Compress-Archive` + store-zip fallback |
| Root `composer test` / `phpcs` assumed global Composer | `package.json` → `tools/composer.sh` |
| theme-builder still at `0.1.0` | Bumped to `0.3.0` |

### Still deferred (local only)

| Item | Notes |
|------|-------|
| A7 / B9 Docker smoke | Needs `bun run dev:up` on machine with Docker |
| Theme Check on `dist/latte.zip` | Manual gate before .org submit |
| Theme Unit Test import | Manual keyboard + template sweep |
| PHPCS clean | CI has `continue-on-error: true` — debt remains |
| Packagist publish of `ui8kit/latte-primitives` | Zip uses bundled `lib/` instead |

---

## Current state (snapshot)

### Done

| Area | Status |
|------|--------|
| Codegen Latte bundle | Done |
| Docs + `.cursor/rules` + `.project` KB | Done |
| Parent `themes/latte` | Done (v1.1.0) |
| Cycles A–H (code) | Done |
| PHPUnit 26 tests | Done (green) |
| `theme:pack` → `dist/latte.zip` | Done |
| WP.org packaging (`readme.txt`, `LICENSE`) | Done |
| Package scripts / composer helper | Done |
| Cycle I — child themes, data, perf (2026-10-16) | Done (see Cycle I) |
| Cycle J — `wpfasty-builder` plugin (2026-10-17) | Done (see Cycle J) |
| Theme Check 0 REQUIRED (all four themes, theme folders) | Recorded in `docs/agent/tooling.md` (local WP 7.1.3); re-verify on `dist/latte.zip` |
| WordPress Playground previews | Done (`playground/*.json`, `playground/urls.txt`) |

### Architecture (locked)

```
packages/ui8kit-codegen/     SOURCE
        ↓  bun run ui:primitives
.workspaces/ui8kit-latte/    Layer 1
presets/                     Layer 2
themes/latte/                Layer 3 parent (Composer)
themes/latte-skin/           Layer 3b child (no Composer)
themes/latte-press|folio|shop/  Layer 3b child themes (blog / portfolio / shop)
plugins/wpfasty-types/       companion plugin: CPTs, taxonomies, typed meta, metaboxes
plugins/wpfasty-builder/     landing builder: sources, layouts, Latte compiler, REST, Svelte editor
.workspaces/theme-builder/   build + pack
.workspaces/css-build/       token-only Tailwind v4 build (css:build)
playground/                  Playground blueprints + urls.txt (zips built in CI, gitignored)
dist/latte.zip               release artifact (gitignored)
```

### Hard rules (never regress)

- Never hand-edit `.workspaces/ui8kit-latte`
- No WP queries inside Latte — only `ContextFactory`
- No React→Latte / `data-slot` semantic CSS pipelines
- Variants only in `*.variants.json`
- Layout grammar: one Block per block file
- Child skins: no `composer.json` / `vendor/` / Application boot
- No CPT/shortcode/plugin-like features for .org
- No plugin-like features in theme — CPTs, taxonomies, meta and metaboxes live in `plugins/wpfasty-types`
- Comments off by default (privacy); opt-in only via `wpfasty_comments_enabled`
- Design tokens only: `bun run lint:design` must pass (8px grid, no half-steps, no arbitrary px, token colors)
- Post lists must stay N+1-free (`tools/perf/queries.php` guard)
- Never write gist ids, tokens or other secrets into `.project/` docs

---

## Open cycle

**→ Cycle K — Configurator** (see [Cycle K](#cycle-k--configurator-2026-10-19)); K6 done (Latte and Twig equal: views, builder, wordpress.org builds; parity + CI engine matrix); next: K7 (publish the form / MCP package). Follow-ups below stay open.

| # | Task | Done |
|---|------|------|
| 1 | Theme Check on unpacked `dist/latte.zip` — manual re-verify of 0 REQUIRED (recorded results are for theme folders) | [x] 2026-10-19 (0 REQUIRED; now enforced in CI job `wordpress`) |
| 2 | Import Theme Unit Test data; keyboard sweep (skip, menus, sheet, search) | [ ] |
| 3 | Local Docker smoke (A7/B9): activate `latte` then `latte-skin` | [ ] deferred local |
| 4 | Tighten PHPCS until CI can drop `continue-on-error` (`.github/workflows/ci.yml`) | [x] 2026-10-19 (0 errors, 0 warnings; CI strict) |
| 5 | Optional: publish `ui8kit/latte-primitives` to Packagist | [ ] |
| 6 | Adopt `docs/agent/ci.proposed.yml` as real CI (lint:design, css:build, codegen verify, audit, Theme Check, N+1 guard) | [x] 2026-10-19 (`.github/workflows/ci.yml`, jobs `verify` + `wordpress`) |
| 7 | Port saas-design rules (below) into `docs/` + `lint:design` | [ ] |
| 8 | `wpfasty-builder` in CI: svelte-check + Vite IIFE build with committed-asset check, PHPUnit (compiler, `Links`), PHPCS, Seeder landings on the CI site (HTTP 200, no PHP notices), layouts validated per site with `@ui8kit/contract` | [x] 2026-10-19 |
| 9 | N+1 guard on realistic data: `tools/perf/seed.php` (thumbnails, terms, typed meta, 3 authors) for post / project / product, 2 vs 6 items, also measured without widgets; numbers in the CI run summary | [x] 2026-10-19 (no N+1 found) |
| 10 | CI fails on any PHP notice / warning / deprecation logged by the WordPress job (`WP_DEBUG_LOG`) | [x] 2026-10-19 |

**saas-design rules to port (item 7):**

- [ ] Nested radius = outer radius − parent padding
- [ ] `rounded-sm` + `text-sm` for tables and alerts
- [ ] Inner padding inside bordered containers; compact padding on tablet/mobile
- [ ] No stray comments / blank lines in output HTML

---

## Full path — cycles & stages

```
A Parent correctness ✓
    → B Child skin (no Composer) ✓
        → C Packaging & install ✓
            → D Builder assembles views ✓
                → E Quality (i18n, a11y, tests) ✓
                    → F Shell v2 (sidebar + sheet + ARIA) ✓
                        → G Product polish ✓
                            → H WordPress.org completeness ✓
                                → I Child themes, data & perf ✓
                                    → J Builder plugin (wpfasty-builder) ✓
                                        → K Configurator (manifest ✓ → generator → zips → form/CLI/MCP)
                                        → Follow-ups (Theme Check zip / Unit Test / Docker / PHPCS / CI / saas-design)
```

---

### Cycle A — Parent correctness (P0)

| Stage | Task | Done |
|-------|------|------|
| A1 | `ThemeService::resolveView()` | [x] |
| A2 | Wire templates through shared render | [x] |
| A3 | `404.php` + view + `createErrorContext` | [x] |
| A4 | Search handling | [x] |
| A5 | Admin notice if primitives missing | [x] |
| A6 | Latte cache `0755` | [x] |
| A7 | Smoke in Docker | [ ] deferred local |

---

### Cycle B — Child theme as skin

| Stage | Task | Done |
|-------|------|------|
| B1–B8, B10 | Skin contract + roots + scaffold | [x] |
| B9 | Docker mount/activate | [ ] deferred (compose volume present) |

---

### Cycle C — Packaging & install

| Stage | Task | Done |
|-------|------|------|
| C1–C5 | Bundle lib + pack + docs + dev path-repo | [x] |
| C✓ | `dist/latte.zip` produced on Windows | [x] 2026-07-09 |

---

### Cycle D — Builder assembles views

| Stage | Task | Done |
|-------|------|------|
| D1–D6 | Sync blocks, emit pages, skin scaffold | [x] |

---

### Cycle E — Quality bar

| Stage | Task | Done |
|-------|------|------|
| E1–E6 | i18n, a11y, PHPUnit, PHPCS scripts, hero URLs | [x] |
| E✓ | PHPUnit green after autoload fix | [x] 2026-07-09 |

---

### Cycle F — Shell v2

| Stage | Task | Done |
|-------|------|------|
| F1–F6 | app-shell + sheet + ui8kit.js + skin filter | [x] |

---

### Cycle G — Product polish

| Stage | Task | Done |
|-------|------|------|
| G1–G6 | theme.json, logo, comments, srcset, CI | [x] |

---

### Cycle H — WordPress.org Theme Completeness

**Target:** WordPress.org Theme Directory (classic review).  
**Exclusions (intentional):** WooCommerce, FSE/block templates, comments/contact forms (PII), RTL.

| Stage | Task | Done |
|-------|------|------|
| H0 | Sheet IDs in context; `BootableServiceInterface`; shared mobile sheet; app-shell `$labels` | [x] |
| H1 | `resolveView` matrix; home/tax/author/attachment stubs; rich context + attachment view | [x] |
| H2 | Logo/brand, search form, footer menu, sidebars/widgets (classic + app-shell) | [x] |
| H3 | `{wp_body_open}`, `{language_attributes}`, `post_class`, `wp_link_pages`; comments stay off | [x] |
| H4 | Block grammar cleanup; pagination a11y; presets/blocks sync; tokens | [x] |
| H5 | `starter-content` (Home/Blog/About, posts, menus) | [x] |
| H6 | `readme.txt`, `LICENSE`, honest tags, Resources; style.css `1.1.0` | [x] |
| H7 | PHPUnit matrix expanded (26 tests); Theme Check / Unit Test = manual gate | [x] |
| H8 | PROGRESS Cycle H + version bump | [x] |

---

### Cycle I — Child themes, data & performance (2026-10-16)

**Target:** three real child themes on the `latte` framework, declarative data types, renderer abstraction, wp.org-safe split of data into a companion plugin.

| Stage | Task | Evidence | Done |
|-------|------|----------|------|
| I0 | Dev tooling deps (wpcs 3.4.1, latte 3.1.6, `bun.lock`); PHPCS php-only scope, 0 errors; `theme:build` keeps hand-authored 404 | `2fb8637`, `3954675`, `2dfc0e6` | [x] |
| I1 | Token-only Tailwind v4 build (`css:build`); `lint:design` for 8px grid + token colors (12 violations fixed); later catches half-steps and arbitrary px | `0816bc1`, `ce0bf80`, `.workspaces/css-build`, `tools/lint-design.ts` | [x] |
| I2 | Lucide icons via `{icon()}` Latte function (+ Twig `icon`), replace text glyphs; tests | `8a013bb`, `1ae0aad`, `tests/IconsTest.php` | [x] |
| I3 | Opt-in comments (`wpfasty_comments_enabled`), `wpfasty_post_data` filter, query-driven context for custom views | `8a013bb`, `1ae0aad` | [x] |
| I4 | `latte-press` — classic blog (sidebar, threaded comments, editorial list) + demo content | `28104f7`, `tools/demo/press.php` | [x] |
| I5 | `latte-folio` — portfolio: `project` CPT + types, filterable work grid, case-study page; `wpfasty_posts()` child API | `6354935`, `themes/latte-folio/types/project.php` | [x] |
| I6 | `latte-shop` — WooCommerce-free: `product` CPT + categories, price/sale/SKU/stock, HMAC-signed cookie cart, nonce-protected cart/checkout, private orders with admin status; cart/money tests | `4a77f4d`, `themes/latte-shop/inc/cart.php`, `tests/ShopCartTest.php` | [x] |
| I7 | Declarative post-type modules `types/<name>.php` (DI `types.registry`); folio/shop moved onto it (identical HTML) | `891c48f`, `db37f6a`, `dc56371`, `6f93ce9` | [x] |
| I8 | `RendererInterface` with `LatteRenderer` + `TwigRenderer` (`wpfasty_renderer` filter, shared root chain) | `8aa29db`, `themes/latte/classes/Template/` | [x] |
| I9 | No N+1: batch-prime meta/terms/thumbnails/authors for post lists; guard `tools/perf/queries.php` (`bun run perf:queries`) | `3ebdc4f` | [x] |
| I10 | i18n: remaining shop/folio strings translated; `.pot` for all four themes (`bun run i18n:pot`) | `7cf822e`, `themes/*/languages/*.pot` | [x] |
| I11 | Companion plugin `plugins/wpfasty-types` (CPT/taxonomy/meta/metabox registration); cart cookie without base64; `widgets_init`, core CSS classes, readme/copyright | `e175b8a`, ADR-001 §3a | [x] |
| I12 | Theme Check 0 REQUIRED for latte, latte-press, latte-folio, latte-shop (local WP 7.1.3, theme folders) | `e175b8a`, `docs/agent/tooling.md#theme-check` | [x] recorded · re-verify on zip |
| I13 | Money in integer cents, currency from `wpfasty_currency` (formatting, orders, seeder); typed REST meta (null when unset) | `2974d4d`, `b278ca2`, `a22fc97` | [x] |
| I14 | WordPress Playground blueprints (`playground/{press,folio,shop}.json`) + built zips; companion plugin activated for folio/shop; `playground/urls.txt` generated by `tools/playground-build.sh` | `aff9cf5`, `4895c4d`, `fb39725` | [x] |
| I15 | Agent KB: `docs/agent/` (ADR-001, manifest schema, requirements, wp.org review, tooling, proposed CI) | `ce0bf80`, `120b734`, `774d3ef` | [x] |

---

### Cycle J — Builder plugin `wpfasty-builder` (2026-10-17)

**Target:** landing pages assembled from a ui8kit block catalog, compiled to Latte and rendered on the front end; editable in wp-admin.

| Stage | Task | Evidence | Done |
|-------|------|----------|------|
| J1 | Export ui8kit catalog + Latte blocks from codegen-x as a builder source | `d119db1`, `tools/builder/export-ui8kit.ts`, `plugins/wpfasty-builder/sources/ui8kit` | [x] |
| J2 | Plugin core: sources, layout entries, Latte compiler, front rendering, REST (`/catalog`, `/layout/{id}`, `/sources`) | `6628537`, `plugins/wpfasty-builder/src/` | [x] |
| J3 | Isolated block stylesheet (Tailwind v4 + codegen-x kit, selectors scoped to `.wpfb-page`) | `f031532`, `editor/build-css.mjs`, `assets/blocks.css` | [x] |
| J4 | Svelte 5 editor for wp-admin (Vite IIFE, global `WPFastyBuilder`) | `793236a`, `plugins/wpfasty-builder/editor/` | [x] |
| J5 | Link tokens resolved at render time (`Links::resolve`) — layouts survive domain changes | `52d20e9`, `src/Links.php` | [x] |
| J6 | Seeder: landing as front page, added to a nav menu location; two landings per site (press subscribe, folio services, shop sale) | `4338fd3`, `aee281f`, `093ac94`, `fca1acd` | [x] |
| J7 | Playground installs `wpfasty-builder` and seeds landings | `f3379ff`, `fca1acd` | [x] |
| J8 | Bindings: kind-aware card link labels; on-sale filter for product bindings | `082c0fd`, `4497fd5` | [x] |
| J9 | Layouts validated by published `@ui8kit/contract` (`validateCollectionV04`); remote source packing | `71a53bb`, `35ea512`, `tools/builder/validate-layouts.ts` | [x] |
| J10 | PHPCS line wrapping in builder / types / shop money | `0c2b93d`, `79d0bbb` | [x] |

### Cycle K — Configurator (2026-10-19)

**Target:** Underscores-style configurator — pick archetype + types + engine + brand tokens + blocks/landings, get parent + child zip + types-plugin config from one manifest.

| Stage | Task | Evidence | Done |
|-------|------|----------|------|
| K1 | Site manifest v0.2: one schema (engine, theme meta, archetype, parent preset, features, brand tokens, currency, postTypes/fields/taxonomies/capabilities/archive/views, templates, builder landings, output); `date` field type; press/folio/shop examples reproduce the child themes; `bun run manifest:validate` (ajv + rules + repo parity) in CI `verify`; 0.1 draft `docs/agent/schema/theme-manifest.schema.json` removed | `f8bbacf`, `ce4dcc3`, `70c4c64`, `77c0703`, `61398c7`, `schema/site.manifest.schema.json`, `manifests/examples/`, `tools/manifest/`, `docs/validation.md` | [x] |
| K2 | Generator `bun run theme:generate <manifest> [--out dir]`: manifest + archetype package (`presets/archetypes/<id>/`) → style.css, functions.php, tokens.css, `types/*.php`, views, readme.txt, `.pot` (wp-cli) | `9eedf43`, `71f0eda`, folio/shop: `b7f82ee`, `0fb755e`; folio/shop `types/*.php` regenerated (`a554ab1`, docblock-only diff, dumper content identical) | [x] |
| K3 | Round-trip gate `bun run theme:roundtrip` (CI `verify`): examples regenerate byte-identical to `themes/<slug>` (only `.pot` date/generator lines normalized); Theme Check on the generated theme (CI `wordpress`, 0 REQUIRED) | `572dff8` (press), `4449ddd` (folio, shop) | [x] |
| K4 | `bun run theme:package <manifest> [--out dist/<slug>/]`: parent `latte.zip` (reused), generated child zip, `wpfasty-types.zip` (companion), `wpfasty-builder.zip`, separate `demo.zip` (seeder, bundled images, landings), `blueprint.json` + `<slug>-playground.zip` bundle (all resources `bundled`, no external URLs); wpfasty-types keeps reading the active theme's `types/`. CI packages the three examples, runs Theme Check / N+1 guard / landing smoke on the unzipped packages, uploads `dist` | `3408eb8`, `7939780`, CI run 39402141286 | [x] |
| K5 | Configurator front ends over one pure `buildManifest(answers)` (`.workspaces/theme-builder/src/configure/`): fixed palettes `presets/palettes/` (free tokens only past a WCAG AA 4.5:1 gate), extra post types on any archetype (clash / reserved-name checks); `bun run theme:new-site` (interactive or `--answers`, `--package`); MCP stdio server `tools/mcp/` (5 tools, writes only to `dist/`); static form `configurator/index.html` (`bun run configure:form`); CI: self-test (examples rebuilt exactly from answers), form drift, MCP smoke, 4 answers fixtures → package → Theme Check 0 REQUIRED. Playground bundle verified end to end locally (Playground CLI, Node 24) | `1968cd9`, `80bbf2f`, `c512817`, `2a2bb0c`, `b6ce7c9`, `f5f4994`, `70197ba`, CI run 39409753325 | [x] |
| K6 | Twig engine, equal to Latte (design, builder, wordpress.org). **Done when parity is green:** fixture parity + builder parity + live WP engine matrix in CI | K6.1–K6.14 | [x] |
| K6.1 | Press Twig views: `presets/archetypes/press/views/blocks/post-list.html.twig` (+ round-tripped copy in `themes/latte-press`), same DOM as the `.latte` twin via the existing `TwigRenderer` (ADR-001 §1, `.html.twig` suffix, codegen Twig primitives, `include … only`); parity gate `bun run parity:press` (`tools/parity/press.php`, fixtures `tests/fixtures/parity/press/*.json`, Latte vs Twig normalized HTML) in CI `verify`; `lint:design` covers `.twig` views. twig/twig stays a root dev/tooling dependency (children ship no composer/vendor); engine selection unchanged (`wpfasty_renderer`), `engine: twig` still reserved until parent page/layout views exist | K6 step 1 commit | [x] |
| K6.2 | Parent Twig views: `layout/{default,app-shell}`, `layout/parts/*`, `pages/*`, shared `presets/blocks/*.html.twig`; `safe_url` filter (= Latte URL check, lint requires it on href/src/action) and WP hook functions in `TwigRenderer`; `bun run parity` (`tools/parity/parity.php`, suites latte/press/folio/shop, full-page fixtures dumped from QA by `tools/parity/dump-context.php`); `tools/parity` in strict phpcs | `22a0272`, `14000ae` | [x] |
| K6.3 | Folio + shop Twig views (archetype presets + child themes), fixtures incl. edge cases (empty cart, checkout errors, out of stock) | `a69de11`, CI fix `8c845e3` (supervised `php -S`, runner segfault) | [x] |
| K6.4 | `engine: twig` real: schema/configurator/generator; parent build `theme:pack -- latte --engine twig` → `dist/latte-twig.zip` (bundles twig/twig + Twig primitives), shipped as the site's `latte.zip`; child gets Twig-only views + `wpfasty_renderer` switch in functions.php; twig sites: `output.wporg = false`, builder off (builder plugin falls back to normal page under non-Latte renderer); parity coverage check (every `.latte` needs a twin) | `85620cb` | [x] |
| K6.5 | Live WP check: `tools/qa/renderer-switch.php` (QA/CI mu-plugin, never shipped), `tools/qa/live-render.sh`, `tools/parity/compare.php`; CI wordpress job renders press/folio/shop/twig-gazette with latte and twig (codes, no notices, identical HTML) + N+1 guard under Twig | `0d2d87a` | [x] |
| K6.6 | Docs: ADR-001 status, tooling, PROGRESS | `c75981e` | [x] |
| K6.7 | Engines equal — wordpress.org: wporg ban for `engine: twig` removed (schema/configurator/docs); `latte-twig.zip` credits bundled Twig/Symfony (BSD-3/MIT) in readme.txt; CI artifact + Theme Check of `latte-twig.zip` | `1bccce3` | [x] |
| K6.8 | Builder in both engines: `Compiler::compile($entry, $catalog, 'latte' \| 'twig')`, Store writes both twins, Front attaches `wpfb_block` to Latte or Twig; builder-off-for-twig removed; parity suite `builder`; live CI landings compared; Twig `auto_reload` like Latte | `4cbb6ee`, CI `9c72931` | [x] |
| K6.9 | Asymmetries closed: shared `ViewHelpers` (esc_url, kses, safe_url, t) in both engines; children of both engines ship both view sets (engine = default renderer); Twig→Latte fallback without twig/twig; MCP `engines`; CI engine matrix (`tools/matrix/engine-variants.ts`: every site packaged, Theme-Checked, live-rendered in both engines; twig children on `latte.zip`) | `fa7ff1d` | [x] |
| K6.10 | Docs: ADR-001 (engines equal, helper vocabulary, parity = K6 completion criterion), tooling, MCP README, PROGRESS | K6.10 commit | [x] |
| K6.11 | Builder Twig block package generated by codegen-x from the same commit as Latte (`sources/ui8kit/twig`, catalog `engines`, export/pack copy both, `twig.zip`); parity suite `blocks` (92 blocks × 474 variants, Twig twin coverage), DOM normalizer | `82a3302`, CI fix `eeb03be` | [x] |
| K6.12 | Blocks render with Twig under Twig, Latte under Latte; lazy engines; Twig sandbox for remote sources; optional remote `twig.zip`; builder parity page+blocks per engine | `d3bee68` | [x] |
| K6.13 | CI: Twig site without the Latte library (latte/latte + UI8Kit Latte removed from `latte-twig`), pages + builder landings 200, no notices, no `Latte\` class loaded, DOM = with-Latte render; Twig build bundles the engine-agnostic UI8Kit runtime (`Rt`, `Classes`) in `lib/ui8kit-twig/runtime/php` | `bb90053`, fix `e699b39`, CI `39573868526` | [x] |
| K6.14 | Docs: ADR-001 (engine-native blocks, DOM contract), tooling, PROGRESS | K6.14 commit | [x] |
| K7 | Latte and Twig are isolated runtimes: a Twig site never requires or falls back to Latte and vice versa; one builder registry per runtime | K7.1–K7.8 | [x] |
| K7.1 | ADR-001 "Engines are isolated runtimes", spec docs/agent/builder-registry.md, CHANGELOG (breaking) | `29d627f`, CI `39583891938` | [x] |
| K7.2 | Builder: wpfasty-catalog@2 (runtime, semver, archive sha256), one catalog per runtime, no cross-runtime fallback (missing → '' + admin notice), runtime-checked remote import, pack-source.ts | `4d3d190`, CI `39585324158` | [x] |
| K7.3 | Theme: `Engines` (BUILT/DEFAULT), `EngineUnavailable`, no renderer fallback (front end HTTP 500 "Install the <engine> parent theme", admin notice) | `f0bae17`, CI `39585817363` | [x] |
| K7.4 | Builds: latte.zip only Latte, twig.zip (slug `twig`) only Twig; sites packaged per engine; CI Theme Check both parents + all sites, N+1 both engines, class markers, parity of twins | `b35e660`, CI `39588325086` | [x] |
| K7.5 | registry:build / registry:verify (both runtimes, immutable v<semver>, deterministic zip), CI negatives | `8ce9835`, fix `2757768`, CI `39590627850` | [x] |
| K7.6 | CI mirror isolation: other parent physically absent, remote own-runtime registry import + render over HTTP; negatives (other runtime, tampered archive, forced absent engine → 500) | `b0a2c76`, CI `39590986557` | [x] |
| K7.7 | Published https://registry.buildy.tw/{latte,twig}/wpfasty/{v1.0.0,latest}/ from ui8kit/site (`builder-registry/`, registry:validate); verified over HTTPS with registry-verify.ts | ui8kit/site `236f670` | [x] |
| K7.8 | Docs, ADR-001 status, PROGRESS | K7.8 commit | [x] |

### Cycle L — Builder editor on the Puck model (2026-10-24)

**Target:** an editor as convenient as Puck (typed fields, live canvas, overlay, history) without React. Details: `plugins/wpfasty-builder/README.md`.

| Stage | Task | Evidence | Done |
|-------|------|----------|------|
| L1 | `POST /wpfasty-builder/v1/render-block` (`Fragment`, `Compiler::blockProps`); the editor swaps the edited block's HTML in the preview, structural changes without reload. `tools/builder/render-block-check.php`: 474/474 variants × Latte and Twig equal to the full-page block (offline in CI, `--live` on Playground: 0 diffs). Edit → visible: 2257 → 778 ms (median, Playground wasm) | `5a06564` | [x] |
| L2 | Typed fields from the catalog (`editor/src/fields/`, `schema.ts` + bun tests); catalog `records` generated from codegen-x specs (`tools/builder/catalog-records.ts`, blocks.zip unchanged); tokens + design lint for the editor; Lucide icons | `8707ae8` | [x] |
| L3 | Selection overlay over the iframe (hover, selected, label, toolbar, drag grip, drop line); no in-page selection styles | `a4c6c40` | [x] |
| L4 | `EditorStore` ($state) + undo/redo (50 JSON snapshots, typing coalesced), shortcuts and buttons | `1eea3f8` | [x] |
| L5 | Nesting (slots): design only — README "Next step" | README | [ ] |
| L6 | IDE layout, step 1: resizable columns (separators on the 8px grid, min/max, double click resets, ARIA separator), collapse by bar buttons / `[` `]`, focus mode `F`, widths + collapsed columns in localStorage per user (`config.user`) | `editor/src/layout*.ts`, `Splitter.svelte` | [x] |
| L7 | Dark mode: a dark preview per variant (`<block>/<variant>.dark.webp`, class `dark` + shadcn `.dark` tokens, 640×360, quality searched for 6–10 KB; manifest format 2 `dark`, `Previews::attach` → `preview.dark`, CI `--check` 474 × 2); toolbar switch (last `top-secondary` node, Builder screen only, `aria-pressed`, Lucide moon/sun) toggles `.dark` on the editor, the preview page and the cards; localStorage per user | `Admin::darkSwitch`, `editor/src/theme*.ts` | [x] |
| L8 | New pages from the Builder: "Add New with Builder" on Pages, row action "Edit with Builder", "New page" panel (title, slug, starter: Empty / shop / folio / press landing when available) on the chooser and in the editor bar; `POST /pages` → draft + Builder draft, opens the editor; page template untouched | `Pages`, `Starters`, `NewPage.svelte` | [x] |
| L9 | Light + dark in every child theme: `.dark` with the `:root` colour keys in latte-shop / folio / press / skin; palettes (`presets/palettes/*.json`) and example manifests carry `dark`; without `brand.dark` the generator derives one (`configure/palette.ts`, hues kept, AA); `bun run tokens:contrast` (CI) checks keys + text pairs ≥ 4.5:1; front stays light | `tokens.css`, `palette.ts`, `tools/qa/token-contrast.ts` | [x] |
| L10 | Template page preview: hover / focus on a "Template pages" button opens a side panel (right of the left column, under the bar) with the tall page shot, light / dark, scrolls inside; 150 ms show delay, Esc closes; shots by `tools/builder/page-previews.ts` (62 × 2 WebP, 640 px wide, CI `--check`), `Previews::attachPages` | `TemplatePages.svelte`, `peek.ts` | [x] |
| L11 | Every block opens its fields: audit of the 474 card variants, 62 template pages, 3 starters and 6 demo landings (static + a click on each of 760 blocks in the live editor). Fixed in the editor: sample keys the codegen-x records / props do not declare get fields typed by the value (`labels.title`/`lead`… in 18 blocks, 65 variants; hero `prompt`: `composer`, `nav`, `badgeMark`), the sample is all variants merged; a layout block outside the open collection (press starter / landing `cta:banner` under `content-blog`) is looked up in the whole catalog. Nothing excluded. Gate `editor/tests/coverage.test.ts` (Latte + Twig); `NO_SETTINGS` for blocks without settings (none) | `schema.ts` (`typeOfSample`, `mergeSamples`), `App.svelte`, `util.ts` | [x] |

---

## План макросов

Эпик: [alexy-os/chaosbun#88](https://github.com/alexy-os/chaosbun/issues/88).

План макросов WP FastY. Владелец 2026-10-28: «все они будут реализованы».

**Ядро (wpfasty-builder)**
- [x] 0. Дропдаун макросов ссылок: иконка в поле-ссылке, 3 колонки, поиск, подсказка о результате (`GET wpfasty-builder/v1/links`, `editor/src/links.ts`, `fields/LinkPicker.svelte`)
- [x] 1. Данные сайта: `{site:name}`, `{site:phone}`, `{site:email}`, `{site:address}`, `{site:hours}` (`Links::data()`; `name` = SettingsV04 `title`, остальные — блок контактов SettingsV04 `contact.phone|email|address`, `hours` (ui8kit/contract `8a09c8f`): опции wpfasty-types `wpfasty_contact_*`, `wpfasty_hours*`, `wpfasty_map`, страница Settings → Contact & hours (`WPFastyTypes\Contact`, санитайзеры, i18n), Mapper отдаёт их в `/codex/settings`; `{site:hours}` — «Mon–Fri 09:00–18:00; Sat 10:00–16:00»; пустое поле — пустая строка)
- [x] 2. Ссылки на записи, товары и термины: `{post:<slug>}`, `{product:<slug>}`, `{term:<tax>:<slug>}` (`WPFastyBuilder\Records`; товары — purchasable-тип, термины — таксономии Codex-манифеста wpfasty-types; все токены страницы грузятся пачкой в `Front::context` / `render-block` (`Links::prime`): один запрос на тип — записи, товары (+ мета), термины всех таксономий; не найдено, нет типа или таксономии — `#`; в пикере колонки Posts, Products, Terms вторым рядом, `GET /links` по-прежнему один на сессию; `{category:}` остаётся в Sections, Terms — остальные таксономии). Вопросы к контракту — в эпике
- [x] 3. Действия: `{tel}`, `{mailto}`, `{whatsapp}`, `{telegram}`, `{map}` (`Links::contact()`; без исходного значения — `#` и предупреждение в редакторе; источники — `contact.phone`, `contact.email`, `contact.links` (`whatsapp` — номер из links, иначе телефон; `telegram` — handle), `{map}` — пин по `map.lat/lng`, иначе поиск по `contact.address`, провайдер `map.provider` (osm по умолчанию, google, yandex, apple))
- [x] 4. Цены и наличие: `{price:<product>}`, `{stock:<product>}` на пайпах `|settings:currency` и `|in-stock` (товар читается как Codex entry: `metadata.<price>.value` в минорных единицах, эффективная цена — sale, если ниже (как `DataSources`); валюта `metadata.currency.value|settings:currency`, формат `Codex\Format::currency`; `metadata.<stock>.value|in-stock` → «In stock» / «Out of stock», пустой остаток = в наличии; нет товара или цены — пустая строка; в пикере текстовых полей колонки Prices и Stock; в href не допускаются (`Layout::safeHref`). N+1: `wp eval-file tools/perf/queries.php macros 2 6` — 8 запросов и на 2, и на 6 блоков × 5 токенов, в CI)
- [x] 5. Дата и время: `{year}`, `{today}`, `{open-now}` по часам работы (`{year}` и `{today}` — на сервере, `date_format`, таймзона сайта; `{open-now}` — только на клиенте: в HTML пустой `<span data-wpfb-open-now>`, один инлайн-скрипт в футере с `hours`, `hours_exceptions`, `timezone_string` (иначе `gmt_offset`), пересчёт раз в минуту — кеш страницы не ломается; без часов работы ничего не выводится; в пикере редактора `{open-now}` пока нет — editor/src в параллельной задаче)
- [ ] 6. Формы и якоря: `{form:booking}`, `{anchor:<block>}`, переживают перемещение блока
- [ ] 7. Медиа: `{logo}`, `{media:<id>}`, переживают импорт и Playground
- [ ] 8. Переводы: значение по текущей локали (Codex localized)

**Отдельный плагин: мультилендинг**
- [ ] 9. Варианты по UTM на одной странице и одном slug: любой проп или блок целиком (заголовок, картинка, опция, блок), например мангал: шашлык или люля-кебаб. Выбор варианта на клиенте, все варианты в HTML или `<template>`, кеш страницы не ломается, по умолчанию — обычный блок.
- [ ] 10. Позже: гео, время суток, A/B.

Порядок: 0, затем 1, 3, 5 (просто, без новых запросов), затем 2 и 4 после согласования контракта, затем 6, 7, 8. Плагин мультилендинга — отдельным эпиком.
Правила: без N+1, один запрос справочников на сессию редактора, сетка 8+4, токены `--wpfb-*`.

---

## Commands cheat sheet

```bash
bun run ui:primitives
bun run ui:install
bash tools/composer.sh install --working-dir=themes/latte
bun run theme:build -- latte
bun run theme:build -- latte-skin
bun run theme:pack -- latte          # → dist/latte.zip
bun run test                         # PHPUnit via tools/composer.sh
bun run phpcs
bun run manifest:validate          # site manifests (schema + rules + parity)
bun run theme:generate manifests/examples/press.json --out /tmp/latte-press
bun run theme:roundtrip            # generated == themes/<slug> for packaged archetypes
bun run theme:package manifests/examples/shop.json   # → dist/latte-shop/ (zips + Playground bundle)
bun run theme:new-site --answers manifests/answers/press-events.json --package   # answers → manifest → dist/<slug>/
bun run configure:test && bun run configure:form && bun run mcp:smoke

# Docker (local)
bun run dev:up
bun run dev:wp -- theme activate latte
bun run dev:wp -- theme activate latte-skin
```

---

## Package versions (aligned)

| Package | Version |
|---------|---------|
| `wpfasty` (root `package.json`) | `0.3.0` |
| `@wpfasty/theme-builder` | `0.3.0` |
| `themes/latte` style.css | `1.1.0` |
| `latte-press` / `latte-folio` / `latte-shop` / `latte-skin` style.css | `1.0.0` |
| `plugins/wpfasty-types` | `1.0.0` |
| `plugins/wpfasty-builder` | `0.1.0` |
| `@ui8kit/contract` (root devDependency) | `^0.2.0` |
| Root Composer tooling | phpunit ^10.5, phpcs, wpcs |

---

## Decision log

| Date | Decision |
|------|----------|
| 2026-07 | YM deleted; `themes/latte` is reference parent |
| 2026-07 | Child skins = WP child themes, no Composer |
| 2026-07 | Release zip bundles primitives under `lib/ui8kit-latte` |
| 2026-07 | Presets SSOT for blocks; builder emits page views |
| 2026-07 | Minimal `ui8kit.js` until `@ui8kit/aria` package |
| 2026-07 | Docker smokes deferred to local machine |
| 2026-07 | Theme path-repo uses `symlink: false` for Windows PHP |
| 2026-07 | Pack uses `composer.phar` + PowerShell zip on Windows |
| 2026-07 | **Cycle H:** target WordPress.org classic directory |
| 2026-07 | **Cycle H exclusions:** WooCommerce, FSE, comments/PII forms, RTL |
| 2026-07 | Comments remain disabled until a privacy-safe plugin/option |
| 2026-07 | Sheet IDs live in `ContextFactory` only (no orphan includes) |
| 2026-10-16 | Comments become opt-in (`wpfasty_comments_enabled`), off by default |
| 2026-10-16 | ADR-001: theme keeps presentation-only `types/<name>.php`; data registration moves to `plugins/wpfasty-types` |
| 2026-10-16 | Shop is WooCommerce-free; money stored as integer cents, currency via `wpfasty_currency` |
| 2026-10-16 | Previews via WordPress Playground blueprints (repo is private; zips mirrored outside the repo) |
| 2026-10-17 | Landings are a plugin (`wpfasty-builder`), not theme code; layouts validated by `@ui8kit/contract` |
| 2026-10-21 | K6.11–K6.13: builder blocks are engine-native (codegen-x Latte + Twig packages from one commit); a Twig site needs no Latte library (CI-checked); block/landing parity = codegen-x DOM contract; remote sources without `twig.zip` fall back to Latte |
| 2026-10-21 | K6.7–K6.9 (supersedes K6.4): Latte and Twig are equal options — twig sites may be wordpress.org builds (`latte-twig.zip`), builder compiles both, every child ships both view sets, `engine` = default renderer, Twig→Latte fallback; parity is the K6 completion criterion |
| 2026-10-21 | K6.4: a Twig site is not wordpress.org-eligible (the .org parent `latte.zip` stays Latte-only, no vendor); Twig parent is a separate `latte-twig` build; builder landings are Latte-only, disabled for twig sites |
| 2026-10-21 | K7: engines are isolated runtimes (supersedes the K6.9/K6.12 fallbacks). Twig parent is `twig` (twig.zip), Latte parent `latte` (latte.zip), each ships only its engine; the engine is chosen at site creation (no live switch); builder registries are per runtime at registry.buildy.tw/<runtime>/wpfasty/ (immutable v<semver>/ + latest), separate from the shadcn-style registry; parity stays a quality check |
| 2026-10-21 | K6.1: Twig views live next to Latte views (`<view>.html.twig`), DOM parity enforced by fixture rendering; twig/twig only in root tooling; renderer still picked by `wpfasty_renderer` |
| 2026-10-20 | K5: brand = fixed palette list, free tokens only with WCAG AA contrast; custom post types allowed on top of any archetype; MCP server lives in the repo (separate package later) |
| 2026-10-20 | K4: demo ships as a separate `demo.zip`; wpfasty-types keeps reading the active theme's `types/` (no generated plugin config) |
| 2026-10-19 | One site manifest (`schema/site.manifest.schema.json` v0.2) replaces the 0.1 theme-manifest draft; `presets/themes/*.json` stay parent presets referenced by `parent.preset`; platform-neutral names, money in cents; `wporg` ⇒ types in the companion plugin |

---

## Browser Cursor handoff prompt

> Continue WP FastY from `.project/PROGRESS.md`.  
> Cycles A–J are complete; follow-ups: Theme Check on `dist/latte.zip` (manual re-verify), Theme Unit Test import + keyboard sweep, Docker smoke (A7/B9), saas-design rules, optional Packagist. CI (`.github/workflows/ci.yml`) runs PHPUnit, strict PHPCS, lint:design, Theme Check and the N+1 guard.  
> Active theme: `themes/latte` (v1.1.0); demo skin: `themes/latte-skin`; child themes `latte-press`, `latte-folio`, `latte-shop`; plugins `wpfasty-types`, `wpfasty-builder`.  
> Use `bash tools/composer.sh` for Composer; `bun run theme:pack -- latte` for zip.  
> Discipline: `docs/` + `.cursor/rules/`. Do not revive YM. Do not hand-edit `ui8kit-latte`.  
> Do not add contact forms or WooCommerce for .org; comments stay opt-in. CPTs belong in `wpfasty-types`, never in a theme.

---

## Related docs

| Doc | Use |
|-----|-----|
| [constructor/child-skins.md](./constructor/child-skins.md) | Skin contract |
| [constructor/theme-constructor.md](./constructor/theme-constructor.md) | Builder design |
| [constructor/shell-composition.md](./constructor/shell-composition.md) | App shell recipe |
| [docs/install.md](../docs/install.md) | Zip + monorepo install |
| [docs/architecture.md](../docs/architecture.md) | Layers |
| [themes/latte/README.md](../themes/latte/README.md) | Parent setup |
| [themes/latte/readme.txt](../themes/latte/readme.txt) | WP.org readme |
| [themes/latte-skin/README.md](../themes/latte-skin/README.md) | Skin demo |
| [docs/agent/README.md](../docs/agent/README.md) | Agent KB (ADR-001, tooling, Theme Check results) |
| [playground/README.md](../playground/README.md) | Playground previews |
