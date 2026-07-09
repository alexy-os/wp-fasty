# WP FastY — Full Progress & Continuation Plan

**Purpose:** Single handoff document for agents (including Cursor browser).  
Read this first, then execute the next open cycle. Do not invent a parallel roadmap.

**Last updated:** 2026-07-09 (verification pass)  
**Active theme:** `themes/latte` · demo skin: `themes/latte-skin`  
**Repo:** `godudy/wpfasty` · monorepo with UI8Kit Codegen Latte primitives  
**Monorepo version:** `0.3.0` (`package.json`)

---

## How to use this file (agent protocol)

1. Read **Current state** and **Open cycle**.
2. Execute only the tasks in that cycle (or the next open follow-up).
3. After finishing work: mark checkboxes, bump **Last updated**.
4. Prefer small PRs / commits per cycle.
5. Discipline SSOT: `docs/` · agent rules: `.cursor/rules/` · constructor KB: `.project/constructor/`.

### Session start checklist

```
[x] bun / PHP available (Composer via tools/composer.sh or composer.phar)
[x] themes/latte + themes/latte-skin (child has no composer/vendor)
[x] .workspaces/ui8kit-latte exists
[x] PHPUnit 13/13 green (after UI8Kit autoload fix)
[x] theme:pack → dist/latte.zip works
```

---

## Verification pass (2026-07-09)

Audited claimed Cycles A–G against the tree and ran tooling.

### Matches the plan (implemented)

| Cycle | Evidence |
|-------|----------|
| **A** | `ThemeService::resolveView()`, `404.php`, `search.php`, `createErrorContext`, cache `0755`, `PrimitivesLocator` |
| **B** | Loader roots child→parent→primitives, `wpfasty_latte_roots`, `themes/latte-skin` (no Composer), `Template: latte` |
| **C** | `theme:pack` bundles `lib/ui8kit-latte`, release composer path, `docs/install.md`, `dist/latte.zip` |
| **D** | Builder syncs `presets/blocks` → theme, emits page views, scaffolds skins |
| **E** | `languages/latte.pot`, skip/mobile nav, PHPUnit suite, PHPCS scripts, hero URLs via context |
| **F** | `presets/layouts/app-shell`, app-shell Latte parts, `ui8kit.js`, skin sets `wpfasty_shell` |
| **G** | `theme.json`, custom-logo / embeds, comments off, srcset, CI workflow, screenshots |

### Gaps found & fixed in this pass

| Issue | Fix |
|-------|-----|
| PHPUnit Latte smoke: `UI8Kit\Rt` not found (Windows junction) | `tests/bootstrap.php` fallback require; theme `composer.json` `symlink: false` |
| `theme:pack` failed without system `composer` | Prefer `php composer.phar`; `tools/composer.sh` |
| `theme:pack` zip/tar failed on Windows (`tar` D: bug) | PowerShell `Compress-Archive` + store-zip fallback |
| Root `composer test` / `phpcs` assumed global Composer | `package.json` → `tools/composer.sh` |
| theme-builder still at `0.1.0` | Bumped to `0.3.0` |

### Still deferred (local only)

| Item | Notes |
|------|-------|
| A7 / B9 Docker smoke | Needs `bun run dev:up` on machine with Docker |
| PHPCS clean | CI has `continue-on-error: true` — debt remains |
| Packagist publish of `ui8kit/latte-primitives` | Zip uses bundled `lib/` instead |

---

## Current state (snapshot)

### Done

| Area | Status |
|------|--------|
| Codegen Latte bundle | Done |
| Docs + `.cursor/rules` + `.project` KB | Done |
| Parent `themes/latte` | Done |
| Cycles A–G (code) | Done |
| PHPUnit 13 tests | Done (green) |
| `theme:pack` → `dist/latte.zip` | Done (verified this pass) |
| Package scripts / composer helper | Done (this pass) |

### Architecture (locked)

```
packages/ui8kit-codegen/     SOURCE
        ↓  bun run ui:primitives
.workspaces/ui8kit-latte/    Layer 1
presets/                     Layer 2
themes/latte/                Layer 3 parent (Composer)
themes/latte-skin/           Layer 3b child (no Composer)
.workspaces/theme-builder/   build + pack
dist/latte.zip               release artifact (gitignored)
```

### Hard rules (never regress)

- Never hand-edit `.workspaces/ui8kit-latte`
- No WP queries inside Latte — only `ContextFactory`
- No React→Latte / `data-slot` semantic CSS pipelines
- Variants only in `*.variants.json`
- Layout grammar: one Block per block file
- Child skins: no `composer.json` / `vendor/` / Application boot

---

## Open cycle

**→ Follow-ups (post A–G)** — not a new lettered cycle; pick from backlog below.

Suggested next:

1. Local Docker smoke (A7/B9): activate `latte` then `latte-skin`
2. Tighten PHPCS until CI can drop `continue-on-error`
3. Optional: publish `ui8kit/latte-primitives` to Packagist

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
                            → Follow-ups (Docker / PHPCS / Packagist)
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
| A7 | Smoke in Docker | [ ] deferred local |

---

### Cycle B — Child theme as skin

| Stage | Task | Done |
|-------|------|------|
| B1–B8, B10 | Skin contract + roots + scaffold | [x] |
| B9 | Docker mount/activate | [ ] deferred (compose volume present) |

---

### Cycle C — Packaging & install

| Stage | Task | Done |
|-------|------|------|
| C1–C5 | Bundle lib + pack + docs + dev path-repo | [x] |
| C✓ | `dist/latte.zip` produced on Windows | [x] 2026-07-09 |

---

### Cycle D — Builder assembles views

| Stage | Task | Done |
|-------|------|------|
| D1–D6 | Sync blocks, emit pages, skin scaffold | [x] |

---

### Cycle E — Quality bar

| Stage | Task | Done |
|-------|------|------|
| E1–E6 | i18n, a11y, PHPUnit, PHPCS scripts, hero URLs | [x] |
| E✓ | PHPUnit 13/13 green after autoload fix | [x] 2026-07-09 |

---

### Cycle F — Shell v2

| Stage | Task | Done |
|-------|------|------|
| F1–F6 | app-shell + sheet + ui8kit.js + skin filter | [x] |

---

### Cycle G — Product polish

| Stage | Task | Done |
|-------|------|------|
| G1–G6 | theme.json, logo, comments, srcset, CI | [x] |

---

## Commands cheat sheet

```bash
bun run ui:primitives
bun run ui:install
bash tools/composer.sh install --working-dir=themes/latte
bun run theme:build -- latte
bun run theme:build -- latte-skin
bun run theme:pack -- latte          # → dist/latte.zip
bun run test                         # PHPUnit via tools/composer.sh
bun run phpcs

# Docker (local)
bun run dev:up
bun run dev:wp -- theme activate latte
bun run dev:wp -- theme activate latte-skin
```

---

## Package versions (aligned)

| Package | Version |
|---------|---------|
| `wpfasty` (root `package.json`) | `0.3.0` |
| `@wpfasty/theme-builder` | `0.3.0` |
| `themes/latte` style.css | `1.0.0` |
| Root Composer tooling | phpunit ^10.5, phpcs, wpcs |

---

## Decision log

| Date | Decision |
|------|----------|
| 2026-07 | YM deleted; `themes/latte` is reference parent |
| 2026-07 | Child skins = WP child themes, no Composer |
| 2026-07 | Release zip bundles primitives under `lib/ui8kit-latte` |
| 2026-07 | Presets SSOT for blocks; builder emits page views |
| 2026-07 | Minimal `ui8kit.js` until `@ui8kit/aria` package |
| 2026-07 | Docker smokes deferred to local machine |
| 2026-07 | Theme path-repo uses `symlink: false` for Windows PHP |
| 2026-07 | Pack uses `composer.phar` + PowerShell zip on Windows |

---

## Browser Cursor handoff prompt

> Continue WP FastY from `.project/PROGRESS.md`.  
> Cycles A–G are complete and verified; follow-ups: Docker smoke (A7/B9), PHPCS debt, optional Packagist.  
> Active theme: `themes/latte`; demo skin: `themes/latte-skin`.  
> Use `bash tools/composer.sh` for Composer; `bun run theme:pack -- latte` for zip.  
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
| [themes/latte/README.md](../themes/latte/README.md) | Parent setup |
| [themes/latte-skin/README.md](../themes/latte-skin/README.md) | Skin demo |
