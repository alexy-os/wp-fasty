# Live previews (WordPress Playground)

The zips are not in git (`playground/*.zip` is gitignored): the **Playground** workflow builds them in CI with
`tools/playground-build.sh` and mirrors them (base64) in a secret gist: https://gist.github.com/alexy-os/ce9f531eeb05bb8aee9dadcd3b0c1ecc
and the blueprints (`press.json`, `folio.json`, `shop.json`, `shop-twig.json`) are passed inline in the Playground URL fragment.
Everything runs in the visitor's browser — no server.

- Latte Press (classic blog): see `urls.txt` → `press`
- Latte Folio (portfolio): `folio`
- Latte Shop (no WooCommerce): `shop`
- Builder editor: `shop-builder` / `shop-twig-builder` in `urls.txt` — the same blueprints opening wp-admin → Pages → Builder (pick a page, e.g. the front-page landing)
- Latte Shop Twig (the same shop on the Twig engine — `twig.zip` parent, generated twin `latte-shop-twig` with only `.html.twig` views, Twig-only `wpfasty-builder-twig.zip`; no Latte library or views on the site): `shop-twig`

Self-contained alternative (no gist; verified 2026-10-20 with `@wp-playground/cli server --blueprint=<bundle>/blueprint.json --blueprint-may-read-adjacent-files` on Node 24 — site boots, demo seeded, front page and `/subscribe/` landing render with no PHP notices): `bun run theme:package manifests/examples/<site>.json` writes `dist/<slug>/<slug>-playground.zip`, a blueprint bundle loadable with `?blueprint-url=<hosted zip>`.

Rebuild: automatic. The **Playground** workflow (`.github/workflows/playground.yml`) runs on every push to `main` that
touches what goes into the zips (themes, plugins, demo seeders, landings, blueprints, presets, the generator, the two
scripts) and on demand: `gh workflow run playground.yml --repo alexy-os/wp-fasty` (or Actions → Playground → Run
workflow). It builds the ten zips with `tools/playground-build.sh` (the same script as locally) and publishes them with
`tools/playground-publish.sh`: a zip is rewritten as `<name>.zip.b64` only when its content changed (timestamps, `.pot`
dates and Composer's per-build ids ignored), one gist commit by Grok Designer, then every raw URL is checked — 200 and
the zip's sha256. One run at a time (`concurrency`); the gist token is the repository secret `PLAYGROUND_GIST_TOKEN`.
The raw URLs have no revision, so `urls.txt` and the blueprints never change.

Gist raw cache: after a publish, `gist.githubusercontent.com/.../raw/<file>` may serve the previous file for up to
~5 minutes. A Playground opened right after a rebuild can still boot the old build — wait a few minutes and reload.

Locally: `bash tools/playground-build.sh` (needs `zip`) writes the ten zips into `playground/` (ignored by git — never
commit them), then `tools/playground-publish.sh <gist checkout>` shows what
would change (dry run; `--push` publishes).
Last rebuild 2026-10-24 (UI8Kit 3): all four blueprints booted with `@wp-playground/cli` 3.1.60 server on Node 24 (PHP 8.5, WP_DEBUG display on) — front page, archive and a single entry 200 with no PHP notices; the Twig shop loads no `Latte\` class and ships no Latte file; all four URLs open on playground.wordpress.net.
Rebuild 2026-10-24 (builder editor, Cycle L): shop and shop-twig booted locally from the new zips with `@wp-playground/cli` (Node 24, PHP 8.5): editor opens, overlay/typed fields/undo work, `render-block` checked live (`tools/builder/render-block-check.php --live`).
Rebuild 2026-10-24 (@ui8kit/registry 3.3.0): all four blueprints booted locally from the new zips (Playground CLI, Node 24, PHP 8.5) — press, folio, shop, shop-twig pages 200 with no PHP notices; gist updated.
Rebuild 2026-10-25 (builder IDE layout, a7aa983; built from 6966742): all four blueprints booted locally from the new zips (`@wp-playground/cli` 3.1.60 server, Node 24, PHP 8.5, WP_DEBUG + log on) — press, folio, shop, shop-twig front page, archive and single entry 200 with no PHP notices and an empty debug.log; shop and shop-twig builder editor opens with both column separators (keyboard resize/reset) and collapsible columns (bar buttons, `[`/`]`, F focus mode), no JS errors; gist updated, raw URLs match the zips by sha256.
Rebuild 2026-10-28 (block previews, site contacts, builder tokens; built from 1c85f36): six URLs — press, folio, shop, shop-twig, shop-builder, shop-twig-builder; the press blueprint now installs wpfasty-types too (its demo seeds site contacts, read through `WPFastyTypes\Contact`). All six booted on playground.wordpress.net: front pages render; in shop-builder and shop-twig-builder the editor opens with the block cards and loads the WebP previews from the plugin (`sources/ui8kit/previews/`, 474 per build), and `GET /links` returns the demo contacts (tel, mailto, WhatsApp, Telegram, OSM map; phone, email, address, hours). Builder zips: wpfasty-builder.zip 3.6 MB, wpfasty-builder-twig.zip 3.2 MB (previews ≈ 2.7 MB of it). Gist updated, raw URLs 200 and match the zips by sha256.
