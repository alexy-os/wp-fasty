# Live previews (WordPress Playground)

`godudy/wpfasty` is private, so raw.githubusercontent URLs are not public. The built zips are mirrored
(base64) in a secret gist: https://gist.github.com/alexy-os/ce9f531eeb05bb8aee9dadcd3b0c1ecc
and the blueprints (`press.json`, `folio.json`, `shop.json`) are passed inline in the Playground URL fragment.
Everything runs in the visitor's browser — no server.

- Latte Press (classic blog): see `urls.txt` → `press`
- Latte Folio (portfolio): `folio`
- Latte Shop (no WooCommerce): `shop`

Rebuild after theme changes: `bash tools/playground-build.sh`, re-upload `*.zip` as base64 to the gist, regenerate URLs.
