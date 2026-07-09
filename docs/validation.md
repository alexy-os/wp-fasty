# Validators & gates

Map of historical Templ checks → Codegen / wp-fasty. What is **enforced today** vs **target**.

## Templ registry (historical — reference only)

Combined: `bun run verify`

| Gate | Command | Scope |
|------|---------|--------|
| Variant invariants | `variantcheck` | every `*.variants.json` |
| Block grammar | `blockonce` | one `<Block>` per TSX/Templ file |
| Spec contract | `validate-spec.sh` | `*.spec.md` front matter, showcase, cva flags |
| Blockgen freshness | `blockgen --check` | generated pages match spec/data |
| ui8px allow-list | `bun run lint:ui8px` | Class strings vs `.ui8px/policy/` |
| ARIA hooks | `bun run validate:aria` | examples markup vs JS manifest |
| Go render tests | `go test ./…` | class / role / `data-ui8kit` contracts |

## Codegen (authoritative for primitives)

| Gate | Command | What it proves |
|------|---------|----------------|
| Brick registry | `bun run check` | `validateRegistry` on all `*.def.ts` |
| Emit | `bun run generate` / `generate:latte-bundle` | Latte + `Classes.php` + manifest |
| DOM parity | `bun test` (+ PHP for Latte) | Same DOM across runtimes |
| PHP support | `phpSupported()` | Exactly which parts skip Latte files |

Parity is the **DOM contract**. Do not weaken it for WordPress convenience.

## wp-fasty (theme constructor — target gates)

| Gate | Status | Intent |
|------|--------|--------|
| Primitives fresh | Partial | `ui:primitives` before build; stamp records `manifest.json` |
| Theme manifest schema | Stub | Validate `presets/themes/*.json` (layout/style/templates exist) |
| `blockonce` on presets | **TODO** | One Block landmark per `presets/blocks/*.latte` |
| ui8px on presets | **TODO** | Lint `class:` strings in preset Latte (+ theme views) |
| ARIA twin IDs | **TODO** | Sheet trigger/panel/title IDs consistent in shell presets |
| Context schema | Soft | `context.schema.json` ↔ `ContextFactory` keys |
| No WP in Latte | Review | Ban `get_*`, `have_posts`, etc. in `.latte` |
| No hand-edit primitives | Review | Diff / CI: `.workspaces/ui8kit-latte` only from generator |

## What agents should run

**Changing bricks (codegen):**

```bash
bun run check
bun run generate:latte-bundle   # or from wpfasty: bun run ui:primitives
bun test                        # when PHP available: Latte parity
```

**Changing presets / theme views:**

```bash
bun run theme:build -- <theme-id>
# Later: lint:ui8px + blockonce on presets/
```

**Changing PHP runtime:**

- PHPUnit / manual Docker smoke (homepage, archive, single)
- Clear theme `~cache` after Latte changes

## Invariants that never need a tool name

1. Variants only in `*.variants.json`
2. One Block per block file
3. Layout utilities only on `class`
4. Behavior hooks opt-in; SSR valid without JS
5. Context from PHP; no WP queries in Latte
6. Never hand-edit generated `ui8kit-latte`
