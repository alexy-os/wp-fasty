=== WP FastY Types ===
Contributors: godudy
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Registers the declarative post-type modules (types/<name>.php) of WP FastY themes.

== Description ==

Themes built on the Latte parent (WP FastY) describe content types in `types/<name>.php`.
The theme only handles presentation (views, routing, archive queries). This plugin does the
data part that wordpress.org reserves for plugins: register_post_type, register_taxonomy,
register_post_meta (typed + sanitized), the admin metabox with nonce/capability checks and
admin list columns. Requires an active Latte-based theme.
