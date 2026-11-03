# Plan — view profile `view/v1` (chaosbun#121)

Owner mandate 2026-11-03: stabilize Codex v0.4 + `@ui8kit/contract` so views of **any** backend that speaks Codex
are served, for any record kind and arbitrary fields. Rule: a view context is a **projection** of Codex records
plus a closed set of render-time primitives; no new entities, no copied record definitions.

| # | Step | Where | Status |
|---|---|---|---|
| 1 | `schema/theme-context.schema.json` rewritten as view/v1: shell (Settings, Dictionary, request, head, breadcrumbs, slots), menuTree, pagination, entry view of any kind + `fields` typed by Manifest Field types, archives (term / taxonomy / kind / author / date), search, 404, comments, image, link, trustedHtml; `$ref`s to `https://schema.buildy.tw/*`; descriptions enough for an SSG author | wp-fasty | ✅ f442eb2 |
| 2 | `schema/examples/*.json`, one per page type (19), validated in CI (`ContextSchemaTest`) | wp-fasty | ✅ f442eb2 |
| 3 | Live contexts stay valid (CI snapshot step, parity fixtures) | wp-fasty CI | ✅ CI green (1dcbf4b) |
| 4 | This plan | wp-fasty | ✅ |
| 5 | Single source in the contract: `src/view-v1.ts` (TS types, `VIEW_PROFILE_V1`, page types, `FIELD_TYPE_VIEW_V1` = Manifest field type → view value) → `scripts/schemas-view-v1.ts` → `bun run gen` → `schemas/view.schema.json` + `public/` (`https://schema.buildy.tw/view.schema.json`) | ui8kit/contract | ✅ [fa81e13](https://github.com/ui8kit/contract/commit/fa81e13) |
| 6 | Contract fixtures `fixtures/view/*.json` (the examples) + `test/view-v1.test.ts` (ajv over all schemas, negative cases, mapping ↔ schema agreement); `bun run check` green; CHANGELOG (0.3.0 unreleased, additive), README section; no npm publish | ui8kit/contract | ✅ [fa81e13](https://github.com/ui8kit/contract/commit/fa81e13) |
| 7 | wp-fasty consumes the contract schema: `schema/theme-context.schema.json` becomes a byte copy of the contract's `schemas/view.schema.json` at a pinned commit (`schema/VIEW_SCHEMA_SOURCE`), CI `tools/qa/view-schema-sync.sh` fails on drift; raw URL of the copy stays for SSG authors | wp-fasty | ✅ (this commit) |
| 8 | PHP view-models aligned: `kind` on every entry view, `profile: view/v1` in the shell; templates untouched, HTML unchanged | wp-fasty | ✅ (this commit) |
| 9 | fastygo/codex: no change — its AGENTS.md rule 1 forbids UI / views in the module; the view profile lives in the contract that implements Codex for TS (documented here and in #121) | — | ✅ decided |
| 10 | DoD + CHANGELOG; chaosbun#121 updated and closed; temp clones deleted | wp-fasty, chaosbun | ✅ |

Later (not in v1.0, additive in v1.x): structured `commentsView` and `head` emitted by wp-fasty (schema has them, the
theme still renders comments / head through WordPress), `request.pageType` from the theme, PHP DTO generation from
the schema.
