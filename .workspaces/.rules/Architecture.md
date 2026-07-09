# WP FastY Architecture

## Purpose

**wp-fasty** generates WordPress themes with:

1. **UI8Kit Latte primitives** — typed DOM/ARIA contract from `@ui8kit/codegen`
2. **Layout / block / style presets** — Underscores-style catalog, richer than a single starter
3. **Clean PHP runtime** — DI container, Latte engine, WordPress hooks as thin adapters

## Three layers

| Layer | Location | Owns |
|-------|----------|------|
| **1. Primitives** | `.workspaces/ui8kit-latte/` | `Button.latte`, `Card.latte`, `Classes.php` |
| **2. Presets** | `presets/` | layouts, blocks, color styles, theme manifests |
| **3. Theme instance** | `themes/<slug>/` | `functions.php`, context, views, assets |

Codegen (submodule `packages/ui8kit-codegen`) is **dev-only**. Themes consume the Latte bundle, never the TypeScript engine.

## Contracts

### UI primitives

- Single source of truth: `bricks/*.def.ts` in ui8kit/codegen
- Latte templates: `{include 'button/Button.latte', variant: 'default', children: '…'}`
- Children are pre-rendered HTML strings (`{capture}` → `children`)
- Skipped parts (`Breadcrumb`, `Select`, `Icon`): app loops + `UI8Kit\Classes::*()`

### WordPress context

- All template data via one context array (`site`, `menu`, `page` / `posts`, …)
- Schema: `themes/<slug>/context.schema.json`
- No WordPress API calls inside Latte — only in `ContextFactory`

### Templates

- Latte 3 only for presentation
- Framework hooks: `{do_action}`, `{wp_head}`, `{wp_footer}`, `{body_class}` via `WordPressExtension`
- Layout shell calls WordPress head/footer; content views compose presets + primitives

### CSS

- Design tokens in `presets/styles/*.css`
- Tailwind v4 `@source` over `ui8kit-latte/ui/**` and theme `views/**`
- Prefer utility classes from shared recipes; avoid a second semantic-CSS extraction pipeline

## Anti-patterns (retired)

- React → Latte Babel transpile
- Parallel UI kits (`@semantic`, `n3shadcn`, …) as second codegen
- Hand-written `button button-primary` without UI8Kit includes
- Shipping the codegen submodule inside theme zips

## Standards priority

1. Security (escaping, nonces, capability checks)
2. PSR-4 / typed PHP 8.2+ for new OOP
3. WordPress APIs at the adapter boundary
4. UI8Kit DOM parity for primitives
