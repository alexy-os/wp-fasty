=== Latte ===
Contributors: alexy-os
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.1.0
License: AGPLv3 or later
License URI: https://www.gnu.org/licenses/agpl-3.0.html
Tags: blog, custom-menu, featured-images, custom-logo, one-column, two-columns, translation-ready

Clean classic WordPress theme powered by Latte templates and UI8Kit primitives.

== Description ==

Latte is a lightweight classic theme for WordPress. Presentation uses Latte templates composed from UI8Kit Codegen primitives. WordPress data is prepared in PHP (`ContextFactory`) so templates stay free of queries.

Features:

* Classic and optional app-shell layouts
* Custom logo, primary and footer menus
* Sidebar and footer widget areas
* Search form in header and 404
* Archives for categories, tags, authors, dates
* Attachment template
* Starter content for Customizer
* Accessible skip link and mobile navigation sheet

Privacy: this theme does not collect personal data and loads no remote resources.

== Installation ==

1. Upload the theme zip via Appearance → Themes → Add New → Upload Theme, or unpack into `wp-content/themes/latte`.
2. Activate **Latte**.
3. Optional: Appearance → Customize → Starter Content, then publish.
4. Assign menus to Primary and Footer locations.
5. Set a custom logo under Site Identity.

When installing from the wp-fasty monorepo, see the project `docs/install.md`. Release zips bundle UI8Kit Latte primitives under `lib/ui8kit-latte`.

== Frequently Asked Questions ==

= Are comments supported? =

Yes. Threaded comments use the core comment form (`comment_form()`) and `wp_list_comments()`; whether they are open is controlled by WordPress discussion settings.

= Does this theme support block themes / FSE? =

No. Latte is a classic PHP/Latte theme. A minimal `theme.json` is included for editor color alignment only.

= WooCommerce? =

Not included in this version.

== Changelog ==

= 1.1.0 =
* WordPress.org completeness: attachment, tax/author templates, logo, search, widgets, footer menu
* `wp_body_open`, `language_attributes`, `post_class`, multipage posts
* Starter content, readme.txt, LICENSE
* Hierarchical menus and richer post meta

= 1.0.0 =
* Initial public parent theme

== Copyright ==

Latte WordPress Theme, Copyright 2024-2026 AlexY OS
Latte is distributed under the terms of the GNU AGPL v3 or later.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

== Resources ==

* Latte template engine — https://latte.nette.org/ — New BSD / GPL dual (see vendor)
* UI8Kit Latte primitives (bundled) — MIT — https://github.com/alexy-os/wp-fasty
* ui8kit.js (theme assets) — MIT — theme author
* Design tokens / CSS utilities — MIT — theme author
* screenshot.png — theme author, AGPLv3+
* Lucide icons (assets/icons) — ISC License — https://lucide.dev/license
* Tailwind CSS build output (assets/css/theme.css, theme.min.css) — MIT — https://tailwindcss.com
