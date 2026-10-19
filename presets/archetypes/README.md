# Archetypes

An archetype package is the child-theme code family a site manifest (`schema/site.manifest.schema.json`, field
`archetype`) selects. `bun run theme:generate <manifest>` combines it with the manifest:

| From the manifest | From the archetype package |
|---|---|
| `style.css` header, `assets/css/tokens.css` (brand), `types/*.php` (postTypes), template variables (features, labels, theme meta) | `archetype.json` (summary, palette note, copyright year, required types, demo / Playground / landing refs), `templates/*.tpl` (functions.php, readme.txt; `{{var}}`, `{{#if}}`, `{{#unless}}`), `templates/style.body.css`, `views/` (copied verbatim) |

`languages/<slug>.pot` is produced by `wp i18n make-pot` on the generated tree.

`bun run theme:roundtrip` regenerates every `manifests/examples/*.json` whose archetype is packaged and requires the
result to equal `themes/<slug>` byte for byte (only the `.pot` `POT-Creation-Date` / `X-Generator` lines are normalized).

| Archetype | Package | Round-trip |
|---|---|---|
| press | `press/` | yes (CI) |
| folio | – | – |
| shop | – | – |
