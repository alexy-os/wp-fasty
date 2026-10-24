# Live previews (WordPress Playground)

The built zips are mirrored
(base64) in a secret gist: https://gist.github.com/alexy-os/ce9f531eeb05bb8aee9dadcd3b0c1ecc
and the blueprints (`press.json`, `folio.json`, `shop.json`, `shop-twig.json`) are passed inline in the Playground URL fragment.
Everything runs in the visitor's browser — no server.

- Latte Press (classic blog): see `urls.txt` → `press`
- Latte Folio (portfolio): `folio`
- Latte Shop (no WooCommerce): `shop`
- Latte Shop Twig (the same shop on the Twig engine — `twig.zip` parent, generated twin `latte-shop-twig` with only `.html.twig` views, Twig-only `wpfasty-builder-twig.zip`; no Latte library or views on the site): `shop-twig`

Self-contained alternative (no gist; verified 2026-10-20 with `@wp-playground/cli server --blueprint=<bundle>/blueprint.json --blueprint-may-read-adjacent-files` on Node 24 — site boots, demo seeded, front page and `/subscribe/` landing render with no PHP notices): `bun run theme:package manifests/examples/<site>.json` writes `dist/<slug>/<slug>-playground.zip`, a blueprint bundle loadable with `?blueprint-url=<hosted zip>`.

Rebuild after theme changes: `bash tools/playground-build.sh` (zips + `urls.txt`), then re-upload every `*.zip` as `<name>.zip.b64` to the gist.
Last rebuild 2026-10-24 (UI8Kit 3): all four blueprints booted with `@wp-playground/cli` 3.1.60 server on Node 24 (PHP 8.5, WP_DEBUG display on) — front page, archive and a single entry 200 with no PHP notices; the Twig shop loads no `Latte\` class and ships no Latte file; all four URLs open on playground.wordpress.net.
