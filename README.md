# WP FastY

WordPress theme **generator** — Latte templates, UI8Kit primitives, and a
catalog of layout / block / style presets.

> Repository: [alexy-os/wp-fasty](https://github.com/alexy-os/wp-fasty)

## Architecture (UI8Kit 3)

```
themes/latte/ui8kit/          # UI8Kit 3 Latte primitives (npx ui8kit@3, registry 3.2.0) — unstyled, never hand-edit
themes/latte/twig/ui8kit/     # UI8Kit 3 Twig primitives (npx ui8kit@3 --framework twig) — never hand-edit
themes/latte/kit/presets.json # our classes: variant/size recipes (generated from presets/components/*.json)
themes/latte/kit/ui/          # Latte middle layer: Block, Title, Card*, Badge, Alert … (legacy prop API → v3 + kit())
themes/latte/twig/kit/ui/     # the same compositions for Twig
presets/                      # layouts × blocks × styles
themes/<slug>/                # WordPress theme instances (latte + press/folio/shop/skin children)
```

| Layer | What | Edit? |
|-------|------|-------|
| Primitives | `ui8kit/ui/**`, `twig/ui8kit/ui/**`, runtimes | No: `npx ui8kit@3 add --all --force`, then `npx ui8kit@3 check` |
| Kit | `presets/components/*.json` → `kit/presets.json`, `kit/ui`, `twig/kit/ui` | Yes: `php tools/ui8kit3/presets.php` |
| Presets / theme | `presets/blocks`, `themes/latte/views` | Yes |

Classes come from `kit('button', {variant: 'outline', size: 'sm'}, extra)` (Latte `{kit(...)}`, Twig `kit(...)`), and are passed to
primitives through `class`. Latte and Twig stay isolated: `latte.zip` ships no `twig/`, `twig.zip` ships no `ui8kit/` and `kit/ui/`.
The legacy codegen bundle (`packages/ui8kit-codegen`, `.workspaces/ui8kit-latte`) is still in the tree as a fallback until the deletion commit.

## Quick start

```bash
# Clone (with submodule when published)
git clone --recurse-submodules https://github.com/alexy-os/wp-fasty.git
cd wp-fasty

# Or: local codegen sibling / UI8KIT_CODEGEN env
bun run ui:primitives
bun run ui:install          # composer → .workspaces/ui8kit-latte/php/vendor

bun run theme:build -- latte
composer install --working-dir=themes/latte
composer install   # PHPUnit / PHPCS tooling
composer test
```

Child skin (no Composer): activate **Latte Skin** after parent.  
Release zip: `bun run theme:pack -- latte` → see [docs/install.md](docs/install.md).

### Local WordPress (Docker / WSL)

See **[.dev/README.md](.dev/README.md)** for the full command list.

```bash
bun run ui:primitives
docker compose up -d
docker compose --profile tools run --rm composer
# → http://localhost:8080  → activate theme "Latte"
```

## Scripts

| Script | Purpose |
|--------|---------|
| `ui:primitives` | Generate Latte bundle into `.workspaces/ui8kit-latte` |
| `ui:install` | `composer install` for Latte + UI8Kit autoload |
| `theme:build` | Sync blocks + emit page views from `presets/themes/<id>.json` |
| `theme:pack` | Release zip → `dist/<slug>.zip` (vendored primitives, no codegen TS) |
| `theme:new` | Scaffold help for a new preset / skin |
| `theme:cache` | Clear Latte `~cache` under themes |
| `test` / `phpcs` | PHPUnit + PHPCS |
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
├── presets/                  # layouts × blocks × styles × parent presets
├── schema/                   # site.manifest.schema.json (v0.2)
├── manifests/examples/       # press / folio / shop site manifests
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

## UI8Kit primitives (v3)

```latte
{include 'button/Button.latte', class: kit('button', ['variant' => 'outline', 'size' => 'sm']), children: 'Go'}
```

```twig
{% include 'button/Button.html.twig' with { class: kit('button', {'variant': 'outline', 'size': 'sm'}), children: 'Go' } only %}
```

Update: `cd themes/latte && npx ui8kit@3 add --all --force && npx ui8kit@3 check` (and the same in `themes/latte/twig`).
Parity: `bun run parity` (Latte ↔ Twig, DOM + class token sets, `tools/parity/compare-classes.php`).

## License

MIT
