# WP FastY

WordPress theme **generator** — Latte templates, UI8Kit primitives, and a
catalog of layout / block / style presets.

> Repository: [alexy-os/wp-fasty](https://github.com/alexy-os/wp-fasty)

## Architecture (UI8Kit 3)

```
themes/wp-fasty/ui8kit/          # UI8Kit 3 Latte primitives (npx ui8kit@3, registry 3.2.0) — unstyled, never hand-edit
themes/wp-fasty/twig/ui8kit/     # UI8Kit 3 Twig primitives (npx ui8kit@3 --framework twig) — never hand-edit
themes/wp-fasty/kit/presets.json # our classes: variant/size recipes (generated from presets/components/*.json; 11 of them are codegen-x kit-compose recipes)
themes/wp-fasty/kit/ui/          # Latte middle layer: Block, Title, Card*, Badge, Alert … (legacy prop API → v3 + kit())
themes/wp-fasty/twig/kit/ui/     # the same compositions for Twig
presets/                      # layouts × blocks × styles
themes/<slug>/                # WordPress theme instances (wp-fasty parent + latte-press/folio/shop/skin children)
```

| Layer | What | Edit? |
|-------|------|-------|
| Primitives | `ui8kit/ui/**`, `twig/ui8kit/ui/**`, runtimes | No: `npx ui8kit@3 add --all --force`, then `npx ui8kit@3 check` |
| Kit | `presets/components/*.json` → `kit/presets.json`, `kit/ui`, `twig/kit/ui` | Yes: `php tools/ui8kit3/presets.php` |
| Presets / theme | `presets/blocks`, `themes/wp-fasty/views` | Yes |

Classes come from `kit('button', {variant: 'outline', size: 'sm'}, extra)` (Latte `{kit(...)}`, Twig `kit(...)`), and are passed to
primitives through `class`. Latte and Twig stay isolated: `wp-fasty.zip` ships no `twig/`, `twig.zip` ships no `ui8kit/` and `kit/ui/`.
The legacy codegen bundle (`packages/ui8kit-codegen`, `.workspaces/ui8kit-latte`) is still in the tree as a fallback until the deletion commit.

## Quick start

```bash
# Clone (with submodule when published)
git clone --recurse-submodules https://github.com/alexy-os/wp-fasty.git
cd wp-fasty

# Or: local codegen sibling / UI8KIT_CODEGEN env
bun run ui:primitives
bun run ui:install          # composer → .workspaces/ui8kit-latte/php/vendor

bun run theme:build -- wp-fasty
composer install --working-dir=themes/wp-fasty
composer install   # PHPUnit / PHPCS tooling
composer test
```

Child skin (no Composer): activate **Latte Skin** after parent.  
Release zip: `bun run theme:pack -- wp-fasty` → see [docs/install.md](docs/install.md).

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
├── schema/                   # site.manifest.schema.json (v0.2); theme-context.schema.json = view/v1 copy from @ui8kit/contract (schema/README.md)
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

Update: `cd themes/wp-fasty && npx ui8kit@3 add --all --force && npx ui8kit@3 check` (and the same in `themes/wp-fasty/twig`).
Parity: `bun run parity` (Latte ↔ Twig, DOM + class token sets, `tools/parity/compare-classes.php`).

## Security

Run WordPress core **7.1.4 or newer**: 4.7–7.1.3 have a remote code execution flaw,
[CVE-2026-119977](https://www.tenable.com/cve/CVE-2026-119977), fixed in 7.1.4. The themes keep their
`Requires at least` header (they run on older cores), but CI tests only on 7.1.4+ and fails on an older core
(`tools/qa/wp-min-version.sh`).

## License

Copyright (C) 2025–2026 alexy-os.

GNU General Public License v2.0 or later (`GPL-2.0-or-later`) — the whole repository, including the themes and the `wpfasty-builder` / `wpfasty-types` plugins. See [LICENSE](LICENSE).
