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
runtime (`ui8kit/site`: `bun run registry:wpfasty` publishes both into `r/<runtime>/wpfasty/`).
