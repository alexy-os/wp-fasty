# Changelog

## Unreleased

### Breaking (K7: isolated engines)
- Latte and Twig are isolated runtimes. A Twig site never loads or falls back to Latte, and vice versa:
  - Twig parent theme is now `twig.zip` (theme slug `twig`, Twig only); `latte.zip` is Latte only. `latte-twig.zip`
    is gone. Twig children use `Template: twig` and ship only Twig views.
  - No renderer fallback: a child asking for an engine its parent build lacks gets an admin notice instead of being
    rendered by the other engine.
  - `wpfasty-builder` ships per engine and renders blocks only from the registry of the site runtime. The old fallback
    (a remote source without `twig.zip` rendering its Latte templates on a Twig site) is removed: such blocks render
    nothing and are reported in wp-admin.
  - Remote block sources are per-runtime registries (`wpfasty-catalog@2`, `catalog.json` + `blocks.zip`), published at
    `https://registry.buildy.tw/<runtime>/wpfasty/`. Format-1 sources still import, using only the site engine's zip.
- The engine is chosen at site creation; there is no supported live switch between engines.
