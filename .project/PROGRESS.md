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
[x] themes/latte is the only theme under themes/
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
| Clean parent theme `themes/latte` (DI, ContextFactory, Latte, UI8Kit includes) | Done |
| Preset manifest `presets/themes/latte.json` | Done |
| Docker mounts + composer-install for `latte` | Done |
| Deleted `themes/wp-fasty-ym` + reference cleanup | Done |
| Theme-builder default id → `latte` | Done |
| `ThemeService::resolveView()` + shared render entry | Done |
| `404.php` / `search.php` + Latte views + error/search context | Done |
| Admin notice when UI8Kit primitives missing | Done |
| Latte cache dir mode `0755` | Done |
| Offline Latte smoke (front, single, archive, search, 404) | Done |

### Not done (summary)

| Area | Status |
|------|--------|
| Docker WP smoke (A7) | Blocked in cloud-agent (no Docker) |
| Child theme (“skin”) without Composer | Missing |
| Parent packaging without monorepo path-repo | Incomplete |
| theme-builder assembling views from presets | Stub (stamp only) |
| i18n / a11y / tests / theme.json | Missing |
| Shell with sidebar + mobile sheet + `@ui8kit/aria` | Not started |

### Architecture (locked)

```
packages/ui8kit-codegen/     SOURCE (submodule / local)
        ↓  bun run ui:primitives
.workspaces/ui8kit-latte/    Layer 1 — generated Latte + UI8Kit PHP
presets/                     Layer 2 — layouts × blocks × styles × manifests
themes/latte/                Layer 3 — parent theme (PHP + views)
themes/<skin>/               Layer 3b — child skins (views + CSS only)  ← TARGET
.workspaces/theme-builder/   Assembler CLI
```

### Hard rules (never regress)

- Never hand-edit `.workspaces/ui8kit-latte`
- No WP queries inside Latte — only `ContextFactory`
- No React→Latte / `data-slot` semantic CSS pipelines
- Variants only in `*.variants.json`
- Layout grammar: one Block per block file
- Do not revive deleted YM theme as design system

---

## Open cycle

**→ Cycle A — Parent correctness (P0)** — A1–A6 done; **A7** (Docker WP smoke) still open when Docker is available.

When A7 is complete, set Open cycle to **Cycle B**.

---

## Full path — cycles & stages

Work proceeds in **cycles** (vertical slices). Each cycle has **stages** (ordered steps).  
Finish a cycle before starting the next unless a blocker forces a spike.

```
A Parent correctness
    → B Child skin (no Composer)
        → C Packaging & install
            → D Builder assembles views
                → E Quality (i18n, a11y, tests)
                    → F Shell v2 (sidebar + sheet + ARIA)
                        → G Product polish (theme.json, zip, CI)
```

---

### Cycle A — Parent correctness (P0)

**Goal:** `themes/latte` never renders an empty/wrong page; routing is single-sourced.

| Stage | Task | Done |
|-------|------|------|
| A1 | Add `ThemeService::resolveView(): string` — one place for hierarchy | [x] |
| A2 | Wire `index.php` + dedicated templates through `resolveView()` / shared render | [x] |
| A3 | Add `404.php` + `views/pages/404.latte` + context (`createErrorContext` or similar) | [x] |
| A4 | Dedicated search handling (view or archive variant with query string) | [x] |
| A5 | Admin notice if UI8Kit primitives dir missing (fail loud, not on first include) | [x] |
| A6 | Latte cache dir `0755` (not `0777`) | [x] |
| A7 | Smoke: front, single, archive, 404 in Docker | [ ] |

**Exit criteria:** Unknown routes show a real 404; no duplicate routing logic; primitives missing → admin notice.

**Notes (2026-07-09):** Offline Latte smoke (no WP/Docker) passed for front, single, archive, search, 404. Cloud-agent image has no Docker — finish A7 locally with `bun run dev:up` + `dev:wp`.

**Key files:**  
`themes/latte/classes/Theme/ThemeService.php`, `includes/render.php`, `index.php`, `404.php`, `search.php`, `configs/services.php`, `classes/Template/LatteEngine.php`, `classes/Theme/PrimitivesLocator.php`, `classes/Data/ContextFactory.php`

---

### Cycle B — Child theme as skin (no Composer / no vendor)

**Goal:** A child theme can restyle and override Latte views **without** its own `composer.json` or `vendor/`. Parent owns Latte + UI8Kit; child is CSS + optional view overrides.

#### Product contract (skin)

```
themes/latte/                 PARENT — Composer, Latte, UI8Kit, PHP runtime
themes/latte-<skin>/          CHILD  — no composer.json, no vendor/
  style.css                   Header: Template: latte
  functions.php               Thin: optional filters only (no Application boot)
  assets/css/tokens.css       Token / skin overrides (optional)
  views/…                     Optional overrides (same relative paths as parent)
  screenshot.png              Optional
```

Child **must not**:

- Run `composer install`
- Boot a second DI container
- Duplicate `classes/`
- Ship codegen or `ui8kit-latte`

Child **may**:

- Override any file under `views/` (stylesheet root wins)
- Override / enqueue skin CSS
- Filter `wpfasty_context`
- Filter loader roots (once filter exists)

#### Stages

| Stage | Task | Done |
|-------|------|------|
| B1 | Document skin contract in `themes/latte/README.md` + `.project/constructor/child-skins.md` | [ ] |
| B2 | Latte loader roots: `get_stylesheet_directory()/views` → `get_template_directory()/views` → primitives `ui/` | [ ] |
| B3 | Filter `wpfasty_latte_roots` (array of absolute dirs) for extensibility | [ ] |
| B4 | Assets: enqueue parent CSS, then child `tokens.css` / `style` if present (`get_stylesheet_directory_uri`) | [ ] |
| B5 | Textdomain: parent `latte`; child may load its own domain from stylesheet `languages/` | [ ] |
| B6 | Scaffold example skin `themes/latte-skin/` (or `latte-demo`) — **no** composer/vendor | [ ] |
| B7 | `style.css` of child: `Template: latte`, unique Theme Name | [ ] |
| B8 | Child `functions.php`: only `add_filter('wpfasty_context', …)` demo + optional CSS — **do not** call `Application::getInstance()` | [ ] |
| B9 | Docker: mount child theme; activate child; verify override of one block (e.g. hero title class) | [ ] |
| B10 | Verify child works when parent `vendor/` exists but child has zero PHP deps | [ ] |

**Exit criteria:** Activate child in WP admin → site renders via parent engine; overriding `views/blocks/hero-centered.latte` in child changes output; child folder has no `vendor/` and no `composer.json`.

**Implementation notes for B2:**

```php
// Target root order (first match wins)
$roots = array_values(array_filter([
    get_stylesheet_directory() . '/views',  // child overrides
    get_template_directory() . '/views',    // parent
    $primitivesDir,                         // UI8Kit
]));
$roots = apply_filters('wpfasty_latte_roots', $roots);
```

Parent `functions.php` still boots Application. Child must **not** re-boot. WordPress loads parent `functions.php` automatically when child is active.

---

### Cycle C — Packaging & install (parent distributable)

**Goal:** Parent theme installs on a normal WordPress host without the monorepo checkout.

| Stage | Task | Done |
|-------|------|------|
| C1 | Decide: Packagist `ui8kit/latte-primitives` **or** vendor-commit / build-step copy into theme | [ ] |
| C2 | Remove hard dependency on path repo `../../.workspaces/ui8kit-latte` for release builds | [ ] |
| C3 | `theme:build` (or `theme:pack`) produces zip: theme + vendored primitives PHP/ui, **no** codegen TS | [ ] |
| C4 | Document install: upload zip → activate → child skins optional | [ ] |
| C5 | Keep monorepo path-repo for **dev** only (composer config / env) | [ ] |

**Exit criteria:** Zip activates on stock WP (Docker without monorepo mount except theme zip contents).

---

### Cycle D — Builder assembles views (presets → theme)

**Goal:** `presets/themes/*.json` is the SSOT; theme-builder writes/syncs `views/pages/*` and copies blocks — no twin hand-maintained copies.

| Stage | Task | Done |
|-------|------|------|
| D1 | Move/sync block Latte: `presets/blocks/*` ↔ theme (single SSOT — prefer presets as source) | [ ] |
| D2 | Builder emits `views/pages/{front-page,archive,single,page}.latte` from `templates` map | [ ] |
| D3 | Builder copies/links style preset into theme assets entry | [ ] |
| D4 | Delete divergent duplicates; one `hero-centered.latte` | [ ] |
| D5 | `bun run theme:build -- latte` regenerates stamp **and** views | [ ] |
| D6 | Optional: `theme:build -- latte-skin` scaffolds child from preset | [ ] |

**Exit criteria:** Changing preset block list + rebuild updates theme page views; no manual twin files.

---

### Cycle E — Quality bar (i18n, a11y, tests)

| Stage | Task | Done |
|-------|------|------|
| E1 | Wrap user-visible Latte strings; generate `languages/latte.pot` | [ ] |
| E2 | Honest `style.css` tags (remove false `translation-ready` / `accessibility-ready` until true) | [ ] |
| E3 | Skip link + mobile nav (or sheet trigger) for primary menu | [ ] |
| E4 | PHPUnit: ContextFactory shape + render smoke (front, single, 404) | [ ] |
| E5 | PHPCS in `package.json` / CI verify | [ ] |
| E6 | Wire hero CTAs to real URLs (home / blog) or Customizer | [ ] |

**Exit criteria:** CI fails on PHPCS/test regressions; strings translatable; mobile can open nav.

---

### Cycle F — Shell v2 (sidebar + sheet + ARIA)

**Goal:** Optional layout preset matching `.project/constructor/shell-composition.md`.

| Stage | Task | Done |
|-------|------|------|
| F1 | Layout preset `layouts/app-shell` (or extend classic) | [ ] |
| F2 | Sidebar + header + mobile sheet markup (UI8Kit / preset) | [ ] |
| F3 | Twin sheet IDs constants | [ ] |
| F4 | Enqueue `@ui8kit/aria` / `ui8kit.js` once in shell | [ ] |
| F5 | `open` = SSR initial only when `behavior="ui8kit"` | [ ] |
| F6 | Demo skin or preset flag to enable app-shell | [ ] |

**Exit criteria:** Desktop sidebar + mobile sheet; SSR valid without JS; sheet works with JS.

---

### Cycle G — Product polish

| Stage | Task | Done |
|-------|------|------|
| G1 | `theme.json` + editor styles (minimal) | [ ] |
| G2 | `custom-logo`, `responsive-embeds`, wide alignment supports | [ ] |
| G3 | Comments template or explicit disable | [ ] |
| G4 | Image `srcset` / sizes in ContextFactory | [ ] |
| G5 | Public README + screenshot for parent + sample skin | [ ] |
| G6 | Full CI matrix (primitives generate, build, phpunit, phpcs) | [ ] |

---

## Child theme — quick reference (for Cycle B)

### Parent responsibilities

| Owns | Path |
|------|------|
| Composer + Latte + UI8Kit | `themes/latte/composer.json`, `vendor/` |
| DI / Context / Engine | `themes/latte/classes/` |
| Default views | `themes/latte/views/` |
| Default tokens + built CSS | `themes/latte/assets/` |

### Child responsibilities

| Owns | Path |
|------|------|
| Theme header `Template: latte` | `style.css` |
| Optional filters only | `functions.php` |
| View overrides | `views/**/*.latte` |
| Skin tokens | `assets/css/tokens.css` |
| **No** | `composer.json`, `vendor/`, `classes/` |

### Activation order (WordPress)

1. Parent `latte` must be installed (with vendor).
2. Child appears under Appearance → Themes.
3. Activate child → WP loads parent `functions.php` then child `functions.php`.
4. Latte resolves child views first.

---

## Commands cheat sheet

```bash
# Primitives
bun run ui:primitives
bun run ui:install

# Parent deps (monorepo)
composer install --working-dir=themes/latte
# or: bun run dev:composer

# Builder
bun run theme:build -- latte

# Docker
bun run dev:up
bun run dev:wp -- theme activate latte
# later: bun run dev:wp -- theme activate latte-skin

# Cache
rm -rf themes/latte/~cache
```

---

## Audit backlog map (from 2026-07-09 review)

| Priority | Item | Cycle |
|----------|------|-------|
| P0 | 404 + resolveView + primitives notice + cache perms | A (A1–A6 done; A7 Docker pending) |
| P0 | Child loader roots + example skin without Composer | B |
| P0/P1 | Distributable parent zip | C |
| P1 | Builder emits views from presets | D |
| P1 | i18n, mobile nav, PHPUnit, PHPCS CI | E |
| P2 | App shell + ARIA | F |
| P2 | theme.json, logo, srcset, full CI | G |

---

## Decision log

| Date | Decision |
|------|----------|
| 2026-07 | YM theme deleted; `themes/latte` is the only reference theme |
| 2026-07 | Child skins = WordPress child themes, **no** Composer on child |
| 2026-07 | Parent keeps Composer; primitives from codegen bundle |
| 2026-07 | Presets are future SSOT; builder still stamp-only until Cycle D |
| 2026-07 | Do not claim DDD for the theme — presentation adapter only |

---

## Browser Cursor handoff prompt

Paste this when starting a new chat:

> Continue WP FastY from `.project/PROGRESS.md`.  
> Open cycle is **Cycle A** (or whatever is marked Open cycle).  
> Active theme: `themes/latte`.  
> Child skins must work **without** composer/vendor on the child.  
> Follow cycles in order; update checkboxes in PROGRESS.md when a stage completes.  
> Discipline: `docs/` + `.cursor/rules/`. Do not revive YM. Do not hand-edit `ui8kit-latte`.

---

## Related docs

| Doc | Use |
|-----|-----|
| [constructor/theme-constructor.md](./constructor/theme-constructor.md) | Builder target design |
| [constructor/shell-composition.md](./constructor/shell-composition.md) | Cycle F shell recipe |
| [docs/architecture.md](../docs/architecture.md) | Layers |
| [docs/anti-patterns.md](../docs/anti-patterns.md) | Forbidden pipelines |
| [docs/validation.md](../docs/validation.md) | Gates |
| [themes/latte/README.md](../themes/latte/README.md) | Parent setup |
