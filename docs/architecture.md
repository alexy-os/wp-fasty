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

### Theme PHP layers (`themes/latte/classes`, enforced by `deptrac.yaml` + `tests/ArchitectureTest.php`)

| Layer | Namespace | May use | Contents |
|---|---|---|---|
| Core | `WPFasty\Core` | nothing | kernel, `Core\Port\*Interface` ports, readonly DTOs (`Core\Model\Post`, `Image`, `Term`, `Author`, `PostLink`, `MenuItem`, `SiteInfo`, `ArchiveState`), `Core\Html\TrustedHtml` |
| ViewModel | `WPFasty\Data\*` | Core | builders per domain: `Post\PostViewModelBuilder`, `Menu\…`, `Site\…`, `Archive\…` — no WordPress calls |
| Adapter\WordPress | `WPFasty\Adapter\WordPress`, `Hooks`, `Theme`, `Types\Registry`, `Data\ContextFactory` | Core, ViewModel, Renderer, WordPress | `PostRepository` (WP_Post/WP_Query → `Post`, primed caches), menu/site/label/widget/archive/comments adapters; `ContextFactory` is the facade that wires them (DI in `configs/services.php`) |

`Post` names follow the Codex v0.4 entry where they match (`id`, `kind`, `slug`, `title`, `content`, `excerpt`).
Core-generated HTML (the_content, wp_link_pages, widgets, comments) travels as `TrustedHtml`.

Filter `wpfasty_post_data` receives `(array $viewModel, WPFasty\Core\Model\Post $post, bool $singular)`.
Module `context` callables in `types/*.php` receive `(array $viewModel, WPFasty\Core\Model\Post $post, WPFasty\Types\PostType $type)`. They get the Core DTO, never a `WP_Post`. A module that needs WordPress data looks it up by `$post->id`. Admin column `value` callables keep `(int $postId)`.

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
