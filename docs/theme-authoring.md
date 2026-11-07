# Theme authoring: Latte and Twig themes against view/v1

A WP FastY theme is a **child theme of `themes/wp-fasty`**. The parent boots the engine and builds the template context. The child supplies views (Latte, Twig or both), CSS tokens and optional context filters. It ships no PHP classes and no `vendor/`. The working example is `themes/latte-skin`, which overrides one block in both engines. It is covered by the parity suite `skin`.

## 1. The contract: view profile view/v1

Every view receives one context. The context follows the view profile **view/v1**: `schema/theme-context.schema.json`, `$id` `https://schema.buildy.tw/view.schema.json`. It is a byte copy of `@ui8kit/contract` `schemas/view.schema.json`.

- **Shell:** `site`, `menu` (`menuTree` with `current` / `ancestor`), `labels`, `search`, `widgets`, `request` and `profile: "view/v1"`. It is present on every page.
- **Page types:**
  - `pageContext` (front, single, page, attachment, any kind): the entry in `page`.
  - `archiveContext` (home, category, tag, taxonomy, kind, author, date, search): `posts[]`, `archive`, `pagination`.
  - `errorContext` (404).
- **Entry:** core record fields (`id`, `kind`, `title`, `slug`, `url`, `excerpt`, `content`, `date`, `author`, terms). Arbitrary kind-specific data lives in `fields` (Manifest field types) and `terms`. A multi-page entry has `pages` (`current`, `total`, `links[]` with `current`).
- **Primitives:** `link`, `image`, `trustedHtml`, `pagination`, `menuTree`, `head`, `shell`.
- **Examples:** one context per page type in `schema/examples/*.json`. Validate any context with `php tools/qa/context-schema.php <file-or-dir>`.

Minor versions of view/v1 only add things, so a theme written against view/v1 keeps working.

## 2. Layout of a theme

```
themes/my-theme/
├── style.css              # Template: latte   (WordPress child theme header)
├── functions.php          # optional: add_filter('wpfasty_context', …), wpfasty_shell, wpfasty_renderer
├── assets/css/tokens.css  # optional semantic tokens, both :root and .dark
├── assets/css/fonts.css   # optional self-hosted @font-face definitions (family, style, weight, URLs)
├── assets/fonts/*         # licensed font files referenced relative to fonts.css
├── types/*.php            # optional post-type modules (fields, views, `context` callable)
└── views/
    ├── blocks/<name>.latte + <name>.html.twig    # override or add blocks
    ├── pages/<view>.latte  + <view>.html.twig    # page views: front-page, single, page, archive, search, 404, …
    └── layout/default.latte + default.html.twig  # optional: the document shell
```

The renderer looks up a view in this order: **child `views/` → parent `views/` → engine primitives** (`title/Title`, `text/Text`, `link/Link`, `box/Box`, `group/Group`, `block/Block`, `image/Image`, `badge/Badge`, …). A file in the child wins. Anything you do not override comes from the parent.

## 3. Picking the engine

The parent default is Latte. Select Twig with `add_filter('wpfasty_renderer', fn () => 'twig');` or with the site manifest `"engine": "twig"`. An engine that the build lacks throws `EngineUnavailableException`; it never silently falls back to the other engine. To support both engines, ship every `.latte` view together with its `.html.twig` twin. The parity gate fails on a Latte view without a twin.

## 4. Rules for views

- **Escaping:** text is plain and auto-escaped. HTML fields (`page.content`, `comments`, `widgets.*`) are `TrustedHtml`, already marked safe, and print as `{$page['content']}` / `{{ page.content }}`. Never use `|noescape` or `|raw` on context data; `tools/security/raw-sinks.php` gates this.
- **Composition:** build markup from the primitives (`{include 'link/Link.latte', …}` / `{% include 'link/Link.html.twig' with {…} %}`) and kit recipes (`kit('button', {'variant': 'secondary'})`), not raw tags with ad-hoc classes.
- **Missing data:** guard optional data (`{if !empty($page['pages']['links'])}` / `{% if page.pages.links ?? false %}`), because view/v1 keeps most entry fields optional.
- **Kind-specific display:** read `page.fields.<name>` and `page.terms.<taxonomy>`. Shape them in the module `context` callable of `types/<kind>.php`: `fn (array $data, WPFasty\Core\Model\Post $post, WPFasty\Types\PostType $type): array`. It receives the Core DTO, not a `WP_Post`.

## 5. Checking a theme

1. **Parity:** add the theme's root chain to `$suites` in `tools/parity/parity.php` (for example `'skin' => ['themes/latte-skin/views', 'themes/wp-fasty/views']`). Add fixtures in `tests/fixtures/parity/<suite>/*.json` (`{view, context, page?}`). Copy them from `tests/fixtures/parity/latte` or dump a real context with `tools/parity/dump-context.php`. Then run `bun run parity`: Latte and Twig must render identical normalized HTML.
2. **Contexts:** run `php tools/qa/context-schema.php schema/examples` (and your own dumps).
3. **Raw output:** `php tools/security/raw-sinks.php`.
4. **On WordPress:** activate the child and switch engines with `wp option update wpfasty_qa_renderer twig|latte` (QA mu-plugin `tools/qa/renderer-switch.php`), then compare pages.

## 6. Generated themes

`bun run theme:generate` builds a full child theme (Latte or Twig twin) from a site manifest (`manifests/examples/*.json`, schema `schema/site.manifest.schema.json`). `latte-press`, `latte-folio`, `latte-shop` and their `-twig` twins in CI are produced this way. Start from a manifest when the theme needs post-type modules, menus and demo content. Start from `latte-skin` when the theme only restyles views.

## 7. One appearance source for live pages and Builder

[ADR-004](agent/adr-004-theme-appearance-inheritance.md) is the current contract.
Builder's real page and nonce-protected draft iframe consume the **active theme**, not a copied plugin skin.
The enqueue dependency chain is parent theme CSS → parent fonts/tokens → optional `Extends` base
fonts/tokens → child fonts/tokens → child `style.css`. Existing theme `wp_enqueue_scripts` font enqueues
continue to work. Optional `assets/css/fonts.css` loads automatically per layer; declare actual
`@font-face` family/style/weight and relative file URLs there. Variable-font ranges belong in those definitions.
Provide and license the files yourself; a token naming `Inter` does not mean Inter is bundled/downloaded.
Missing font faces use the CSS font stack. No font provider, remote fetch, Agent, ModulY or Node is required.

Use semantic `--font-body` / `--font-display` and color tokens in `tokens.css`; define light in `:root`
and dark in `.dark`. The parent compiled CSS maps these to `font-sans`, `font-heading` and semantic colors.
Do not enqueue these styles through `admin_enqueue_scripts`: admin chrome keeps WordPress palette/fonts.
Theme releases must bump their WordPress Version to invalidate their stylesheet cache URLs.

Explicit site overrides use WordPress **Additional CSS**, stored in theme-scoped `custom_css` posts and
printed by core after enqueued styles. They are outside updateable theme/plugin files, retained on updates,
and scoped to the exact stylesheet slug: switching to a different child does not import old overrides;
returning restores them. This is not a new unrestricted CSS editor or permission grant in Builder.
Keep existing WordPress edit-CSS capabilities, sanitization and author responsibility. Same-slug breaking
skin changes still require reviewing existing overrides; there is no invented semantic compatibility checker.

Builder sends `wpfb_scheme=light|dark` with its existing authenticated preview URL. The PHP theme renders
that root class **before CSS**, without relying on scripts inside the sandbox or parent DOM patching.
`wpfasty_color_scheme` is a narrowly validated, request-local theme filter (`null` = normal live behavior).
Live pages ignore preview-only mode without the matching page nonce/edit permission; browser color preference
behavior remains unchanged. Preview mode writes no site settings or live localStorage preference.
A custom document shell must retain WordPress `language_attributes()` / `wp_head()` to participate.

Token import/export and a versioned SkinDefinition/catalog engine remain future work, not implemented.
NON-LIVE regression: `wp eval 'require "tools/qa/theme-appearance-contract.php";'` on an isolated WP.
It creates/removes its own fixture themes/pages, uses original synthetic test fonts and restores the active theme.
Never run this fixture on a customer/production installation.
