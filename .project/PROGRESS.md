# WP FastY — Full Progress & Continuation Plan

**Purpose:** Single handoff document for agents (including Cursor browser).  
Read this first, then execute the next open cycle. Do not invent a parallel roadmap.

**Last updated:** 2026-10-19 (Cycles I–J from git log 2026-10-16..17; Cycle K: K1 site manifest v0.2, K2/K3 generator + round-trip for press, folio, shop)  
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
playground/                  Playground blueprints + zips + urls.txt
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

**→ Cycle K — Configurator** (see [Cycle K](#cycle-k--configurator-2026-10-19)); K6 in progress (step 1: press Twig views + parity gate done; next: parent page/layout Twig views) or K7 (publish the form / MCP package). Follow-ups below stay open.

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
| K6 | Twig engine: ship `.twig` views so `engine: twig` is buildable | – | [ ] |
| K6.1 | Press Twig views: `presets/archetypes/press/views/blocks/post-list.html.twig` (+ round-tripped copy in `themes/latte-press`), same DOM as the `.latte` twin via the existing `TwigRenderer` (ADR-001 §1, `.html.twig` suffix, codegen Twig primitives, `include … only`); parity gate `bun run parity:press` (`tools/parity/press.php`, fixtures `tests/fixtures/parity/press/*.json`, Latte vs Twig normalized HTML) in CI `verify`; `lint:design` covers `.twig` views. twig/twig stays a root dev/tooling dependency (children ship no composer/vendor); engine selection unchanged (`wpfasty_renderer`), `engine: twig` still reserved until parent page/layout views exist | K6 step 1 commit | [x] |

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
