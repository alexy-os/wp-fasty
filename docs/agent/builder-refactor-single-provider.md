# Builder refactor: single block provider, shared agent scenarios, shared session standard

Status: technical specification for PHPIngineer. Owner request 2026-11-10. Written by Grok Bot (BuildY chat side).
Tracking issue: alexy-os/chaosbun (PHPIngineer, "single provider" refactor). Every fact below was checked on
2026-11-10 (UTC+3) against the source named next to it. Items marked **proposal** are shapes that do not exist yet
and must be agreed before they are frozen. Items marked **blocked on** wait for another repository.

## 1. Goal

One supply of blocks for every BuildY host, one way to get them, one search, one set of agent scenarios, one
session format:

1. **Single provider.** `codegen-x/ui8kit` -> `registry.buildy.tw` (one `@ui8kit` registry). The WordPress
   Builder plugin installs blocks the same way BuildY chat does: `npx ui8kit` CLI snapshot, pinned, hashed,
   checked offline. No plugin-owned export pipeline, no second copy of primitives.
2. **Derived catalog.** `catalog.json` stops being a source file. It is a build-time cache computed from the
   registry snapshot + `variants/v0.json` + `props/v0.json` + data bindings expressed against Codex schemas.
3. **Same agent scenarios** in chat and WP with identical semantics: **Ask** (read-only), **Build** (page edits),
   **Create** (custom block code, validated, never auto-published). Same tool definitions, same search algorithm.
4. **Same session standard.** One versioned session JSON, one localStorage namespace and key layout, one devtools
   export command, and the export opens in Lab BuildY (`buildytech/lab-console`).

Plain acceptance (owner): a webmaster gets the same blocks, the same answers to "which feature blocks are there"
and the same page edits in BuildY chat and in the WP Builder; the owner runs one command in devtools in either
host and opens the result in Lab.

## 2. Decisions this spec encodes (read first)

| Decision | Where |
|---|---|
| One BuildY product, three hosts (Browser, WordPress adapter, Desktop later), one Stock catalog | [buildytech/chat ADR-0007](https://github.com/buildytech/chat/blob/main/.project/adr/0007-one-buildy-three-hosts.md) |
| The registry is the single source of UI; snapshot = literal CLI output; lib holds logic only | [buildytech/chat ADR-0008](https://github.com/buildytech/chat/blob/main/.project/adr/0008-registry-single-source-of-ui.md) |
| Brands live in CodeGen-X (badge + filter, closed trusted contribution); one published `@ui8kit` registry; brand as `meta.brand` | [buildytech/chat ADR-0009](https://github.com/buildytech/chat/blob/main/.project/adr/0009-brands-in-codegen-x-single-registry.md) |
| Sequence and open WordPress questions | [buildytech/chat docs/plans/one-buildy-three-hosts.md](https://github.com/buildytech/chat/blob/main/docs/plans/one-buildy-three-hosts.md) |
| Chat reference implementation of the snapshot | [buildytech/chat scripts/registry-snapshot.mjs](https://github.com/buildytech/chat/blob/main/scripts/registry-snapshot.mjs) |
| Page document and edit ops (page-spec 1.1.0, page-patch with `set_variant`) | [ui8kit/contract schemas](https://github.com/ui8kit/contract/tree/main/schemas), served at `https://schema.buildy.tw/page-spec.schema.json` |
| Plugin decisions that stay valid: isolated engines (ADR-001), optional Agent module (ADR-003), local search (builder-local-search.md) | this folder |

Not reopened here: author namespaces or author URLs (rejected by ADR-0009), renaming `compose` (owner: keep it;
brand is set at the app level with tokens), a WordPress runtime inside BuildY chat (ADR-0006 of chat).

## 3. Published inputs (verified live 2026-11-10)

All URLs below returned HTTP 200.

| Artifact | URL | Pin |
|---|---|---|
| Registry manifest | `https://registry.buildy.tw/manifest.json` | `source.commit` 3a6d923a (codegen-x), `contractVersion` 2.0.0, runtimes jinja2, latte, react, solid, svelte, templ, twig, vue |
| Latte index | `https://registry.buildy.tw/latte/index.json` | 341 items (block, component, lib, ui, utils) |
| Twig index | `https://registry.buildy.tw/twig/index.json` | 341 items |
| Item JSON | `https://registry.buildy.tw/<runtime>/blocks/<name>.json` | `files[]` with content, `meta.{category,group,kind,tags}` |
| Variants | `https://registry.buildy.tw/variants/v0.json` | file sha256 `c2e72d4ed35a53cae6e6e1d56a4f3b6b4c6516cc4bc8de7283fb359ec81088b4`, digest `sha256:39b97e88abe27f2946e5e3d9d0bd0192abb176986303c1f873aec4b914024f33`; 113 items, 532 variants |
| Props schemas (#138) | `https://registry.buildy.tw/props/v0.json` (+ `.sha256` sidecar) | file sha256 `2f5e08736cee3dd30abfac5fb20b3d974934383ecc5c95b5d8c13b5288b266a5`, digest `sha256:395a62bcfb3439038865637e0d278a8a41413dd1cdd22e00f0feac85fc5748da`; 117 items |
| Page spec / patch | `https://schema.buildy.tw/page-spec.schema.json`, `.../page-patch.schema.json` | page-spec 1.1.0 (ui8kit/contract 9f47cf4) |
| Codex schemas | `https://schema.buildy.tw/{entry,term,taxonomy,menu,manifest,view,dictionary,revision,settings,comment}.schema.json` | @ui8kit/contract 0.3.0 (Codex v0.4) |
| CLI | `npx ui8kit@3.4.1` | npm latest = 3.4.1 |

### 3.1 props/v0.json (alexy-os/chaosbun#138) verification

| Check | Result |
|---|---|
| URL, sidecar, manifest/registry pointers 200 | PASS |
| File sha256 equals sidecar and `manifest.json` `props.sha256` | PASS |
| `digest` = sha256 of the compact JSON without the `digest` key | PASS |
| 117 schemas = 117 registry blocks; every one of the 113 variants items has a schema with the same `organism` | PASS |
| Every schema declares draft 2020-12, meta-validates, compiles in Ajv 2020 `strict: true` | PASS (117/117) |
| All 532 variant `props` validate (required lists removed, as published presets are partial) | PASS (532/532) |
| Every variants axis (including dotted ones such as `actions.mode`) resolves to an enum containing all its values | PASS |
| `additionalProperties: false` at the top of every schema; `$defs.lucideIcon` enum of 278 names | PASS |
| Sample blocks feature-grid, hero-classic, cta-band, dish-card, sitemap-footer: prop names and required set equal the Svelte `export type <Name>Props`; prop names equal the Latte `$p['...']` reads; Latte defaults equal schema `default` | PASS (dish-card: Svelte has the extra `onadd` callback, listed in `events: ["add"]` beside the schema, by design) |
| "Listed with sha256 in the item JSON" (issue's Done-when) | NOT DONE as written: item JSON has no `propsSchema` field; the schemas are one target-agnostic file referenced from `manifest.json` and `registry.json`. Sufficient for a snapshot pin; no blocker. |
| `ui.*` dictionary (values like `{"$":"ui.<key>"}`) | Not published yet (known) |

Schema facts the plugin relies on: one schema per organism, shared by all runtimes (target-agnostic); values may be
literals, dictionary refs `{"$":"ui.<key>"}`, data refs `{"$":"entry.title"}` or ops `{op,args}`; records are
`$defs`; events and render-prop slots are listed beside the schema (`events`, `slots`), not inside it.

## 4. Current state of the plugin (facts, alexy-os/wp-fasty 06669fc)

### 4.1 Block supply

- `plugins/wpfasty-builder/sources/ui8kit/{latte,twig}/` = `catalog.json` + `blocks/`, `compose/`, `ui/` (brick
  adapters), `icons/`, `php/` (og_* runtime). Written by the hand-run `tools/builder/export-ui8kit.ts` from a
  codegen-x checkout (needs private repo access; `--check --offline` compares a recorded digest only).
- `catalog.json`: `format: 2` (`catalog@2`), `version` 3.1.0, `source.commit` 4be6a994 (codegen-x),
  `source.digest` `sha256:8e8b34d7...`; 93 blocks, 482 variants, 125 `records`, 39 `collections`
  (`type: group|template`), 133 `data` bindings on 79 blocks. Built by `tools/builder/catalog-v2.ts` and
  `catalog-records.ts`.
- `plugins/wpfasty-builder/primitives/{latte,twig}/` = a second UI8Kit install (`ui8kit.lock.json` lockVersion 2,
  CLI 3.2.1, 20 items, registry 3.3.0), read by `src/Primitives.php`. The theme has a third install
  (`themes/wp-fasty/ui8kit`, `themes/wp-fasty/twig/ui8kit`, 68 files, registry 3.2.0 per AGENTS.md).
- `sources/ui8kit/{previews,page-previews}/` (webp + manifest, `tools/builder/previews.ts`, `page-previews.ts`),
  `sources/ui8kit/styles/` (theme.css, kit.css), `starters/{shop,press,folio}.json`
  (= `layout` of `tools/builder/landings/*.json`).
- Remote sources: `src/Source/RemoteSource.php`, sandboxed `SourceLoader`; separate registry format published at
  `registry.buildy.tw/{latte,twig}/wpfasty/v<semver>/` (`tools/builder/registry-build.ts`, `registry-verify.ts`,
  `pack-source.ts`; docs `builder-registry.md`).

### 4.2 How the CLI snapshot compares (run 2026-11-10 in clean folders)

| | Latte | Twig |
|---|---|---|
| `npx ui8kit@3.4.1 init --yes --framework <t>` + `add --all` | 341/341 items | 341/341 items |
| `npx ui8kit@3.4.1 check` | 370 files OK | 372 files OK |
| Tree | `src/{blocks,compose,ui,icons,runtime,organisms}` + `composer.json`, `ui8kit.config.json`, `ui8kit.lock.json` | same |
| Shared block files byte-identical to the plugin bundle | 240/240 | 240/240 |
| Block files the plugin lacks | 19 food blocks (`dish*`, `menuboard`, `reservation`, `pizzapicker`, ...) | 29 files, same 19 blocks |
| Known drift | 6 files: Button `pressed`/`expanded`, two runtime edits (`Rt::optBoolStr`); plugin primitives lag (registry 3.3.0 / CLI 3.2.1) | same family |

### 4.3 How much of catalog.json is derivable today

Mapping key: a catalog block id is the Stock block id = `variants.items[*].block` (e.g. `about` <- item
`about-company`; `hero` groups two items, `hero-classic` and `hero-prompt`). A catalog variant is
`(item, variantId)`.

| catalog@2 field | Published source | Status |
|---|---|---|
| `blocks[].id`, `axes`, `defaults` | `variants/v0.json` `block`, `axes`, `defaults` | derivable; `hero` needs the union of its two items (catalog axes = union) |
| `variants[].id`, `set`, `props` | `variants/v0.json` `variants{id:{set,props}}` | 482/482 ids map; `set` equal 481/482 (hero/prompt: plugin has 3 extra keys `scale`, `tone`, `chrome` from the second item) |
| `variants[].organism`, `template` | variants item name -> `blocks/<item>.latte` in the snapshot | 482/482 equal |
| `blocks[].props` (TS type strings) | `props/v0.json` JSON Schema | derivable, better typed |
| `records` (125) | `props/v0.json` `$defs` per schema | derivable (record field lists) |
| `title`, `docs`, `group` | item JSON `title`, schema `description`, item `meta.group` | derivable; wording may differ, accept the registry text |
| `blocks[].data` (`as`, `kind`, `record`, `fields`, `scope`, `sample`), `variants[].bindings`, `variants[].current` | none | **not published** (dependency D2) |
| `collections` (39) | none | **not published** (dependency D3) |
| `contract` text (Codex v0.4, pipes) | ui8kit/contract | constant |

### 4.4 Agent and search today

- Agent = optional ModulY module `modules/builder-agent` (PHP adapters, Svelte panel `editor/*.svelte`, Node worker
  `worker/`). Tools in `worker/sdk.mjs`: `search_catalog`, `get_block_details({blockId, variant})`,
  `propose_layout({blocks:[{id,block,variant,props,bindings}]})`, `get_skill`. The prompt already allows plain
  conversation without a layout change.
- Search: vendored codegen-x facet-search engine (`tools/vendor/facet-search/`, codegen-x 6b2e71b) over
  `worker/catalog/stock.v0.json` (digest `sha256:a9d256e6...`), intersected with installed blocks.
- Conversation storage: server side only (post meta `_wpfb_chat_<requestId>`, `_wpfb_turn_<id>`; worker
  `node:sqlite` table `turns`). Browser localStorage holds only `wpfasty-builder:layout:<user>` and
  `wpfasty-builder:dark:<user>`.

### 4.5 BuildY chat today (the shape both hosts converge on)

- Tools (`apps/xai-runtime/src/chat-tools.ts`, `packages/builder-core/src/tools.ts`): `search_catalog`,
  `get_block_details({id})`, `propose_layout` (page-spec), `edit_page` (page-patch ops), `get_skill`. Today every
  turn is forced to call a tool (`tool_choice: "required"`), which is why "just talk" does not work in chat. The
  shared Ask mode below fixes that in both hosts.
- Search (`packages/builder-core/src/catalog/search.ts`, `text-search.ts`): facet filters `section`, `job`,
  `industry`, `blocks` validated against the pinned vocabulary (unknown value -> `catalog_facet_unknown` with
  `suggested`/`nearest`), free text `q` scored over a generated index (`scripts/build-catalog-index.mjs`: asset
  titles, labels, search words, facet labels in five locales, synonyms; no hand-written word lists), prefix match
  >= 4 chars at half weight, Levenshtein <= 2 for `nearest`, pagination.
- localStorage keys (`apps/chat-web/src/lib/...`):

| Key | Content |
|---|---|
| `buildytech-chat-sessions` | `ChatSession[]` (below) |
| `buildytech-creator-pages-v1` | `{version: 1, pages: {<sessionId>: record}}`, schema = page-spec `$defs/store` |
| `buildy.creator.selectedSessionId` | string |
| `buildy.chat.selectedModelId` | string |
| `buildy.creator.layout` | panel layout JSON |
| `buildytech-chat-theme` | `light` / `dark` / `system` |

`ChatSession = {id, title, updatedAt, messages[], pageId?, pageBound?, pageRevision?, scenarioId?, screenId?,
selectedModelId?, usage?}`; `ChatMessage = {id, role: user|assistant, text, createdAt, artifacts?, proposal?,
run?, selectedModelId?}`; `proposal = {applied, status: pending|applied|discarded|superseded, source, liveModel,
assembler?, warnings[], missing[], title, screenId?, locale?, demoData?, catalogRefs?{catalogDigest, pinnedCommit,
screen, blocks[]}, changes?{added,kept,removed}}`; `run = {requestedModelId, modelId, modelLabel, live,
policyVersion, modelCatalogVersion, trialPolicyVersion, catalogDigest?, path?, tools?, runId?, usage: RunUsage}`.

- Lab BuildY (`buildytech/lab-console` fa98a26, `internal/lab/importer.go` `ParseExport`) already imports a session
  JSON, an array, `{"sessions": [...]}` or JSONL (sessions and `xai_run` log lines), up to 16 MiB, and reads exactly
  the chat field names above. Unknown fields are ignored (Go `encoding/json`).

## 5. Target architecture

```
codegen-x/ui8kit (specs, generator, Stock, brands as DB records)
        |  XGenBot publishes (closed, trusted)
        v
registry.buildy.tw  (@ui8kit)
  <runtime>/index.json + items   variants/v0.json   props/v0.json   [bindings/v0.json]*  [collections]*  [search index]*  [theme-<skin>]*
        |                                   * = dependency, see section 7
        |  npx ui8kit@<pinned> init + add --all   (same command in every host)
        v
host snapshot (committed, lock sha256, --check offline)
  chat:  registry/svelte/                      WP: plugins/wpfasty-builder/registry/{latte,twig}/
        |
        v  build step (no network)
derived data: chat sources.json / search index        WP: catalog.json (cache), fields config, search map
        |
        v
shared agent layer (same tool JSON, same search, Ask / Build / Create)  ->  page-spec + page-patch
        |
        v
host adapter: chat = Svelte preview + ZIP           WP = Latte/Twig render, WP_Post/terms/menus -> Codex entry/term/menu
        |
        v
session standard (contract session.schema.json) -> localStorage buildy:v1:*  -> window.buildy.export() -> Lab
```

Rules of the target:

- The plugin never edits a file under the snapshot. A wrong file is a question to XGenBot (codegen-x), fixed by the
  generator, then re-snapshotted.
- WordPress is an adapter: `WP_Post` -> Codex `entry`, terms -> `term`/`taxonomy`, nav menus -> `menu`, using
  `@ui8kit/contract` schemas (`src/Codex/*` and `DataSources.php` already read posts as Codex entries; keep that,
  remove anything that duplicates published data).
- The host validates every value the model sends against published vocabularies and schemas; no hardcoded word
  lists, no regex intent guessing.

## 6. Refactor phases

Each phase ends green on the repo checks from `AGENTS.md` (`bun run test`, `composer cs`, `composer phpstan`,
`composer deptrac`, `bun run lint:design`, `bun run parity`, `npx ui8kit@3 check` where applicable, theme packs)
plus the phase acceptance. One phase = one or more commits to `main`; the plugin stays usable between phases.

### Phase 0. Baseline and derivation report (no behavior change)

- Add `tools/builder/derive-report.ts`: download (or read pinned copies of) `variants/v0.json`, `props/v0.json`,
  the Latte item index; compare with `sources/ui8kit/latte/catalog.json` field by field (table 4.3); print counts
  and a list of fields that are not derivable.
- Record current `render-block-check.php` totals for Latte and Twig.

Acceptance: report shows 93/93 blocks, 482/482 variants mapped, template 482/482, `set` 481/482 with hero/prompt
explained, and lists `data`/`bindings`/`current`/`collections` as the only non-derivable parts. Numbers go into
this file (append a "Baseline" section with date).

### Phase 1. CLI snapshot for Latte and Twig

- Add `tools/builder/registry-snapshot.ts` modelled on chat `scripts/registry-snapshot.mjs`:
  - `CLI = "ui8kit@3.4.1"` (one constant), `REGISTRY = "https://registry.buildy.tw"`.
  - network mode: for each of `latte`, `twig`: clean temp dir, `npx <CLI> init --yes --framework <t>`,
    `npx <CLI> add --all`, copy `ui8kit.config.json`, `ui8kit.lock.json`, `src/` to
    `plugins/wpfasty-builder/registry/<t>/`; download `variants/v0.json` and `props/v0.json`, verify sha256 against
    `manifest.json` and their own `digest`, write `registry/variants.v0.json`, `registry/props.v0.json` and
    `registry/snapshot.json` (`{cli, registry, manifestSourceCommit, index: {latte: sha256, twig: sha256},
    variants: {url, sha256, digest}, props: {url, sha256, digest}, items, files}`).
  - `--check` (offline, CI): every file under `registry/<t>` matches the lock (sha256, CRLF as LF, no extra or
    missing file), pinned JSON hashes match, `snapshot.json` fresh.
- Point `SourceLoader`, `BlockRenderer`, `Primitives` at the snapshot tree: blocks from `registry/<t>/src/blocks`,
  compositions from `src/compose`, primitives from `src/ui` + `src/runtime`, PHP runtime from `src/runtime` /
  `composer.json` autoload (replace `sources/ui8kit/<t>/php`).
- Delete `plugins/wpfasty-builder/primitives/{latte,twig}` and the brick copies in `sources/ui8kit/<t>/{blocks,
  compose,ui,icons,php}` once rendering reads the snapshot. Theme installs (`themes/wp-fasty/ui8kit`, twig twin):
  move to the same CLI pin in the same phase if the theme renders the same primitives; otherwise record them as
  a follow-up in section 9.
- Keep engines isolated (ADR-001): the Latte ZIP ships only `registry/latte`, the Twig ZIP only `registry/twig`
  (extend the existing CI greps).

Acceptance:
- `bun tools/builder/registry-snapshot.ts` then `--check`: 341/341 items per engine; `npx ui8kit@3.4.1 check` in
  `registry/latte` = 370 files OK, in `registry/twig` = 372 files OK (or the numbers of the then-current pin).
- `tools/builder/render-block-check.php` renders every installed variant in both engines, offline and `--live`;
  the 482 existing variants produce the same HTML as before except the 6 known primitive updates (diff listed in
  the commit message); the 19 food blocks render too (532 variants per engine) once Phase 2 lists them.
- `bun run parity` green; no file under `primitives/` remains; `grep -r "export-ui8kit" .github` empty.

### Phase 2. catalog.json becomes a derived cache

- Add `tools/builder/catalog-build.ts` (replaces `export-ui8kit.ts`, `catalog-v2.ts`, `catalog-records.ts`):
  inputs = `registry/<t>/` + `registry/variants.v0.json` + `registry/props.v0.json` + bindings + collections;
  output = `catalog.json` in the existing catalog@2 shape (so `Catalog.php` and the editor keep working), with a
  new top-level `derived: {cli, variants: digest, props: digest, bindings: digest, collections: digest}` and
  `source.commit` = `manifest.json` `source.commit`. Generated file header says DO NOT EDIT; CI runs
  `catalog-build.ts --check`.
- Bindings and collections: until published upstream (D2, D3) read them from one plugin-owned overlay file per kind,
  `plugins/wpfasty-builder/registry/overlay/bindings.json` and `collections.json`, keyed by registry item name and
  variant id, values in Codex paths (the existing `fields` already are Codex paths such as
  `metadata.value.value|number`). Generate the overlay once from the current catalog (Phase 0 data), then never
  hand-grow it: new bindings are a request to XGenBot. When D2/D3 land, delete the overlay and pin the published
  files like variants/props.
- Editor typed fields: switch `editor/src/fields/schema.ts` from TypeScript type strings to the JSON Schema of
  `props/v0.json` (enum -> select/radio, boolean -> toggle, number, string -> text/textarea, array of `$ref`
  record -> array field, `$defs.lucideIcon` -> icon picker over the enum, `{"$": ...}` data refs -> binding field).
  Keep the `coverage.test.ts` gate.
- PHP validation: `Layout::normalize` / `DraftGuard` validate block props against the same JSON Schema
  (a PHP 2020-12 validator such as `opis/json-schema`, pinned in composer; no custom validator). Unknown prop =
  error (schemas are closed).
- Add the 19 food blocks: they arrive with the snapshot; they appear in the editor once the derived catalog lists
  them (their bindings, if any, come from D2).

Acceptance: `catalog-build.ts --check` green; derived catalog equals the Phase 0 catalog for every overlapping
field (only documented diffs: hero/prompt `set`, registry titles); 112 Stock blocks / 532 variants listed;
`coverage.test.ts` green; a variant with an unknown prop is rejected in PHP and in the editor.

### Phase 3. Starters, previews, theme CSS

- **Starters as page-spec.** A starter becomes a page-spec document (`https://schema.buildy.tw/page-spec.schema.json`,
  blocks with `catalogBlock` + `variant`), the same format chat `propose_layout` produces. page-spec 1.1.0 carries
  copy keys, not full props or bindings, so a WP starter (props + `{archive:product}` bindings) does not fit yet:
  **blocked on D4**. Until then: add `tools/builder/starters-to-page-spec.ts` that converts each starter to
  page-spec (copy keys only) and a test that the conversion validates; keep `starters/*.json` as the runtime source.
- **Previews.** Previews of Stock assets are referenced by Stock asset id (`variants.items[*].variants[*].asset`) and
  pinned with a hash (`previews/manifest.json` gains `asset` and `sha256` per shot). Generated plugin shots remain
  allowed as long as each is keyed by asset id; no new keying scheme.
- **Theme CSS.** Target is a registry item `theme-<skin>` installed by the CLI (alexy-os/chaosbun#143, D5). Until
  then `sources/ui8kit/styles/*.css` stays, with sha256 recorded in `registry/snapshot.json`.

Acceptance: every starter converts to a valid page-spec (`ajv` 2020 against the live schema); previews manifest
lists an asset id for every card; styles hashes are checked by `--check`.

### Phase 4. Shared tools, shared search, Ask and Build

- **One tool file.** Both hosts load the same tool definitions from one JSON file (**proposal**:
  `agent-tools.v1.json` in `@ui8kit/contract`, served at `schema.buildy.tw`, D6). Until it is published, the plugin
  keeps a copy at `modules/builder-agent/worker/agent-tools.v1.json` with the exact content Grok Bot commits to
  chat, and a test that its sha256 equals the pinned value. Proposed content:

```json
{
  "version": 1,
  "modes": {
    "ask":    { "tools": ["search_catalog", "get_block_details", "get_skill"], "toolChoice": "auto" },
    "build":  { "tools": ["search_catalog", "get_block_details", "get_skill", "propose_layout", "edit_page"], "toolChoice": "auto" },
    "create": { "tools": ["search_catalog", "get_block_details", "get_skill", "propose_block"], "toolChoice": "auto" }
  },
  "tools": {
    "search_catalog":   { "input": { "q": "string<=200", "section": "facet", "job": "facet", "industry": "facet", "blocks": "facet[]", "brand": "facet", "page": "int>=1", "pageSize": "int<=10" } },
    "get_block_details": { "input": { "block": "registry item name", "variant": "variant id (optional)" },
                           "output": "props schema of the item, the variant set/props, events, slots, bindings" },
    "propose_layout":   { "input": "page-spec page (whole page)" },
    "edit_page":        { "input": "page-patch {baseRevision, ops[]} incl. set_variant" },
    "propose_block":    { "input": "see Phase 6" },
    "get_skill":        { "input": { "id": "skill id from the index" } }
  }
}
```

  (the real file uses full JSON Schema for every input; the shorthand above is only for this document.)
- **Modes are an explicit user choice** in the composer (Ask / Build / Create switch), never guessed from words.
  - Ask: only read tools are sent to the model; layout tools are not in the request, so the page cannot change.
    The model's text answer is shown as is, with block cards from catalog results. Example: "list 10 feature
    variants" -> the honest answer "there are 8, here they are" from `get_block_details`.
  - Build: layout tools offered with `toolChoice: auto`; a text-only answer (question, explanation) creates no
    proposal. Proposals still require explicit human Apply; nothing publishes.
  - A card in Ask may offer "Apply this variant", which starts a Build turn with one `set_variant` op.
- **WP input/output change:** `propose_layout` moves from the WP block list `{id,block,variant,props,bindings}` to
  page-spec + page-patch. The PHP adapter converts page-spec <-> WP layout (`Compiler`), in one place, with tests.
  Until D4 (props/bindings in page-spec) the adapter keeps WP-only props in a host-namespaced extension and the
  conversion is tested both ways.
- **One search.** Same algorithm and same index in both hosts: facet filters validated against the pinned
  vocabulary (unknown value -> `catalog_facet_unknown` with `suggested` and `nearest`), lexical `q` over the
  generated index, deterministic ranking, `pageSize` max 10, compact cards (id, title, variant, labels, never full
  schemas). The index is generated from published catalog data (D7); the engine is one module shared by both
  hosts (today chat `text-search.ts` and the vendored codegen-x facet-search differ; converge on one, decided
  with XGenBot when D7 is published). The host intersects results with installed blocks and the active engine.
- **Golden parity test** (both repos): the same 20 queries (e.g. `section=marketing`, `job=hero`,
  `q="feature grid"`, `q="dishes grill promotion new year"`, a typo, an unknown facet) return the same ordered
  item ids in chat and WP for the same pins.

Acceptance: Ask turn on a page never changes `_wpfb_*` drafts (test with an injected runner that tries to call
`propose_layout`: it is not offered); Build text-only answer creates no proposal; golden search parity file
passes in both repos; `builder-local-search.md` updated.

### Phase 5. Session standard, localStorage, export to Lab

- **Schema (proposal, D8):** `session.schema.json` in `@ui8kit/contract`, `format: "buildy-session"`,
  `version: 1`. It keeps every chat field name of section 4.5 (Lab already reads them) and adds:

```json
{
  "format": "buildy-session", "version": 1,
  "id": "s-<uuid>", "title": "...", "createdAt": "ISO", "updatedAt": "ISO",
  "host": { "kind": "chat|wp|desktop", "version": "plugin or app version", "scope": "local | wp-<blogId>-<userId>" },
  "mode": "ask|build|create",
  "selectedModelId": "...",
  "provenance": { "cli": "ui8kit@3.4.1", "registryCommit": "3a6d923...", "variants": "sha256:...", "props": "sha256:...",
                  "catalog": "sha256:...", "bindings": "sha256:...", "tools": "sha256:...", "pageSpec": "1.1.0" },
  "messages": [ { "id": "...", "role": "user|assistant", "text": "...", "createdAt": "ISO", "mode": "ask|build|create",
                  "toolCalls": [ { "name": "search_catalog", "args": {}, "result": "truncated <= 8 KB", "ok": true, "ms": 12 } ],
                  "proposal": { "...": "chat ChatProposalState" }, "run": { "...": "chat ChatRunRecord incl. usage" },
                  "review": { "rating": "up|down|null", "tags": [], "comment": "" } } ],
  "page": { "applied": null, "candidate": null, "lastProposed": null, "revision": 0, "revisions": [ { "revision": 1, "at": "ISO", "patch": {} } ] },
  "usage": { "...": "chat SessionUsage" }
}
```

- **localStorage layout (same in both hosts):**

| Key | Value |
|---|---|
| `buildy:v1:<scope>:index` | `[{id, title, updatedAt, mode, bytes}]`, newest first |
| `buildy:v1:<scope>:session:<id>` | one session document (above) |
| `buildy:v1:<scope>:prefs` | `{selectedSessionId, selectedModelId, mode, theme, layout}` |

  `<scope>` = `local` in chat; `wp-<blogId>-<userId>` in WP (replaces the user suffix of the current
  `wpfasty-builder:layout:<user>` / `:dark:<user>` keys; migrate them once). Limits: one session <= 512 KB
  (tool results truncated to 8 KB each, older revisions collapsed first), all `buildy:v1:*` <= 4 MB. On quota:
  refuse the write and show the error (chat does this today); never evict silently. Migration: a one-time,
  idempotent reader converts old keys (`buildytech-chat-sessions`, `buildytech-creator-pages-v1`,
  `wpfasty-builder:*`) and leaves the old keys untouched until the user confirms cleanup.
- **WP specifics:** the server stays the source of truth for drafts and agent turns (post meta, worker SQLite).
  The editor writes a mirror session document to localStorage after each turn (the same document the REST API can
  return), so the owner can export from the browser. No secrets, nonces or provider keys in the document (reuse
  the redaction rules of `ChatTelemetry.php`).
- **Export command (both hosts):** `window.buildy.export()` returns
  `{format: "buildy-session-export", version: 1, exportedAt, host, sessions: [...]}` and downloads
  `buildy-sessions-<host>-<yyyymmdd-hhmmss>.json`; `window.buildy.export({id})` exports one session. In devtools:
  `copy(JSON.stringify(window.buildy.export()))` copies the same JSON. The `sessions` wrapper is the form Lab
  `ParseExport` already accepts.
- **Lab side (D9):** Lab imports the file today and maps runs; reading `mode`, `toolCalls`, `review` and
  `provenance` needs a small importer change in `buildytech/lab-console`.

Acceptance: a session exported from WP and one from chat both validate against `session.schema.json`
(ajv 2020); `lab-console runs import <file>` imports both with no skipped messages; the WP export contains no
nonce, cookie or key (test with a fixture that injects them).

### Phase 6. Create (custom block code, validated, never auto-published)

- Tool `propose_block` (**proposal**): input `{name, target: "latte"|"twig"|"svelte", files: [{path, content}],
  propsSchema (JSON Schema 2020-12, closed), sampleProps}`.
- Host checks, all must pass before a preview is shown:
  1. `propsSchema` meta-validates; `sampleProps` validate against it; names do not collide with registry items.
  2. Code compiles in the sandbox: Latte `Sandbox` policy / Twig `SandboxExtension` (the remote-source sandbox
     already in `SourceLoader`), allowed tags/filters/functions only, includes only of snapshot `compose/` and
     `ui/`; no PHP, no network, no file access.
  3. Rendered HTML with `sampleProps` passes the UI8Kit rules: a `data-slot` root, ARIA rules from
     `docs/aria.md`, colours only from tokens, spacing on the 8+4 grid (ui8px), Lucide icons from
     `$defs.lucideIcon` only. Reuse `bun run lint:design` and the parity/a11y checks; add one runner script.
  4. Size limits (files <= 32 KB each, <= 8 files).
- Result: a draft custom block stored with the page (or site), labelled "custom", usable in drafts and previews,
  never published to the registry and never auto-applied. Promotion to the shared registry goes only through
  CodeGen-X (ADR-0009 brand contribution through XGenBot and the generator).

Acceptance: a fixture set of 10 model outputs (5 valid, 5 invalid: unknown prop, raw colour class, off-grid
spacing, missing data-slot, PHP tag) gives 5 accepted / 5 rejected with stable error codes.

### Phase 7. Remove the old pipeline

- Delete `tools/builder/export-ui8kit.ts`, `catalog-v2.ts`, `catalog-records.ts` and their CI steps.
- The separate `registry.buildy.tw/{latte,twig}/wpfasty/v<semver>/` format: stop publishing new versions; never
  overwrite or delete published versions (AGENTS.md). Remote sources read CLI registries instead (or are retired
  after the owner decides).
- Update `README.md` of the plugin, `builder-registry.md`, `builder-local-search.md`, `tooling.md`.

Acceptance: `rg "export-ui8kit|catalog-v2|primitives/(latte|twig)"` finds only CHANGELOG/history; CI green;
plugin ZIPs (Latte and Twig) install on Playground and render all starters.

## 7. Dependencies

| Id | What | Owner | State |
|---|---|---|---|
| D1 | Props schemas `props/v0.json` | XGenBot, alexy-os/chaosbun#138 | **done**, verified (section 3.1) |
| D2 | Data bindings per item/variant (`as`, `kind`, `record`, `fields` as Codex paths, `scope`, `current`) published next to variants, e.g. `bindings/v0.json` with sha256 + digest (**proposal**; the data comes from the same codegen-x specs `catalog-v2.ts` reads today) | XGenBot | not filed (owner asked to discuss WP further before new XGenBot issues) |
| D3 | Collections (group/template sets with pages) published from Stock | XGenBot | not filed |
| D4 | page-spec carries full props + bindings per block (additive 1.2.0) so starters and WP layouts are page-spec | ui8kit/contract (Grok Bot) | not started |
| D5 | Theme/skin as CLI item `theme-<skin>` | XGenBot, alexy-os/chaosbun#143 | open, P2 |
| D6 | `agent-tools.v1.json` (modes + tool schemas) in @ui8kit/contract | Grok Bot | not started; chat implements Ask/Build first |
| D7 | Published search index + vocabulary (Stock) and one engine for both hosts | XGenBot + Grok Bot | not filed |
| D8 | `session.schema.json` in @ui8kit/contract; chat migrates to `buildy:v1:*` | Grok Bot | not started |
| D9 | Lab importer reads `mode`, `toolCalls`, `review`, `provenance` | buildytech/lab-console | not filed |
| D10 | `ui.*` dictionary (en) for `{"$":"ui.<key>"}` values; until then WP keeps gettext | XGenBot | known gap |
| D11 | `meta.brand` on items and variants (ADR-0009) for the brand filter | XGenBot | not filed |

Order of phases versus dependencies: Phases 0, 1, 2 (with overlays), 4 (Ask/Build with the local tool copy) and
5 (with the proposed schema, local copy) can start now. Phase 3 starters wait for D4; Phase 6 can start after
Phase 4.

## 8. Out of scope

- Desktop (Wails3) host; any WordPress runtime in BuildY chat.
- Brand-specific kits, author namespaces, renaming `compose`.
- Changing codegen-x or the published registry from this repository.
- Nesting (slots) in the editor (see plugin README "Next step: nesting").
- Paid model calls in tests (use injected NON-LIVE runners as today).

## 9. Risks

- **Primitive drift** when moving to the snapshot (Button `pressed`/`expanded`, `Rt::optBoolStr`): HTML of some
  blocks changes. Mitigation: Phase 1 lists the HTML diff; parity check in both engines.
- **Theme vs plugin primitives**: three installs today (plugin, theme Latte, theme Twig). If the theme stays on an
  older pin, the same primitive renders differently in theme and blocks. Mitigation: one CLI pin constant for the
  theme and the plugin, or a documented exception.
- **Overlay becomes a second source**: bindings/collections overlay is generated once and frozen; CI fails if it
  grows without a linked upstream request.
- **hero is two organisms**: Stock `hero` = `hero-classic` + `hero-prompt`; derivation must union axes per Stock
  block and keep `organism` per variant.
- **localStorage quota** on large sessions: hard limits + truncation of tool results + explicit error.
- **Privacy**: sessions may contain site content; export is a manual owner action; redaction before write.
- **Contract churn**: D4/D6/D8 are proposals; freeze them in @ui8kit/contract before the WP side depends on
  more than the local copy.

## 10. Rules for this work

- Commit to `main`, no pull requests, no force-push, no `--no-verify`; the author identity is PHPIngineer's own.
- No Cyrillic in repository files except locale files (`languages/*.po`, dictionaries).
- Generated code only through the generator: never hand-edit the snapshot, `catalog.json` or any file marked
  DO NOT EDIT.
- UI8Kit rules (ui8kit/contract `.cursor/rules`, `docs/aria.md`, `docs/atomic-ui8px.md`): tokens only, 8+4
  spacing grid, Lucide icons, data-slot roots, static ARIA.
- No secrets, nonces or keys in issues, logs, sessions or exports.
- Every claim in `docs/agent` carries a date and a source (folder rule).
