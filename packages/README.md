# packages/

External / vendored dependencies for the wp-fasty monorepo.

## `ui8kit-codegen` (git submodule)

Upstream: [ui8kit/codegen](https://github.com/ui8kit/codegen)

```bash
# First-time setup
git submodule add https://github.com/ui8kit/codegen.git packages/ui8kit-codegen
git submodule update --init --recursive

# Or clone with submodules
git clone --recurse-submodules https://github.com/alexy-os/wp-fasty.git
```

**Not** a Bun workspace package — invoke explicitly:

```bash
bun run ui:primitives          # → .workspaces/ui8kit-latte
bun run codegen:verify         # full codegen test suite (optional)
```

Local development without submodule: set `UI8KIT_CODEGEN` or keep a sibling
checkout at `../UI8Kit/Codegen` / `D:/UI8Kit/Codegen` (see `tools/generate-primitives.ts`).
