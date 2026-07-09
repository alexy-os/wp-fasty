# WP FastY — Full Progress & Continuation Plan

**Purpose:** Single handoff document for agents (including Cursor browser).  
Read this first, then execute the next open cycle. Do not invent a parallel roadmap.

**Last updated:** 2026-07-09  
**Active theme:** `themes/latte` (experimental YM theme deleted)  
**Repo:** `godudy/wpfasty` · monorepo with UI8Kit Codegen Latte primitives

---

## How to use this file (agent protocol)

1. Read **Current state** and **Open cycle**.
2. Execute only the tasks in that cycle (or the next P0 if cycle is done).
3. After finishing a cycle: mark checkboxes, bump **Last updated**, set **Open cycle** to the next one.
4. Prefer small PRs / commits per cycle. Do not skip ahead to P2 while P0 is open.
5. Discipline SSOT: `docs/` · agent rules: `.cursor/rules/` · constructor KB: `.project/constructor/`.

### Session start checklist

```
[x] bun / PHP / Docker available as needed
[x] themes/latte is the only parent theme under themes/ (+ latte-skin child)
[x] .workspaces/ui8kit-latte exists (bun run ui:primitives if not)
[x] Read Open cycle below — do that work only
```

---

## Current state (snapshot)

### Done

| Area | Status |
|------|--------|
| Codegen Latte bundle (`ui:primitives` → `.workspaces/ui8kit-latte`) | Done |
| Docs + `.cursor/rules` + `.project` KB | Done |
| Clean parent theme `themes/latte` | Done |
| Cycle A — resolveView, 404/search, primitives notice, cache 0755 | Done (A7 Docker deferred) |
| Cycle B — child skin roots/filter/CSS + `themes/latte-skin` | Done (B9 Docker deferred) |
| Cycle C — `theme:pack` zip with bundled `lib/ui8kit-latte` | Done |
| Cycle D — builder syncs blocks + emits page views from presets | Done |
| Cycle E — i18n pot, skip/mobile nav, PHPUnit, PHPCS scripts, hero URLs | Done |
| Cycle F — app-shell preset + classic sheet + `ui8kit.js` | Done |
| Cycle G — theme.json, logo/embeds, comments off, srcset, screenshots, CI | Done |

### Deferred (local Docker)

| Area | Status |
|------|--------|
| A7 / B9 / C exit Docker smoke | Run locally: `bun run dev:up` + activate latte / latte-skin |

### Architecture (locked)

```
packages/ui8kit-codegen/     SOURCE (submodule / local)
        ↓  bun run ui:primitives
.workspaces/ui8kit-latte/    Layer 1 — generated Latte + UI8Kit PHP
presets/                     Layer 2 — layouts × blocks × styles × manifests
themes/latte/                Layer 3 — parent theme (PHP + views)
themes/latte-skin/           Layer 3b — child skin (no Composer)
.workspaces/theme-builder/   Assembler CLI (build + pack)
```

### Hard rules (never regress)

- Never hand-edit `.workspaces/ui8kit-latte`
- No WP queries inside Latte — only `ContextFactory`
- No React→Latte / `data-slot` semantic CSS pipelines
- Variants only in `*.variants.json`
- Layout grammar: one Block per block file
- Do not revive deleted YM theme as design system
- Child skins: no `composer.json` / `vendor/` / Application boot

---

## Open cycle

**→ None — Cycles A–G complete (Docker smokes deferred for local).**

Next work is product polish beyond G, or local Docker verification of A7/B9.

---

## Full path — cycles & stages

```
A Parent correctness ✓
    → B Child skin (no Composer) ✓
        → C Packaging & install ✓
            → D Builder assembles views ✓
                → E Quality (i18n, a11y, tests) ✓
                    → F Shell v2 (sidebar + sheet + ARIA) ✓
                        → G Product polish ✓
```

---

### Cycle A — Parent correctness (P0)

| Stage | Task | Done |
|-------|------|------|
| A1 | `ThemeService::resolveView()` | [x] |
| A2 | Wire templates through shared render | [x] |
| A3 | `404.php` + view + `createErrorContext` | [x] |
| A4 | Search handling | [x] |
| A5 | Admin notice if primitives missing | [x] |
| A6 | Latte cache `0755` | [x] |
| A7 | Smoke in Docker | [ ] deferred |

---

### Cycle B — Child theme as skin (no Composer / no vendor)

| Stage | Task | Done |
|-------|------|------|
| B1 | Document skin contract | [x] |
| B2 | Loader roots: child → parent → primitives | [x] |
| B3 | Filter `wpfasty_latte_roots` | [x] |
| B4 | Child CSS enqueue | [x] |
| B5 | Child textdomain load | [x] |
| B6 | Scaffold `themes/latte-skin/` | [x] |
| B7 | Child `style.css` Template: latte | [x] |
| B8 | Thin `functions.php` (no Application) | [x] |
| B9 | Docker mount/activate | [ ] deferred (compose volume added) |
| B10 | Child zero PHP deps | [x] |

---

### Cycle C — Packaging & install

| Stage | Task | Done |
|-------|------|------|
| C1 | Decision: build-step copy into `lib/ui8kit-latte` | [x] |
| C2 | Release composer uses bundled path (not monorepo) | [x] |
| C3 | `theme:pack` → `dist/latte.zip` | [x] |
| C4 | Install docs (`docs/install.md`) | [x] |
| C5 | Dev path-repo kept in theme `composer.json` | [x] |

---

### Cycle D — Builder assembles views

| Stage | Task | Done |
|-------|------|------|
| D1 | Presets blocks = SSOT (UI8Kit compositions) | [x] |
| D2 | Emit page views from templates map | [x] |
| D3 | Copy style preset → theme tokens | [x] |
| D4 | One hero-centered (presets → theme) | [x] |
| D5 | `theme:build -- latte` stamp + views | [x] |
| D6 | `theme:build -- latte-skin` scaffolds skin | [x] |

---

### Cycle E — Quality bar

| Stage | Task | Done |
|-------|------|------|
| E1 | Labels via ContextFactory + `languages/latte.pot` | [x] |
| E2 | Honest `style.css` tags | [x] |
| E3 | Skip link + mobile sheet trigger | [x] |
| E4 | PHPUnit (13 tests) | [x] |
| E5 | PHPCS via composer / CI | [x] |
| E6 | Hero CTAs → blog / home URLs | [x] |

---

### Cycle F — Shell v2

| Stage | Task | Done |
|-------|------|------|
| F1 | `presets/layouts/app-shell` | [x] |
| F2 | Sidebar + header + mobile sheet | [x] |
| F3 | Twin sheet ID constants | [x] |
| F4 | Enqueue `assets/js/ui8kit.js` | [x] |
| F5 | `behavior="ui8kit"` + SSR `hidden` / `data-state` | [x] |
| F6 | `latte-skin` enables app-shell via `wpfasty_shell` | [x] |

---

### Cycle G — Product polish

| Stage | Task | Done |
|-------|------|------|
| G1 | `theme.json` | [x] |
| G2 | custom-logo, responsive-embeds, align-wide | [x] |
| G3 | Comments disabled | [x] |
| G4 | Image srcset/sizes in ContextFactory | [x] |
| G5 | README + screenshots (parent + skin) | [x] |
| G6 | `.github/workflows/ci.yml` | [x] |

---

## Commands cheat sheet

```bash
bun run ui:primitives
bun run ui:install
composer install --working-dir=themes/latte
bun run theme:build -- latte
bun run theme:build -- latte-skin
bun run theme:pack -- latte          # → dist/latte.zip
composer test
composer phpcs

# Docker (local)
bun run dev:up
bun run dev:wp -- theme activate latte
bun run dev:wp -- theme activate latte-skin
```

---

## Decision log

| Date | Decision |
|------|----------|
| 2026-07 | YM theme deleted; `themes/latte` is the only reference theme |
| 2026-07 | Child skins = WordPress child themes, **no** Composer on child |
| 2026-07 | Release zip bundles primitives under `lib/ui8kit-latte` (not Packagist yet) |
| 2026-07 | Presets are SSOT for blocks; builder emits page views |
| 2026-07 | Minimal `ui8kit.js` ships in theme until `@ui8kit/aria` package lands |
| 2026-07 | Docker smokes deferred to local machine |

---

## Browser Cursor handoff prompt

> Continue WP FastY from `.project/PROGRESS.md`.  
> Cycles A–G are complete; Docker smokes (A7/B9) are for local verification.  
> Active theme: `themes/latte`; demo skin: `themes/latte-skin`.  
> Discipline: `docs/` + `.cursor/rules/`. Do not revive YM. Do not hand-edit `ui8kit-latte`.

---

## Related docs

| Doc | Use |
|-----|-----|
| [constructor/child-skins.md](./constructor/child-skins.md) | Skin contract |
| [constructor/theme-constructor.md](./constructor/theme-constructor.md) | Builder design |
| [constructor/shell-composition.md](./constructor/shell-composition.md) | App shell recipe |
| [docs/install.md](../docs/install.md) | Zip + monorepo install |
| [docs/architecture.md](../docs/architecture.md) | Layers |
| [docs/anti-patterns.md](../docs/anti-patterns.md) | Forbidden pipelines |
| [themes/latte/README.md](../themes/latte/README.md) | Parent setup |
| [themes/latte-skin/README.md](../themes/latte-skin/README.md) | Skin demo |
