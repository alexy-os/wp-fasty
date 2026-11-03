# Changelog

## Unreleased

### Breaking
- Module `context` callables in `types/*.php` now receive the Core `WPFasty\Core\Model\Post` DTO instead of a `WP_Post`: `fn (array $data, Post $post, PostType $type): array`. Use `$post->id`, `->kind`, `->slug` and `->author?->id`. Updated: latte-folio `types/project.php`, the `productViewModel` of latte-shop, and the presets of the folio, shop and market archetypes. Admin column `value` callables are unchanged (`int $postId`).

### Added
- `docs/theme-authoring.md`: how to build a Latte or Twig child theme against view/v1, covering the contract, the layout, the root chain, engine choice, the view rules and the checks. `latte-skin` now ships the Twig twin of its override, and the new parity suite `skin` (front page plus a multi-page single) checks it.

### CI
- chaosbun#120: the WordPress job serves with `php -S` and opcache **on** for PHP 8.5 (8.5.12 floor), with `revalidate_freq=0`. PHP 8.2 stays off. A new step fails the job on a server crash (139/134/136 or a segfault). On the box, PHP 8.5.12 with opcache gave identical HTML with opcache on and off: 84 pages, cold and warm, 658 scripts cached, no crash.

### Fixed
- chaosbun#119: multi-page entries (`<!--nextpage-->`) had no Pages nav, and the single view printed every page at once. `PostRepository` now splits the content the way `WP_Query::generate_postdata()` does, without touching the globals, and shows only the current page. It also builds the view/v1 `pages` primitive (`PostPages`: current, total, links). Latte and Twig render the nav from that data (`blocks/post-content`), with no raw HTML. `linkPages` (the wp_link_pages() HTML) is still filled for child templates that print it. [html-change]


- View schema source switched to npm: `@ui8kit/contract` is pinned exactly at `0.3.0` (devDependency). `tools/qa/view-schema-sync.sh` compares the committed `schema/theme-context.schema.json` with `node_modules/@ui8kit/contract/schemas/view.schema.json`.

- `schema/theme-context.schema.json` is now a byte copy of the @ui8kit/contract view profile view/v1 (`schemas/view.schema.json`, $id `https://schema.buildy.tw/view.schema.json`), pinned in `schema/VIEW_SCHEMA_SOURCE` ([fa81e13](https://github.com/ui8kit/contract/commit/fa81e13)). The CI step `tools/qa/view-schema-sync.sh` fails when the copy drifts. The validator registers the schema under its own $id. The context adds `kind` on every entry view and `profile: view/v1` in the shell. Templates are untouched and the HTML is unchanged.

### Security
- WordPress core 7.1.4+ recommended (README → Security): CVE-2026-119977, core RCE in 4.7–7.1.3
  (https://www.tenable.com/cve/CVE-2026-119977). CI runs on 7.1.4 and fails on an older core
  (`tools/qa/wp-min-version.sh`); `Requires at least` of the themes is unchanged.

### Quality
- PHPStan level max with no baseline; Infection (CI job `mutation`, MSI 97.9 %, gate 95) on Core + ViewModel builders;
  Rector (PHP 8.2 + dead code / code quality / early return, dry-run in CI; skipped rules listed in `rector.php`);
  `tools/qa/pot-check.sh` keeps every .pot file current in CI. New composer scripts: `infection`, `rector`, `rector:fix`.
- Contract tests: Post DTO fields vs the `@ui8kit/contract` entry schema; one Post DTO renders the single page to the
  same DOM through Latte and Twig.
- CI: `php -S` runs without opcache (PHP 8.2.35 segfaulted on every request, chaosbun#120).

### Breaking (template context HTML is TrustedHtml, chaosbun#118)
- The HTML fields of the template context are `WPFasty\Core\Html\TrustedHtml` objects, no longer strings:
  `page.content`, `page.linkPages`, `posts[].content`, `posts[].linkPages`, `comments`, `widgets.sidebar`,
  `widgets.footer` (list: `WPFasty\Core\Html\ContextHtml`). A `wpfasty_post_data` / `wpfasty_context` callback that
  treats them as strings must cast (`(string) $data['content']`) and give back `new TrustedHtml($html)` for markup it
  changes; a plain string put there is now **escaped**.
- LatteRenderer / TwigRenderer print TrustedHtml raw (Latte `Html`, Twig `Markup`; an empty one is `''`, so `{if}`
  tests are unchanged) and escape everything else; the views no longer use `|noescape` / `|raw` on those fields.
  Child-theme views that print them with `|noescape` / `|raw` keep working. Output is unchanged (60 local pages, normalized like the CI snapshots).
- `TrustedHtml` is `JsonSerializable` (the plain string); `ContextHtml::trust()` marks the fields of a frozen JSON
  context (parity fixtures, snapshots).

### Breaking (theme PHP: Post DTO)
- Filter `wpfasty_post_data` now receives the readonly `WPFasty\Core\Model\Post` DTO instead of `WP_Post`:
  `fn (array $data, Post $post, bool $singular): array`. Use `$post->id`, `$post->kind` (post type), `$post->slug`,
  `$post->title`… instead of `$post->ID`, `$post->post_type`, `$post->post_name`; call `get_post($post->id)` if a
  callback really needs the WP object. For archive lists the filter now runs after the loop (`wp_reset_postdata()`),
  so callbacks must not rely on the global `$post`. Module `context` callables (`types/*.php`) keep their `WP_Post`
  argument.
- `ContextFactory::text()` is removed: use `WPFasty\Adapter\WordPress\PlainText::from()`.
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
