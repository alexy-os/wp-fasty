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

### Added
- Site contacts and opening hours (Codex SettingsV04 contact block, ui8kit/contract `8a09c8f`): wpfasty-types options
  `wpfasty_contact_phone|email|address|links`, `wpfasty_hours`, `wpfasty_hours_exceptions`, `wpfasty_map`, a
  Settings → Contact & hours page (sanitized, translatable), served in `/wpfasty/v1/codex/settings` as `contact`,
  `hours`, `hours_exceptions`, `map`. Builder tokens `{site:phone|email|address|hours}`, `{tel}`, `{mailto}`,
  `{whatsapp}`, `{telegram}`, `{map}` (pin or address search, provider osm|google|yandex|apple) use them; `{open-now}`
  is filled on the client by one small inline script (cache-safe; nothing without hours). Demo sites have test contacts.
- Builder registries v1.0.0: `https://registry.buildy.tw/latte/wpfasty/v1.0.0/` and
  `https://registry.buildy.tw/twig/wpfasty/v1.0.0/` (+ `latest/`); `bun run registry:build` / `registry:verify`.
- QA: `tools/qa/site-pair.sh`, `engine-marker.php`, `registry-import.php` (CI: each site on its own parent with only its
  engine loaded, mirror checks with the other engine physically absent, negative import and engine tests).

### Removed
- `tools/qa/live-render.sh`, `no-latte.sh`, `no-latte.php` (one site rendered by both engines no longer exists).
