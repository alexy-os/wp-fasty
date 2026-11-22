# ADR 007 — UI8Kit Simple blocks only, live theme prototypes, pure constructor

Status: accepted and implemented (2026-11-22). Owner decisions t247/t248/t250: there are no consumers yet, so the
legacy block line is removed outright (no compat resolver, no migration); the Builder is a pure constructor; the Agent
is left unchanged for now. Owner decisions t251: publish registry v4.0.0, the three Simple starters as onboarding
cards, cards grouped by block with an axis switcher, and the dormant engines deleted (items 6–9).

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
   One reviewed brick fix (`kitBrick`): Latte >= 3.1.7 checks every URL attribute of `n:attr` and emptied the
   data:image placeholders of media-frame (Twig kept them), so `Image.src` is an expression attribute with `|nocheck`,
   its value still checked by `UI8Kit\Latte\Rt::imgUrlOrNull` (safe URLs, data:image only).
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
   escape) — no agent logic.
6. **Onboarding (t251).** The three shells (standard / blank / sidebar) stay, and the deleted screens are replaced by
   the three Simple starters (`starters/{shop,folio,press}.json`) as onboarding cards (`OnboardingStarters.svelte`):
   each card shows its first blocks as live prototypes (the same sandboxed, script-free iframe as the block cards, item
   4) and offers **Use as starting page** — `POST /onboarding/<page>` `{mode, starter}`; the blocks are read from the
   plugin's own starter file (`Starters::get`), never from the request, and saved through `PageOperations` with the
   draft guard — and, when the optional Chat module is installed, **Attach to Chat** (the starter's `{source, starter,
   blocks}` JSON becomes the Chat attachment). `PageOnboarding::reference($source, $id)` is kept as the port the
   unchanged Agent calls for an attachment (`Chat::turn` `screenId` → `referenceScreenData`, calling it unconditionally
   once the class exists, so deleting the method would make every attachment turn a fatal error): it now returns the
   composition of an available starter of that source and null otherwise. The **collection import path** behind it —
   the scan of template collections of installed sources and their pages — is deleted (item 9).
7. **Cards grouped by block (t251).** One card per block (`cards.ts` `groupsOf`, `BlockCards.svelte`) with the
   variant's live prototype and an axis switcher like buildytech/chat: chips per axis value (a select beyond five
   values); a click picks the variant with that value that keeps most of the other axis values (`pick`); the variant
   label and count are shown, the card drags or adds the selected variant. Switching changes only the iframe's
   `#<block>--<variant>` (no reload, no script). Search filters by block, docs, group, variant ids and labels. The
   image fallback for imported cards (`Variant.preview`, `previewSrc`) is deleted: a variant without a prototype shows
   its name only; registries carry no card images.
8. **UI from chat, shell from WP.** The Builder shell (layout, skin, sidebars, inspector, overlay) is kept. Borrowed
   from chat: live wireframe cards instead of screenshots, contract variant labels, axis-driven variants.
9. **Deleted dormant engines (t251).**
   - *Template collections*: `collections` of the catalog and of registry imports (dropped on import), the REST
     `collection` parameter, `metadata.collection` of layouts, the editor collection select, template pages and page
     peek (`TemplatePages.svelte`, `peek.ts`), the manifest `layout.collection` field, `tools/builder/catalog-v2.ts`.
   - *Data bindings*: layout `bindings` (a `bindings` member is reported as `layout.binding_unsupported` and dropped;
     an empty one is accepted silently), block `data` and variant `bindings`/`current` of the catalog,
     `Catalog::bindable/slot/current*/pager/slotsByKind`, `DataSources` (resolve, paged, current record, trusted
     markup, plan, the `wpfasty_builder_data_sources` filter), `Pager` and its head links, `Codex\Compat` with
     `tools/codex/compat.php`, the `wpfb` page context and bound expressions of `Compiler`, the kind-aware read label,
     `Fragment` resolvers, the editor `BindingField`, data-source UI (`sources.ts`) and adapter-record trimming, and the
     manifest schema `layoutBlock.bindings`. Kept: `Records` (link tokens `{post:…}`, `{price:…}`, read through
     `SiteModels`, the site's Codex models), `Codex\ReadPath`/`Sources`/`Format` (shared codegen-x vectors), and
     `Layout::untrust` / the `__html` drop of `Layout::clean` (defence in depth: no input carries trusted markup).
   - The editor `Item` type keeps a never-set `bindings?: undefined` only so the unchanged Agent editor file
     (`modules/builder-agent/editor/context.ts`, which reads `item.bindings`) still type-checks.
10. **Registry v4.0.0 (t251)** is published to registry.buildy.tw through the existing path (ui8kit/site
    `registry:wpfasty`, from the green CI artifact of the final commit) — see `builder-registry.md`.

## Agent impact (Agent module left unchanged; to adapt in phpfasty/wpmodules)

- Catalog search uses the bundled Stock map (local-search) of the removed families: over the Simple catalog it finds
  nothing, so turns fail closed (no proposal, nothing written). Needs a Simple map built from `contracts.json`
  (item, axes, variant labels, model) or contract-driven search.
- Bindings are gone (item 9) but the Agent still speaks them: the worker (`core.mjs`) rejects a context or proposal
  block without a `bindings` object (`invalid_catalog_block`); the editor context (`context.ts`) now sends
  `bindings: undefined` and the stored draft blocks have no `bindings`; `propose_layout` requires `bindings` and
  `get_block_details` describes data bindings; the catalog snapshot (`PageOperations::snapshot`) no longer has block
  `data`. The Agent must drop `bindings` from its schemas and checks. The server accepts an empty `bindings` from it.
- Attachments: `screenId` is now a starter id (`shop`, `folio`, `press`); `PageOnboarding::reference()` returns
  `{schema: wpfasty-screen-reference/1, source, id, blocks[{id, block, variant, props}]}` (no `bindings`). The Agent's
  copy ("Reference screen") can say "starter".
- Catalog payload: 52 blocks, props without axis keys, `contract` per block; prompts/size limits should be re-measured.
- Worker unit tests read the frozen pre-ADR-007 catalog `tests/fixtures/agent-catalog/` (test-only, not shipped); the
  CI step "Real PHP to Node module resource" runs with `continue-on-error` until the Agent is adapted. Remove both then.
- Optional follow-up for the Builder: the format-1 remote catalog compat (`Catalog::forRuntime`) and the
  `Item.bindings` type stub once the Agent no longer needs them.

## Verification

composer test, PHPStan max (no baseline), PHPCS, php-cs-fixer, Rector, Deptrac, raw-sinks, sandbox check, xss-fuzz,
parity (52 blocks / 157 variants DOM-identical Latte = Twig), render-block check (157 × 2, 0 differences), editor
check/test/build, `simple-snapshot.ts --check`, registry build/verify, MCP smoke, packaging. Locally on a WP 7.1.4
(SQLite) site: onboarding contract (both engines), theme appearance contract, landing seeding + `@ui8kit/contract`
layout validation, N+1 guards, prototype route (light/dark, 404s) and headless-Chrome shots of the prototype document.
t251: the same gates plus the design lint; onboarding contract over the starters (shell + starter, reference port, no
collections) on both engines, render-block with a `bindings` member (reported, dropped) and a published starter page
rendered on the front end of the local WP. Not verified: the editor UI (grouped cards, axis switcher, starter cards)
in a real browser session.
