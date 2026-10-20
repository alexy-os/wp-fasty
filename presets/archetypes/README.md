# Archetypes

An archetype package is the child-theme code family a site manifest (`schema/site.manifest.schema.json`, field
`archetype`) selects. `bun run theme:generate <manifest>` combines it with the manifest:

| From the manifest | From the archetype package |
|---|---|
| `style.css` header, `assets/css/tokens.css` (brand), `types/*.php` (postTypes), template variables (features, labels, theme meta) | `archetype.json` (summary, palette note, copyright year, required types, demo / Playground / landing refs), `templates/*.tpl` (functions.php, readme.txt; `{{var}}`, `{{#if}}`, `{{#unless}}`), `templates/style.body.css`, `views/` and `inc/` (copied verbatim), `types/<name>.extra.php` (callables — view models, admin column values — appended to the generated array; `{{theme.textDomain}}` substituted) |

`languages/<slug>.pot` is produced by `wp i18n make-pot` on the generated tree (bug-report header pinned to the slug).

`types/*.php` in `themes/latte-folio` and `themes/latte-shop` are generator output — edit the manifest or the
`.extra.php` fragment, then regenerate. `adminColumns` in the manifest describe the columns; their value callables live
in the fragment.

`bun run theme:roundtrip` regenerates every `manifests/examples/*.json` whose archetype is packaged and requires the
result to equal `themes/<slug>` byte for byte (only the `.pot` `POT-Creation-Date` / `X-Generator` lines are normalized).

| Archetype | Package | Round-trip |
|---|---|---|
| press | `press/` | yes (CI) |
| folio | `folio/` | yes (CI) |
| shop | `shop/` | yes (CI) |

## Packaging (`bun run theme:package <manifest> [--out dist/<slug>/]`)

| File | When | Content |
|---|---|---|
| `latte.zip` | always | parent from `theme:pack` (`dist/latte.zip`, built if missing) |
| `<slug>.zip` | always | the generated child theme (same tree `theme:roundtrip` checks) |
| `wpfasty-types.zip` | `output.typesPlugin = companion` and post types | companion plugin; reads the active theme's `types/` (no generated config) |
| `wpfasty-builder.zip` | `builder.enabled` | builder plugin without `editor/` sources |
| `demo.zip` | `output.includeDemo` | `wpfasty-demo/`: `common.php`, the archetype seeder (`archetype.json` → `demo`), `images/` taken from the committed `playground/demo.zip` (missing seeds are fetched by `common.php` at seeding time), `landings/` from the manifest |
| `blueprint.json`, `<slug>-playground.zip` | `output.playground` | Playground blueprint (install parent + child + plugins, activate, permalinks, unzip demo, run seeder, seed landings in manifest order); every resource is `bundled`, so the bundle zip (one top-level folder) is self-contained — no gist or other external URLs |
| `site.manifest.json` | always | copy of the input manifest |
