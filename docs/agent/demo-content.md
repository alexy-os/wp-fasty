# Demo content — completeness checklist

Demo seeders: `tools/demo/{common,press,folio,shop}.php` (run with `wp eval-file`; idempotent by slug).
They are **dev/preview tooling**, never shipped in the theme zip (wp.org forbids demo import in themes — see
[wporg-theme-review.md](wporg-theme-review.md)).

## A. Theme Unit Test coverage (for every theme, incl. parent)
Import `themeunittestdata.wordpress.xml` (https://github.com/WordPress/theme-test-data) and check:
- [ ] long title / no title / title with markup, sticky post (`.sticky` styled), password-protected post, private
- [ ] paginated post (`<!--nextpage-->` → `wp_link_pages()`), "more" tag, excerpt vs content
- [ ] all core blocks + classic markup: alignleft/right/center, wide/full, captions (`.wp-caption`, `.wp-caption-text`), gallery (`.gallery-caption`)
- [ ] post formats (if declared), featured image missing / huge / tiny
- [ ] comments: threaded (depth ≥ 3), paged, pingbacks, author comment (`.bypostauthor`), long comment, comments closed
- [ ] categories/tags with long names and many items; author archive; date archives; search with/without results; 404
- [ ] nested nav menu (≥ 3 levels), page hierarchy, widgets in every sidebar
- [ ] RTL (`rtl.css` or logical properties) — **UNVERIFIED**, no RTL test run yet

## B. Per-theme demo (current seeders)
| Theme | Content the seeder creates | Fields that must be filled for every item |
|---|---|---|
| press | posts with categories/tags, featured images, comments, pages About/Contact, menu | title, excerpt, content, thumbnail, ≥1 category |
| folio | `project` items + `project_type` terms, front page, Work page | client, year, role, url (`_folio_*`), thumbnail, type |
| shop | `product` items + `product_cat`, Shop/Cart/Checkout pages, one sample order | price/sale in integer cents + store currency (`wpfasty_currency`, ISO 4217, default USD), SKU, stock (unset = unlimited), thumbnail, category |

When a post type module (`types/<name>.php`, ADR-001 §3) declares `fields`, the seeder must fill **every** field
(test: `tools/demo/*` + `fields` list). — planned, not yet automated.

## C. Images & licensing
- Current preview images: `https://picsum.photos/seed/<seed>/…` (bundled into `wpfasty-demo/images` by
  `tools/playground-build.sh`). Picsum serves photos **from Unsplash** (picsum.photos footer, verified 2026-10-16).
  The Unsplash License is **not GPL-compatible** → acceptable for previews, **not** for anything shipped in a theme zip.
- Rule: theme zips ship **no photos**; screenshot.png and any bundled image must be own work or CC0, listed in
  `readme.txt` `== Resources ==` with source + license.
- TODO: replace picsum in demos with CC0 images (e.g. own generated SVG/PNG placeholders), record each in a
  `tools/demo/images/LICENSES.md`.
- Child themes `latte-press`, `latte-folio`, `latte-shop` currently have **no screenshot.png** (verified: only
  `latte` and `latte-skin` have one, both 1200×900).

## D. Money and typed fields
- `money` fields are stored as **integer minor units** (cents): `_shop_price = 4800` is $48.00. The admin metabox shows and
  accepts a decimal (48.00) and converts (`Field::fromInput` / `toInput`). Orders also store cents and `_order_currency`.
- Currency: option `wpfasty_currency` (ISO 4217, default `USD`), registered by `plugins/wpfasty-types` (Settings → General,
  REST `/wp/v2/settings`). `LatteShop\Money::format()` uses it (symbol filter `latte_shop_currency_symbol`).
- Field meta is registered with real types (`integer`/`number`/`boolean`/`string`) and `show_in_rest`; a field that is not set
  comes back as `null` in REST (not 0), so stock "unlimited" differs from stock 0. `shop_order` stays private (no REST).
