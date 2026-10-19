# Architecture — theme constructor stack

Constructor roadmap: [`.project/constructor/theme-constructor.md`](../.project/constructor/theme-constructor.md).

## Repositories

| Repo | Role |
|------|------|
| `ui8kit/codegen` | Primitive SSOT + emitters (submodule → `packages/ui8kit-codegen`) |
| `alexy-os/wp-fasty` | Theme generator monorepo (this repo) |

## Four layers

```
packages/ui8kit-codegen/          SOURCE (submodule)
        ↓  bun run ui:primitives
.workspaces/ui8kit-latte/         Layer 1 — Latte primitives + UI8Kit PHP
presets/                          Layer 2 — layouts × blocks × styles × manifests
themes/<slug>/                    Layer 3 — WordPress theme instance
.workspaces/theme-builder/        Assembler CLI (Underscores++)
```

| Layer | Path | Edit? | Owns |
|-------|------|-------|------|
| **0 Source** | `packages/ui8kit-codegen` | In codegen repo | Bricks, IR, emitters |
| **1 Primitives** | `.workspaces/ui8kit-latte` | **Never by hand** | `.latte`, `Classes.php`, `Rt.php` |
| **2 Presets** | `presets/` | Yes | Shell recipes, block Latte, tokens |
| **3 Theme** | `themes/<slug>/` | Yes (PHP + thin views) | DI, context, WP hooks, assets |

## Dependency direction

```
Theme PHP (ContextFactory, Application)
    → Latte Engine (MultiRootLoader: views + ui8kit-latte/ui)
        → Preset / theme views (compose)
            → Generated Part.latte / Classes::*
```

WordPress is a **data adapter**. Presentation is Latte. Primitives are codegen.

## What ships in a theme zip

- Theme PHP + views + assets
- Vendored `ui8kit-latte` (`ui/` + `php/UI8Kit/` + composer deps)
- **Not** the TypeScript codegen engine
- **Not** React/Vite preview apps

## Local preview

Docker Compose mounts monorepo into WordPress (`docker-compose.yml`).

- Edit theme/presets → refresh browser (clear `~cache` if Latte sticks)
- Change bricks → `ui:primitives` (+ composer if PHP API changed)
- Never `docker compose build` for template edits

## PHP runtime (keep from YM experiment)

Worth preserving and later refactoring:

- DI `Container` + bootable services
- `ThemeService` + `ContextFactory` (typed context, not WP calls in views)
- `LatteEngine` + `WordPressExtension` (`{wp_head}`, `{do_action}`, …)
- `MultiRootLoader` (theme views + primitives root)

Discard: dirty front-page markup, dual headers, root CSS copy hacks, React→Latte history.
