# WP FastY

WordPress theme **generator** — Latte templates, UI8Kit primitives, and a
catalog of layout / block / style presets.

> Temporary developer remote: [godudy/wpfasty](https://github.com/godudy/wpfasty)

## Architecture (three layers)

```
packages/ui8kit-codegen/      # git submodule → ui8kit/codegen (SOURCE)
        ↓  bun run ui:primitives
.workspaces/ui8kit-latte/     # Layer 1: Latte primitives + UI8Kit PHP
presets/                      # Layer 2: layouts × blocks × styles
themes/<slug>/                # Layer 3: WordPress theme instances
```

| Layer | What | Edit? |
|-------|------|-------|
| Primitives | `Button.latte`, `Classes.php` | No — regenerate from codegen |
| Presets | `presets/blocks/*.latte`, `styles/*.css` | Yes |
| Theme | `themes/wp-fasty-ym` | Yes (views + PHP runtime) |

## Quick start

```bash
# Clone (with submodule when published)
git clone --recurse-submodules https://github.com/godudy/wpfasty.git
cd wpfasty

# Or: local codegen sibling / UI8KIT_CODEGEN env
bun run ui:primitives
bun run ui:install          # composer → .workspaces/ui8kit-latte/php/vendor

bun run theme:build -- wp-fasty-ym
```

### Local WordPress (Docker / WSL)

See **[.dev/README.md](.dev/README.md)** for the full command list.

```bash
bun run ui:primitives
docker compose up -d
docker compose --profile tools run --rm composer
# → http://localhost:8080  → activate theme "WP FastY YM"
```

## Scripts

| Script | Purpose |
|--------|---------|
| `ui:primitives` | Generate Latte bundle into `.workspaces/ui8kit-latte` |
| `ui:install` | `composer install` for Latte + UI8Kit autoload |
| `theme:build` | Stamp / assemble theme from `presets/themes/<id>.json` |
| `theme:new` | Scaffold help for a new preset |
| `theme:cache` | Clear Latte `~cache` under themes |
| `codegen:verify` | Run full ui8kit/codegen suite (needs submodule) |

## Repository layout

```
wpfasty/
├── packages/ui8kit-codegen/  # submodule (optional locally)
├── .workspaces/
│   ├── ui8kit-latte/         # generated — commit or CI-regenerate
│   └── theme-builder/        # Bun CLI
├── .cursor/rules/            # Cursor agent rules (single location)
├── .project/                 # theme constructor KB
├── docs/                     # human-facing documentation
├── presets/                  # layouts × blocks × styles × manifests
├── themes/<slug>/            # WordPress theme instances
└── tools/
```

## Documentation

| Path | Role |
|------|------|
| [docs/](docs/) | Human SSOT — architecture, grammar, Latte, ARIA |
| [.cursor/rules/](.cursor/rules/) | Agent enforcement |
| [.project/](.project/) | Theme constructor deep dive |
| [.dev/README.md](.dev/README.md) | Docker / WSL |

**Three WhalY:** zero redundant code · zero wasteful work · PageSpeed 100 goal.  
See [docs/principles.md](docs/principles.md) and [docs/manifesto.md](docs/manifesto.md).

## UI8Kit primitives

Generated Latte parts (60/63). Skipped: `Breadcrumb`, `Select`, `Icon` —
use `UI8Kit\Classes::*` helpers. Details in the codegen docs
(`docs/php-complex-parts.md` in the submodule).

```latte
{capture $label}Explore{/capture}
{include 'button/Button.latte', variant: 'default', children: (string) $label}
```

## License

MIT
