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
