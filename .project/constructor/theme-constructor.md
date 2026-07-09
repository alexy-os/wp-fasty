# Theme constructor — target design

How `.workspaces/theme-builder` should grow. Current CLI is a **stamp stub**; this doc is the product contract.

## Job

Assemble a WordPress theme from:

1. **Latte primitives** (`.workspaces/ui8kit-latte`)
2. **Presets** (layouts × blocks × styles × theme manifest)
3. **PHP skeleton** (DI, ContextFactory, Latte engine, WP adapters)

Output: `themes/<slug>/` ready for Docker preview and later zip export.

## Manifest (`presets/themes/<id>.json`)

```json
{
  "id": "wp-fasty-ym",
  "name": "WP FastY YM",
  "slug": "wp-fasty-ym",
  "textDomain": "wp-fasty-ym",
  "layout": "layouts/classic",
  "style": "styles/default",
  "templates": {
    "front-page": ["blocks/hero-centered", "blocks/featured-grid"],
    "archive": ["blocks/archive-header", "blocks/post-list"],
    "single": ["blocks/post-single"]
  },
  "features": ["post-thumbnails", "primary-menu", "title-tag"],
  "php": { "namespace": "WPFasty", "container": true }
}
```

| Field | Meaning |
|-------|---------|
| `layout` | Shell recipe (sidebar/header/sheet or classic top-nav) |
| `style` | Token CSS preset |
| `templates` | WP template → ordered block includes |
| `features` | `add_theme_support` / menu registrations |
| `php` | Namespace + whether to emit DI container |

## Build pipeline (target)

```
validate manifest
  → ensure ui8kit-latte manifest exists
  → resolve layout + blocks + style paths
  → emit / sync theme skeleton (if new)
  → wire views: layout shell + per-template includes
  → copy or composer-path vendor ui8kit-latte
  → enqueue style tokens + built CSS + ui8kit.js
  → write .theme-build.json stamp
  → (later) run blockonce + ui8px on emitted views
```

### v0.1 (today)

- Load JSON preset
- Warn if primitives missing
- Write `.theme-build.json` stamp only

### v0.2 (next)

- Emit layout shell Latte from `presets/layouts/<name>/`
- Compose template views from block list
- Copy style CSS into theme assets entry
- Scaffold missing PHP files from a clean skeleton (not dirty YM views)

### v0.3

- Shell with sidebar + mobile sheet + twin IDs (see [shell-composition.md](./shell-composition.md))
- Enqueue `@ui8kit/aria` bundle
- Context schema generation from manifest features
- Zip export without codegen submodule

## Layout presets

Each layout owns:

| Artifact | Role |
|----------|------|
| `shell.latte` / README contract | Document + app chrome |
| `sheet-ids` constants | Twin IDs for mobile nav |
| Slot names | `content`, optional `sidebar` |

Layouts compose **primitives + composites**, not raw flex divs.

## Block presets

- One file ≈ one section; **one Block** landmark
- Props from context keys documented in block README or front matter
- No WP API calls
- Demo defaults optional in `presets/blocks/*.data.json` (Templ pattern)

## Style presets

- CSS variables only (`presets/styles/*.css`)
- Theme Tailwind `@source` includes `ui8kit-latte/ui/**` + theme `views/**`
- Do not redefine Button/Card recipes in theme CSS

## PHP skeleton (clean baseline)

Emit or maintain separately from dirty YM markup:

- `functions.php` → boot container
- `classes/` — Container, ThemeService, ContextFactory, LatteEngine, WordPressExtension, MultiRootLoader
- `configs/services.php`
- `context.schema.json`
- `views/layout/default.latte` — `{wp_head}`, `{body_class}`, `{wp_footer}`
- `views/pages/*.latte` — thin includes of preset blocks

Refactor YM OOP later; **do not** copy its front-page HTML into the generator.

## Commands

```bash
bun run ui:primitives              # Layer 1
bun run ui:install                 # composer in ui8kit-latte
bun run theme:build -- <theme-id>  # Layer 2→3
bun run theme:new                  # interactive scaffold (stub)
bun run dev:up                     # Docker WordPress
```

## Success criteria

A generated theme:

1. Renders front page from layout + blocks with UI8Kit includes
2. Has valid SSR without JS; sheet works when `ui8kit.js` loaded
3. Passes layout grammar in presets
4. Ships without codegen TypeScript
5. Uses ContextFactory for all template data
