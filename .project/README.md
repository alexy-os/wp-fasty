# `.project/` — Theme constructor knowledge base

Deep dive for building the **theme generator**. Shared discipline (layout,
ui8px, ARIA, Latte) lives in [`docs/`](../docs/) — do not duplicate it here.

## Contents

| Path | Purpose |
|------|---------|
| [constructor/theme-constructor.md](./constructor/theme-constructor.md) | Builder pipeline, manifest, roadmap |
| [constructor/shell-composition.md](./constructor/shell-composition.md) | Sidebar + header + mobile sheet recipe |
| [SOURCES.md](./SOURCES.md) | Provenance (Templ → docs / this KB) |

## Read with

1. [`docs/`](../docs/) — mental model, architecture, grammar, primitives  
2. This folder — how the assembler should compose themes  
3. [`.cursor/rules/`](../.cursor/rules/) — agent enforcement while coding  

## Non-goals

- Not brick product specs (codegen `bricks/`)
- Not dirty YM theme templates
- Not a second copy of `docs/`
