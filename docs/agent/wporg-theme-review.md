# wordpress.org theme review — verified facts

Verified 2026-10-16 (UTC+3).

## Sources
- Required rules: https://make.wordpress.org/themes/handbook/review/required/ (page "Last modified: June 9, 2026")
- Theme Check checks: https://make.wordpress.org/themes/handbook/review/theme-check-plugin/ (page says "may be out of date")
- Theme Check plugin: https://wordpress.org/plugins/theme-check/ — zip used: https://downloads.wordpress.org/plugin/theme-check.latest-stable.zip
- Theme Unit Test data: https://github.com/WordPress/theme-test-data (`themeunittestdata.wordpress.xml`)
- Accessibility-ready requirements: https://make.wordpress.org/themes/handbook/review/accessibility/ — **UNVERIFIED** (not opened in this pass)

## Required (summary of the Required page)
- **License:** GPL-compatible for *everything* in the zip (code, images, fonts); "GPLv2 or later" recommended.
  Copyright for the theme + license/source for every bundled resource, listed in one file (readme.txt `== Resources ==`).
- **Privacy:** no tracking unless opt-in.
- **Accessibility:** skip link (first focusable, visible on focus, moves focus to main); visible keyboard focus on menus, form fields, buttons, links; everything keyboard-reachable.
- **Code:** no PHP/JS errors/notices; sanitize input, escape output; no deprecated functions;
  **unique prefix ≥ 4 letters** for functions, options, globals, constants, post meta, script/style handles, image sizes.
- **Plugin territory — NOT allowed in themes:** custom post types, custom blocks, roles, shortcodes, non-design
  metaboxes, anything not design/presentation. ⇒ `latte-folio` and `latte-shop` **cannot** go to wp.org as-is (see ADR-001 §6).
- **No zip files or plugins inside the theme**, no remote resources/CDN without consent (Google Fonts excepted),
  no hidden files, `.sh`, `.xml` (except `wpml-config.xml`, `loco.xml`, `phpcs.xml`), `.sql`, `favicon.ico`.
- **i18n:** all strings translatable with gettext; text domain = theme slug, declared in style.css;
  a framework may add at most one more domain.
- **style.css headers required:** Theme Name, Author, Description, Version, Requires at least, Tested up to,
  Requires PHP, License, License URI, Text Domain. Theme URI optional (must not be wordpress.org).
- **readme.txt** required (format with `== Resources ==` section).
- **Minified files** must ship with their unminified source.
- **Screenshot:** ≤ 1200×900, ratio 4:3, not an advertisement.
- **Classic theme must include:** DOCTYPE + `language_attributes()`, `wp_head()`, `body_class()`, `wp_body_open()`,
  `wp_footer()`, `post_class()`, `wp_link_pages()`, `add_theme_support('title-tag')`, `add_theme_support('automatic-feed-links')`;
  templates via `get_template_part()`/`locate_template()`; header.php/footer.php/sidebar.php/searchform.php, if used,
  through their functions; `edit_theme_options` capability for options.
- **Admin:** notices via `admin_notices`, dismissible; no redirect on activation; **no demo imports in admin/onboarding**;
  one prefixed option (array) max.

## Theme Check checks (from the checks page)
Bad things: `base64_decode/encode`, `eval`, `str_rot13`, system calls. Plugin territory: `register_post_type`,
`register_taxonomy` (REQUIRED), shortcodes (warning). Styles: `.sticky .bypostauthor .alignleft .alignright .aligncenter
.wp-caption .wp-caption-text .gallery-caption`. Text domain must match slug. Screenshot size. Sidebars registered in a
function hooked to `widgets_init`. Also: `comments_template`, `wp_list_comments`, `comment_form`, `comment-reply`, `$content_width`.

## Running Theme Check locally (verified)
```bash
curl -sSLo /tmp/tc.zip https://downloads.wordpress.org/plugin/theme-check.latest-stable.zip
wp plugin install /tmp/tc.zip --activate     # `wp plugin install theme-check` failed in this sandbox (no wp.org API)
PAGER=cat wp theme-check run <theme-slug>    # PAGER needed: `less` not installed on the box
```
Note: Theme Check also scans `~cache/` (compiled Latte) — delete `themes/latte/~cache` before running.

### Baseline results 2026-10-16 (before ADR-001 refactor), local WP 7.1.3, `/workspace/sites/std`
| theme | REQUIRED | WARNING | RECOMMENDED |
|---|---|---|---|
| latte | 1 | 4 | 17 |
| latte-press | 1 | 5 | 16 |
| latte-folio | 3 | 5 | 17 |
| latte-shop | 6 | 5 | 17 |

REQUIRED items: sidebars not *visibly* registered on `widgets_init` (all 4 — registration goes through `AbstractHooks::addAction`,
which the static check cannot follow); `register_post_type`/`register_taxonomy` (folio, shop); `base64_encode/decode` in
`latte-shop/inc/cart.php`. WARNINGs: no copyright notice, two text domains (child + `latte`), hard-coded `role="search"` form
(compiled Latte cache) instead of `get_search_form()`. Results after the refactor: see [tooling.md](tooling.md#theme-check).

## Theme Unit Test data (verified counts in `themeunittestdata.wordpress.xml`, fetched 2026-10-16)
58 posts, 21 pages, 37 attachments, 70 nav menu items, 33 comments, 68 category refs, 110 tag refs.
Import: `wp plugin install wordpress-importer --activate && wp import themeunittestdata.wordpress.xml --authors=create`
— **UNVERIFIED** in this sandbox (importer download not tried).
