# Live previews (WordPress Playground)

The built zips are mirrored
(base64) in a secret gist: https://gist.github.com/alexy-os/ce9f531eeb05bb8aee9dadcd3b0c1ecc
and the blueprints (`press.json`, `folio.json`, `shop.json`, `shop-twig.json`) are passed inline in the Playground URL fragment.
Everything runs in the visitor's browser — no server.

- Latte Press (classic blog): see `urls.txt` → `press`
- Latte Folio (portfolio): `folio`
- Latte Shop (no WooCommerce): `shop`
- Builder editor: `shop-builder` / `shop-twig-builder` in `urls.txt` — the same blueprints opening wp-admin → Pages → Builder (pick a page, e.g. the front-page landing)
- Latte Shop Twig (the same shop on the Twig engine — `twig.zip` parent, generated twin `latte-shop-twig` with only `.html.twig` views, Twig-only `wpfasty-builder-twig.zip`; no Latte library or views on the site): `shop-twig`

Self-contained alternative (no gist; verified 2026-10-20 with `@wp-playground/cli server --blueprint=<bundle>/blueprint.json --blueprint-may-read-adjacent-files` on Node 24 — site boots, demo seeded, front page and `/subscribe/` landing render with no PHP notices): `bun run theme:package manifests/examples/<site>.json` writes `dist/<slug>/<slug>-playground.zip`, a blueprint bundle loadable with `?blueprint-url=<hosted zip>`.

Rebuild after theme changes: `bash tools/playground-build.sh` (zips + `urls.txt`, incl. the two `-builder` URLs), then re-upload every `*.zip` as `<name>.zip.b64` to the gist.
Last rebuild 2026-10-24 (UI8Kit 3): all four blueprints booted with `@wp-playground/cli` 3.1.60 server on Node 24 (PHP 8.5, WP_DEBUG display on) — front page, archive and a single entry 200 with no PHP notices; the Twig shop loads no `Latte\` class and ships no Latte file; all four URLs open on playground.wordpress.net.
Rebuild 2026-10-24 (builder editor, Cycle L): shop and shop-twig booted locally from the new zips with `@wp-playground/cli` (Node 24, PHP 8.5): editor opens, overlay/typed fields/undo work, `render-block` checked live (`tools/builder/render-block-check.php --live`).
Rebuild 2026-10-24 (@ui8kit/registry 3.3.0): all four blueprints booted locally from the new zips (Playground CLI, Node 24, PHP 8.5) — press, folio, shop, shop-twig pages 200 with no PHP notices; gist updated.
Rebuild 2026-10-25 (builder IDE layout, a7aa983; built from 6966742): all four blueprints booted locally from the new zips (`@wp-playground/cli` 3.1.60 server, Node 24, PHP 8.5, WP_DEBUG + log on) — press, folio, shop, shop-twig front page, archive and single entry 200 with no PHP notices and an empty debug.log; shop and shop-twig builder editor opens with both column separators (keyboard resize/reset) and collapsible columns (bar buttons, `[`/`]`, F focus mode), no JS errors; gist updated, raw URLs match the zips by sha256.
