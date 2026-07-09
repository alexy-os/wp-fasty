# WP FastY documentation

Human-facing source of truth for the monorepo. Agent rules in
[`.cursor/rules/`](../.cursor/rules/) summarize these docs; the theme-constructor
deep dive lives in [`.project/`](../.project/).

## Read order

| # | Doc | Audience |
|---|-----|----------|
| 1 | [manifesto.md](./manifesto.md) | Why FastY exists |
| 2 | [principles.md](./principles.md) | Three WhalY |
| 3 | [mental-model.md](./mental-model.md) | Five ideas that never change |
| 4 | [architecture.md](./architecture.md) | Layers, deps, what ships |
| 5 | [layout-grammar.md](./layout-grammar.md) | Block / Box / Stack / Group |
| 6 | [atomic-ui8px.md](./atomic-ui8px.md) | Variants, Class, tokens |
| 7 | [aria.md](./aria.md) | Static ARIA vs `@ui8kit/aria` |
| 8 | [latte-primitives.md](./latte-primitives.md) | 60/63, capture/include |
| 9 | [anti-patterns.md](./anti-patterns.md) | Retired pipelines |
| 10 | [validation.md](./validation.md) | Gates to run |

## Related

| Path | Role |
|------|------|
| [`.project/`](../.project/) | Theme constructor KB (shell recipes, builder roadmap) |
| [`.cursor/rules/`](../.cursor/rules/) | Cursor agent enforcement |
| [`.dev/README.md`](../.dev/README.md) | Docker / WSL local WordPress |
| Codegen `docs/` | Brick contracts, parity, PHP complex parts |
