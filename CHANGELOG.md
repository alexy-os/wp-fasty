# Changelog

## Unreleased

### Breaking (theme PHP: Post DTO)
- Filter `wpfasty_post_data` now receives the readonly `WPFasty\Core\Model\Post` DTO instead of `WP_Post`:
  `fn (array $data, Post $post, bool $singular): array`. Use `$post->id`, `$post->kind` (post type), `$post->slug`,
  `$post->title`… instead of `$post->ID`, `$post->post_type`, `$post->post_name`; call `get_post($post->id)` if a
  callback really needs the WP object. For archive lists the filter now runs after the loop (`wp_reset_postdata()`),
  so callbacks must not rely on the global `$post`. Module `context` callables (`types/*.php`) keep their `WP_Post`
  argument.
- `ContextFactory::text()` is deprecated: use `WPFasty\Adapter\WordPress\PlainText::from()`.
- `ContextFactory` takes all its dependencies through the constructor (no defaults): `new ContextFactory()` is gone;
  get it from the container (`data.context_factory`) or call the factory in `configs/context.php`.

### Added (theme)
- `<meta name="description">` out of the box (excerpt, term/author description, tagline, else site title; ≤ 160
  chars, escaped). Filter `wpfasty_meta_description` ('' removes it); skipped when Yoast, Rank Math, AIOSEO, SEOPress
  or The SEO Framework is active.
- CI: HTML snapshot parity of the parent theme against the previous commit (declare intended markup changes with
  `[html-change]` in the commit message) and live template contexts validated against
  `schema/theme-context.schema.json`.

### Fixed
- latte-folio: the contact `mailto:` link was double-encoded (`&amp;#100;…`).

### Changed (theme PHP)
- `ContextFactory` is a thin facade: data comes through Core ports (`PostRepositoryInterface`, menu, site info, labels,
  widget areas, archive state, comments) with WordPress adapters in `WPFasty\Adapter\WordPress`, and view-models are
  built by `WPFasty\Data\*\*ViewModelBuilder`. Context keys and HTML are unchanged. Deptrac is blocking.

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

### Changed
- Themes use the codegen-x kit-compose recipes (codegen-x/ui8kit `fb4652ce`, `packages/kit-compose/variants`) for alert,
  badge, button, card (+ parts), iconbadge, input, textarea, label, link, stack and title instead of the legacy xgen
  presets (`php tools/ui8kit3/presets.php` imports them at the builder source pin; `Kit` applies `compound`). Views pick
  recipe keys instead of hand-written classes: button-styled links are `kit('button')`, icon buttons `layout: icon`,
  titles `size`/`weight`, stacks `gap`, post-list cards `variant: compact`; badges render a `<span>`; checkout errors are
  `aria-invalid`; shop category chips carry `aria-current`. Plain `rounded` follows the theme `--radius` token.

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
