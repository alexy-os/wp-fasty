=== {{theme.name}} ===
Contributors: alexy-os
Requires at least: {{requires.platform}}
Tested up to: {{testedUpTo}}
Requires PHP: {{requires.php}}
Stable tag: {{theme.version}}
License: AGPLv3 or later
License URI: https://www.gnu.org/licenses/agpl-3.0.html

Marketplace child theme of Latte for selling ready-made WordPress sites, themes and plugins.

== Description ==

{{theme.name}} turns Latte into a digital-goods marketplace: home with search and categories, catalog with filters and sorting, item pages with live preview, Regular/Extended licenses, changelog, compatibility, support and verified-buyer reviews, author pages.

The theme is presentation only. Cart (license tiers), checkout, orders, downloads (signed links after payment), checkout consents, cookie consent and reviews are ModulY modules (phpfasty/wpmodules: cart, orders, downloads, consent-log, cookie-consent, reviews) installed through the ModulY plugin; without them the theme shows a read-only catalog and "temporarily unavailable" on the cart and checkout pages.

Child theme of Latte (parent theme required). Product post type fields are declared in types/product.php (data via the ModulY types module or the companion plugin "WP FastY Types"). Colors come from design tokens (assets/css/tokens.css, light and dark), icons are Lucide, UI is built from UI8Kit Latte primitives. Russian translation included.

== Copyright ==

{{theme.name}} WordPress Theme, Copyright {{copyrightYear}} AlexY OS
{{theme.name}} is distributed under the terms of the GNU AGPL v3 or later.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

== Resources ==

* Lucide icons (via the parent theme) — ISC License — https://lucide.dev/license
* All other code and CSS — AGPLv3 or later — AlexY OS
* No images are bundled with this theme.

== Changelog ==

= {{theme.version}} =
* Initial release
