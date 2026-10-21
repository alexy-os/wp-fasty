# Builder block registries (wpfasty-catalog@2)

One registry per runtime; a registry never contains or points to the other runtime (ADR-001, K7).

## URLs

| | Latte | Twig |
|---|---|---|
| latest | `https://registry.buildy.tw/latte/wpfasty/` | `https://registry.buildy.tw/twig/wpfasty/` |
| pinned (immutable) | `https://registry.buildy.tw/latte/wpfasty/v<semver>/` | `https://registry.buildy.tw/twig/wpfasty/v<semver>/` |

Served from `ui8kit/site` (`r/<runtime>/wpfasty/`), next to and independent of the shadcn-style `r/<runtime>/index.json`
registry (its format is unchanged). A version directory is written once and never changed; `latest` is a copy of the
newest version. Sites should import a pinned URL.

Published: v1.0.0 (ui8kit/site `236f670`, source `builder-registry/`, checked by its `registry:validate`) —
`latte` blocks.zip sha256 `08d0b36868f4…`, `twig` `f0564786cb9a…`; verified over HTTPS with
`bun run registry:verify <runtime> https://registry.buildy.tw/<runtime>/wpfasty/v1.0.0/`. New versions:
`WPFASTY=<wp-fasty checkout> bun run registry:wpfasty --version x.y.z` in ui8kit/site, then commit and push.

Workflow `Published registries` (`.github/workflows/registry-published.yml`, separate from CI, never blocks it; after
each green CI run on main, daily and on demand) compares the published `v<version>/` and `latest/` byte for byte
(sha256, size) with `dist/registry` built from source in that CI run, runs `registry:verify` on the URLs, and imports
both published registries into a real WordPress (latte-shop on latte.zip, latte-shop-twig on twig.zip): import from
`https://registry.buildy.tw/…`, render with only the site engine loaded, other runtime refused. Network calls retry.

## Files

- `catalog.json` — the builder catalog (blocks, variants, props, bindings, collections) with:
  - `"$schema": "https://github.com/alexy-os/wp-fasty/plugins/wpfasty-builder/catalog@2"`, `"format": 2`
  - `"runtime": "latte" | "twig"`, `"templates"` = the same value
  - `"version"`: semver of the registry (= directory name without `v`)
  - `"source": {repo, commit}` — the codegen-x commit both runtimes were generated from
  - `"archive": {"file": "blocks.zip", "bytes": n, "sha256": "…"}`
  - variant templates in the runtime's extension: `blocks/<name>.latte` or `blocks/<name>.html.twig`
- `blocks.zip` — `blocks/` and `ui/` of that runtime only (`.latte` or `.twig`, `.json`, `.md`; no PHP). The PHP
  runtime always comes from the plugin build of the same engine.

## Import rules (plugin)

- `runtime` must equal the site engine, the archive must match `sha256`, every variant template must exist in the
  archive with the runtime's extension; otherwise the import fails and nothing changes.
- Format 1 (`catalog.json` + `latte.zip` [+ `twig.zip`]) still imports: only the site engine's zip is used and it
  is required (no other-runtime substitute); the stored copy is converted to the format-2 layout.
- Stored at `uploads/wpfasty-builder/sources/<id>/{catalog.json,blocks,ui}`; option `wpfasty_builder_sources` rows
  `{id, url, runtime, version, imported_at}`.
- A block or template missing from the site runtime's source: empty output on the front end, an admin notice, and the
  editor refuses to save the layout.

## Build

`bun tools/builder/export-ui8kit.ts` writes `plugins/wpfasty-builder/sources/ui8kit/{latte,twig}/catalog.json` from
codegen-x; `bun tools/builder/pack-source.ts <runtime> <out>` writes `<out>/catalog.json` + `<out>/blocks.zip` for one
runtime. `bun run registry:build <out> [--version x.y.z]` writes both registries as
`<out>/<runtime>/wpfasty/{v<version>,latest}/` (refuses to change an existing `v<version>/`; the zip is deterministic, so a
rebuild of the same source is accepted) and verifies them; `bun run registry:verify <runtime> <dir|url>` applies the
import rules to a directory or a published URL (format, runtime, semver, sha256/bytes, only that runtime's templates, no
PHP, every variant template present). CI builds `dist/registry/` (artifact), rebuilds it, and checks the negatives
(other runtime, tampered archive, changed immutable version). `ui8kit/site` publishes the built tree under
`r/<runtime>/wpfasty/`.
