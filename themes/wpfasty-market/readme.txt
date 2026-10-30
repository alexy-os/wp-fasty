=== WP FastY Market ===
Contributors: alexy-os
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 2.0.0
License: AGPLv3 or later
License URI: https://www.gnu.org/licenses/agpl-3.0.html

Brand child theme for a WP FastY digital-goods marketplace: tokens, logo and footer on top of Latte Shop.

== Description ==

WP FastY Market only brands the store: design tokens (assets/css/tokens.css, light and dark), logo, and a footer with catalog links, the "legal" documents menu and seller details (option `wpfasty_company`). It has no page templates of its own.

Catalog, product, cart, checkout and author pages are the common Latte Shop templates (header `Extends: latte-shop`; WordPress allows only two theme generations, so Latte loads Latte Shop as a base layer between this theme and Latte). Digital parts are added by ModulY modules through Latte Shop extension points (slots `product.purchase`, `product.after`, `checkout.consents`, `checkout.order`, `footer`): licenses, downloads, reviews, consent-log, cookie-consent. See packs/digital-shop.json in the WP FastY repository.

Requires the Latte parent theme and the Latte Shop theme installed. Russian translation included.

== Copyright ==

WP FastY Market WordPress Theme, Copyright 2026 AlexY OS
WP FastY Market is distributed under the terms of the GNU AGPL v3 or later.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

== Resources ==

* Lucide icons (via the parent theme) — ISC License — https://lucide.dev/license
* All other code and CSS — AGPLv3 or later — AlexY OS
* No images are bundled with this theme.

== Changelog ==

= 2.0.0 =
* Rebuilt as a brand-only child on Latte Shop templates and ModulY slots (replaces wp-fasty-market 1.x).
