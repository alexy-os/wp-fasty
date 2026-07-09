# LLM Agent Instructions — wp-fasty

## What this repo is

WordPress **theme generator** monorepo:

- Primitives from **ui8kit/codegen** (submodule → Latte bundle)
- Themes assembled from **presets** (layouts × blocks × styles)
- Reference theme: `themes/wp-fasty-ym`

## Repository map

```
packages/ui8kit-codegen/     # git submodule (SOURCE)
.workspaces/ui8kit-latte/    # generated Latte + PHP (Layer 1)
.workspaces/theme-builder/   # assemble CLI
presets/                     # layouts, blocks, styles, theme manifests
themes/<slug>/               # WordPress theme instances
tools/generate-primitives.ts
```

## Commands

```bash
bun run ui:primitives              # regenerate .workspaces/ui8kit-latte
bun run ui:install                 # composer in ui8kit-latte
bun run theme:build -- wp-fasty-ym
bun run theme:new                  # stub for interactive scaffold
```

## When editing themes

1. **Primitives** — never hand-edit `.workspaces/ui8kit-latte`. Change bricks in codegen, then `ui:primitives`.
2. **Composites / pages** — Latte in `themes/*/views/` or `presets/blocks/`. Compose with `{include 'button/Button.latte', …}`.
3. **Context** — PHP `ContextFactory` only; keep Latte free of `get_post()` etc.
4. **Layout** — use `{wp_head}`, `{wp_footer}`, `{body_class}`; do not hardcode `<title>` if `title-tag` is supported.

## Latte composition pattern

```latte
{capture $label}Explore{/capture}
{include 'button/Button.latte', variant: 'default', children: (string) $label}
```

Latte `FileLoader` must include `.workspaces/ui8kit-latte/ui` (or vendored copy in shipped themes).

## PHP standards

- Namespace `WPFasty\` → `themes/<slug>/classes/`
- PSR-4, constructor promotion, strict types
- DI via `Container`; bootable services tagged in `configs/services.php`
- Escape on output; sanitize on input; i18n text domain = theme slug

## Do not

- Reintroduce React→Latte converters or `data-slot` semantic extractors
- Put codegen TypeScript inside theme zip
- Dump all post meta into context by default
- Log template inventories on every request

## Complex primitives

`Breadcrumb`, `Select`, `Icon` are not generated as Latte files. Use `UI8Kit\Classes::*` + manual loops. See codegen `docs/php-complex-parts.md`.
