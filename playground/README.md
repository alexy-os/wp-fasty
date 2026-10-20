# Live previews (WordPress Playground)

The built zips are mirrored
(base64) in a secret gist: https://gist.github.com/alexy-os/ce9f531eeb05bb8aee9dadcd3b0c1ecc
and the blueprints (`press.json`, `folio.json`, `shop.json`) are passed inline in the Playground URL fragment.
Everything runs in the visitor's browser — no server.

- Latte Press (classic blog): see `urls.txt` → `press`
- Latte Folio (portfolio): `folio`
- Latte Shop (no WooCommerce): `shop`

Self-contained alternative (no gist; verified 2026-10-20 with `@wp-playground/cli server --blueprint=<bundle>/blueprint.json --blueprint-may-read-adjacent-files` on Node 24 — site boots, demo seeded, front page and `/subscribe/` landing render with no PHP notices): `bun run theme:package manifests/examples/<site>.json` writes `dist/<slug>/<slug>-playground.zip`, a blueprint bundle loadable with `?blueprint-url=<hosted zip>`.

Rebuild after theme changes: `bash tools/playground-build.sh`, re-upload `*.zip` as base64 to the gist, regenerate URLs.
