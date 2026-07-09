# Development workspaces

Internal tooling for wp-fasty — **not** shipped inside WordPress theme zips.

| Path | Role |
|------|------|
| `ui8kit-latte/` | **Generated** Latte primitives + `UI8Kit` PHP runtime (from codegen) |
| `theme-builder/` | Theme assembler CLI (`theme:build`, `theme:new`) |
| `.rules/` | Architecture rules, manifesto, LLM agent instructions |

## Layers

```
packages/ui8kit-codegen     →  SOURCE (submodule)
        ↓ ui:primitives
.workspaces/ui8kit-latte    →  Layer 1 primitives
        ↓ theme-builder + presets/
themes/<slug>/              →  Layer 3 theme instance
```

## Commands (from repo root)

```bash
bun run ui:primitives    # regenerate Latte bundle
bun run ui:install       # composer install in ui8kit-latte
bun run theme:build -- wp-fasty-ym
```
