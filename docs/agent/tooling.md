# Tooling & verification commands

All commands from repo root. "CI" = run by [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml)
(since 2026-10-19): job `verify` (audit, site manifests, codegen verify, lint:design, CSS drift check, PHPUnit, strict PHPCS, zip) and
job `wordpress` (WP 7.1.3 + MariaDB: Theme Check 20260901 on the unpacked zip and the 4 child themes with
`wpfasty-types` active — any REQUIRED fails; N+1 guard for post / project / product archives).

| Command | What it checks / does | CI |
|---|---|---|
| `bun run ui:primitives` | runs ui8kit/codegen (`packages/ui8kit-codegen`) → Latte primitives in `.workspaces/ui8kit-latte` | yes |
| `bun run ui:install` | copies primitives into `themes/latte/views/ui` | yes |
| `bun run codegen:verify` | codegen typecheck + generation (also produces `generated/ui/**/*.html.twig`) | yes |
| `bun run parity` | `tools/parity/parity.php`: renders every fixture (`tests/fixtures/parity/<suite>/*.json`) with Latte and Twig, compares normalized HTML; also fails if a `.latte` view has no `.html.twig` twin; suite `builder` compiles every `tools/builder/landings/*.json` to Latte and Twig and compares the full pages; suite `blocks` renders every catalog block × variant with Latte (BlockRenderer) and Twig (`UI8Kit\\Twig\\Extension`) and checks every Latte block template has a Twig twin (DOM contract, `tools/parity/normalize-dom.php`) |
| `bun tools/matrix/engine-variants.ts` | engine matrix: writes the other-engine answers of every `manifests/answers/*.json` and a Twig twin manifest of every example site (`manifest-<slug>-twig.json`) to `dist/answers-matrix/`; CI packages, Theme-Checks and renders them all, each on its own engine's parent | yes | yes |
| `bun run theme:pack -- latte --engine twig` | Twig parent `dist/twig.zip` (slug/text domain `twig`; only Twig: twig/twig + UI8Kit Twig runtime vendored, no Latte file); `bun run theme:pack -- latte` → `dist/latte.zip` ships only Latte. `engine: twig` sites run on twig.zip, `latte` sites on latte.zip (ADR-001, K7); both wordpress.org-eligible, Theme-Checked in CI | – |
| `tools/qa/site-pair.sh fetch <wp> <url> <theme> <out> <path>[\|code]…` / `compare <a> <slug-a> <b> <slug-b>` | isolated engines on a running WP: `fetch` renders a site on its own parent (code, no notices, only its engine's classes loaded — mu-plugin `tools/qa/engine-marker.php`); `compare` checks parity (quality check) of a Latte site and its Twig twin, slugs/parent normalized. CI runs it for every packaged site incl. builder landings | yes (wordpress job) |
| `wp eval-file tools/qa/registry-import.php <id> <url>` | imports a builder registry like the admin Import (runtime = site engine, sha256, templates); CI mirror step: per engine the other parent is moved out of wp-content (no class of the other library left), every site renders with only its engine loaded, the own-runtime registry (served by the WP host) imports and renders (`tools/qa/remote-landing.json`), the other runtime and a tampered archive are refused, forcing the absent engine (`tools/qa/renderer-switch.php`) gives HTTP 500 "Install the <engine> parent theme" | yes (wordpress job) |
| `tools/builder/export-ui8kit.ts`, `bun tools/builder/pack-source.ts <latte\|twig> <out>` | builder sources: export copies both UI8Kit packages into `plugins/wpfasty-builder/sources/ui8kit/{latte,twig}`; pack-source writes one runtime registry (`catalog.json` + `blocks.zip`, docs/agent/builder-registry.md) | – |
| `bun run registry:build <out> [--version x.y.z]` / `bun run registry:verify <latte\|twig> <dir\|url>` | the two builder registries `<out>/<runtime>/wpfasty/{v<semver>,latest}/` (immutable versions, deterministic) and their verification against the plugin import rules (docs/agent/builder-registry.md) | yes (verify job) |
| `bun run theme:build -- latte` | builds views from `presets/themes/latte.json` (skips empty templates) | yes |
| `bun run manifest:validate [file] [--repo]` | `tools/manifest/validate.ts`: site manifest v0.2 — ajv schema, semantic rules (text domain, capability fields, archetype types, landing blocks/variants/sources), and parity with `themes/<slug>` (style.css, tokens, views, `types/*.php`); no file = all `manifests/examples/*.json` with parity | yes |
| `bun run theme:generate <manifest> [--out dir] [--no-pot]` | manifest + `presets/archetypes/<id>/` → child theme (default `dist/generated/<slug>`); validates the manifest first | – |
| `bun run theme:package <manifest> [--out dir]` | release set in `dist/<slug>/`: `latte.zip` (reused / `theme:pack`), `<slug>.zip`, `wpfasty-types.zip` (`output.typesPlugin=companion` and post types), `wpfasty-builder.zip` (`builder.enabled`), `demo.zip` (`output.includeDemo`), `blueprint.json` + `<slug>-playground.zip` (`output.playground`), `site.manifest.json` | yes (artifact `dist`) |
| `bun run theme:new-site [--answers f.json] [--out f.json] [--package]` | configurator: answers (interactive prompts without `--answers`) → validated manifest in `dist/manifests/<slug>.json`, optionally packaged; fixtures `manifests/answers/*.json` | yes |
| `bun run configure:test` | `buildManifest` self-test: reference answers rebuild `manifests/examples/*.json` exactly; contrast / clash / reserved-name / palette rejections | yes |
| `bun run configure:form` | regenerates the static form `configurator/index.html` (committed; CI fails on drift) | yes |
| `bun run mcp:server` / `bun run mcp:smoke` | MCP stdio configurator server (`tools/mcp/README.md`); smoke calls all five tools | yes (smoke) |
| `bun run theme:roundtrip` | regenerates every example with a packaged archetype; must equal `themes/<slug>` byte for byte (pot date lines normalized) | yes |
| `bun run lint:design` | `tools/lint-design.ts`: token-only colors, 8/4 spacing, no half steps/arbitrary px, no raw `children` | yes |
| `bun run css:build` | Tailwind v4 (`.workspaces/css-build`) → `themes/latte/assets/css/app.css` | yes |
| `bun run icons` | copies used Lucide SVGs (`tools/icons.ts`) | – |
| `bun run verify` | ui:primitives + theme:build + lint:design + css:build + test | – |
| `composer test` / `bun run test` | PHPUnit (`tests/`, bootstrap stubs WP functions) | yes |
| `composer phpcs` / `bun run phpcs` | WPCS via `phpcs.xml` (all themes) | yes |
| `wp plugin activate wpfasty-types` | companion plugin (post types/taxonomies/meta/metaboxes) needed by folio/shop | – |
| `bun run i18n:pot` | regenerates `themes/*/languages/*.pot` (needs wp-cli) | – |
| `composer audit` | known-vulnerability check of PHP deps | yes |
| `bash tools/playground-build.sh` | builds `playground/*.zip` (themes + demo seeders + images) | – |
| `wp eval-file tools/perf/queries.php` | SAVEQUERIES: DB query count on archive must not grow with N posts | – (needs WP+DB) |
| Theme Check | see [wporg-theme-review.md](wporg-theme-review.md#running-theme-check-locally-verified) | – (needs WP) |

## Previews
`playground/{press,folio,shop}.json` blueprints; zips are published base64 in the secret gist
`alexy-os/ce9f531eeb05bb8aee9dadcd3b0c1ecc`; links in `playground/urls.txt`. See `playground/README.md`.

## Theme Check
Latest results are appended here by whoever runs it (date, WP version, counts per theme).
Counting: `grep -c REQUIRED|WARNING|RECOMMENDED` on the CLI output.

| date (UTC+3) | WP | latte | latte-press | latte-folio | latte-shop |
|---|---|---|---|---|---|
| 2026-10-16 baseline (4895c4d) | 7.1.3 | R1 W4 Rec17 | R1 W5 Rec16 | R3 W5 Rec17 | R6 W5 Rec17 |
| 2026-10-16 after ADR-001 work | 7.1.3 | R0 W0 Rec10 | R0 W1 Rec9 | R0 W1 Rec10 | R0 W1 Rec10 |
| 2026-10-17 typed meta + money in cents | 7.1.3 | R0 W0 Rec10 | – | R0 W1 Rec10 | R0 W1 Rec10 |

Remaining WARNING in children: "more than one text-domain" (child domain + parent `latte` — allowed for a
framework/parent per the Required rules, still reported). Folio/shop pass only because post types moved to the
companion plugin `plugins/wpfasty-types` (ADR-001 §3a).
