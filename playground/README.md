# Live previews (WordPress Playground)

Each blueprint installs the vendored `latte` parent + one child theme from this branch and imports demo content
(`tools/demo/*.php`, images bundled in `demo.zip`). Runs entirely in the browser — no server.

| Theme | Preview |
|---|---|
| Latte Press (classic blog) | https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/godudy/wpfasty/improved/playground/press.json |
| Latte Folio (portfolio) | https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/godudy/wpfasty/improved/playground/folio.json |
| Latte Shop (no WooCommerce) | https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/godudy/wpfasty/improved/playground/shop.json |

Rebuild artifacts after theme changes: `bash tools/playground-build.sh`.
