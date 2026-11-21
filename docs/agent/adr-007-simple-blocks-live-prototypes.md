# ADR 007 — UI8Kit Simple blocks only, live theme prototypes, pure constructor

Status: accepted and implemented (2026-11-22). Owner decisions t247/t248/t250: there are no consumers yet, so the
legacy block line is removed outright (no compat resolver, no migration); the Builder is a pure constructor; the Agent
is left unchanged for now.

## Context

buildytech/chat replaced its Stock catalog (complex blocks, screens, thumbnails) with the UI8Kit **Simple** line:
52 blocks, 157 variants, each described by a `block-contract/v1` (item, version, axes, labelled variants, settings,
places, content model JSON Schema). Its cards are live wireframe prototypes that follow the page skin, not images.
The WP Builder bundled 93 older block families (codegen-x export), 481 WebP card shots, 63 template-page shots and 12
onboarding screens built from those families.

## Decision

1. **One bundled source, Simple only.** `plugins/wpfasty-builder/sources/ui8kit` is a pinned snapshot refreshed only by
   `bun tools/builder/simple-snapshot.ts` from `snapshot.config.json` (buildytech/chat `b41c414f`, ui8kit/prototypes
   `8748fcb3`, CLI `ui8kit@3.5.0`); `--check` is offline and runs in CI (every file's sha256, derived catalogs fresh, a
   structural wireframe per variant). The plugin never fetches anything at runtime. The derived `wpfasty-catalog@2`
   (`latte/` and `twig/catalog.json`, version `4.0.0` — registry versions are immutable) carries each block's
   `contract {item, version, block, organism, model}`, `axes` and `defaults`; a variant is one axis combination with its
   contract label. Axis keys are not editable props: the variant decides them.
2. **Legacy removed.** The 93 families, their templates, previews, page previews, icons, the 12 screens
   (`OnboardingScreens.svelte`, `Previews.php`), `export-ui8kit.ts`, `previews.ts`, `page-previews.ts`,
   `preview-html.php`, `catalog-records.ts` and the pager/richtext perf guards are deleted. Starters and demo landings
   are rewritten with Simple blocks (static content, no bindings). Saved pages of the old families are not migrated.
3. **Compose isolation.** The theme ships its own, older `CodegenX\Compose`; the Builder uses its namespaced copy of the
   CLI's (`WPFastyBuilder\Kit\{Latte,Twig}\Compose`, `<engine>/php/Compose.php` + `compose.json`): Latte compositions
   are rewritten to call it, Twig registers `compose_props` / `compose_rc` / `compose_join` to it. Theme kit presets keep
   reading the theme's own kit-compose build (`tools/ui8kit3/presets.php`), so the theme is unchanged.
4. **Live prototypes, script-free.** A card is `<iframe sandbox="allow-same-origin">` (no `allow-scripts`; CSP unchanged)
   on the page preview URL (`wpfb_preview` + page nonce + `edit_page`) with `wpfb_prototypes=<block>` and
   `#<block>--<variant>`. `Front::prototypes()` prints the theme's real `wp_head()` — parent and child tokens, fonts and
   the light/dark scheme forced server-side (`wpfb_scheme`, ADR-004) — and the static wireframe sheet of the block
   (`prototypes.json`: the `unset` DOM fixtures of ui8kit/prototypes, allowlisted tags and `class` / `aria-hidden` /
   `role` / `data-*` only, no links, sources, handlers or inline style). `assets/prototypes.css` (Tailwind over the
   fixtures + `prototypes.tokens.css`, scoped to `.wpfb-protos`) draws them; `.wpfb-proto:target` shows one variant.
   No `wp_footer`, no admin bar, `noindex`, 404 for an unknown block.
5. **Pure constructor.** Insert / reorder / remove / duplicate, the live page preview, and editing a block's contract
   fields (typed fields from the catalog props; `Layout::normalize` sanitizes with the WP functions, the templates
   escape) — no agent logic. The data-binding engine (DataSources, Pager, Records, Codex, RichText current record)
   stays in the code but is dormant: no Simple block declares data; removing it needs the Agent adaptation below.
6. **Onboarding** keeps the three shells (standard / blank / sidebar) plus the optional Chat entry. No bundled screens;
   `PageOnboarding::reference()` still resolves template-collection pages of an installed source for the Agent and
   returns null for the bundled one.
7. **UI from chat, shell from WP.** The Builder shell (layout, skin, sidebars, inspector, overlay) is kept. Borrowed
   from chat: live wireframe cards instead of screenshots, contract variant labels, axis-driven variants.

## Agent impact (Agent module left unchanged; to adapt in phpfasty/wpmodules)

- Catalog search uses the bundled Stock map (local-search) of the removed families: over the Simple catalog it finds
  nothing, so turns fail closed (no proposal, nothing written). Needs a Simple map built from `contracts.json`
  (item, axes, variant labels, model) or contract-driven search.
- The worker requires a `bindings` object per block and `get_block_details` describes data bindings; Simple blocks
  have none (send `{}` / drop the wording).
- Screen references (`screenId`) always fail with "not installed": no bundled screens.
- Catalog payload: 52 blocks, props without axis keys, `contract` per block; prompts/size limits should be re-measured.
- Worker unit tests read the frozen pre-ADR-007 catalog `tests/fixtures/agent-catalog/` (test-only, not shipped); the
  CI step "Real PHP to Node module resource" runs with `continue-on-error` until the Agent is adapted. Remove both then.
- Optional follow-ups for the Builder: remove the dormant binding engine and the format-1 remote catalog compat
  (`Catalog::forRuntime`) once the Agent no longer needs them.

## Verification

composer test, PHPStan max (no baseline), PHPCS, php-cs-fixer, Rector, Deptrac, raw-sinks, sandbox check, xss-fuzz,
parity (52 blocks / 157 variants DOM-identical Latte = Twig), render-block check (157 × 2, 0 differences), editor
check/test/build, `simple-snapshot.ts --check`, registry build/verify, MCP smoke, packaging. Locally on a WP 7.1.4
(SQLite) site: onboarding contract (both engines), theme appearance contract, landing seeding + `@ui8kit/contract`
layout validation, N+1 guards, prototype route (light/dark, 404s) and headless-Chrome shots of the prototype document.
Not verified: the editor UI with the cards in a real browser session.
