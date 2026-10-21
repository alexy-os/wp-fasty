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
| Site manifest schema | **Enforced (CI)** | `bun run manifest:validate` — see [Site manifest v0.2](#site-manifest-v02) |
| Preset schema | Stub | Validate `presets/themes/*.json` (layout/style/templates exist) |
| `blockonce` on presets | **TODO** | One Block landmark per `presets/blocks/*.latte` |
| ui8px on presets | **TODO** | Lint `class:` strings in preset Latte (+ theme views) |
| ARIA twin IDs | **TODO** | Sheet trigger/panel/title IDs consistent in shell presets |
| Context schema | Soft | `context.schema.json` ↔ `ContextFactory` keys |
| No WP in Latte | Review | Ban `get_*`, `have_posts`, etc. in `.latte` |
| No hand-edit primitives | Review | Diff / CI: `.workspaces/ui8kit-latte` only from generator |

## Site manifest v0.2

One JSON file describes a whole generated site; no generator consumes it yet (cycle K). Schema:
[`schema/site.manifest.schema.json`](../schema/site.manifest.schema.json) (draft 2020-12). Examples that reproduce the
current child themes: [`manifests/examples/`](../manifests/examples/) (`press`, `folio`, `shop`).

| Section | Content |
|---------|---------|
| `manifestVersion`, `engine` | `"0.2"`; `latte` \| `twig`. `twig` ⇒ `output.wporg` false (the wordpress.org parent is the Latte-only zip) and `builder.enabled` false (the builder compiles Latte); the child ships only `.html.twig` views and the package carries the parent Twig build |
| `archetype` | `press` \| `folio` \| `shop` \| `blank` — child code family (PHP behaviour, view models, views) |
| `theme` | name, slug, textDomain (= slug), prefix, namespace, description, version, uri, author (default `alexy-os`), requires, testedUpTo, license, tags |
| `parent` | `preset` (`presets/themes/<id>.json`), `layout` (`classic` \| `app-shell`), `style` |
| `features`, `labels` | comments, fullWidth, background, blogIndexTitle; copy overrides (readMore) |
| `brand` | radius, fonts (body/display), `light` / `dark` shadcn token sets (oklch / hsl / hex) |
| `settings` | `currency` (ISO 4217); money fields are integer minor units (cents) of it |
| `postTypes[]` | labels, public, adminParent, createInAdmin, slug, archiveSlug, icon, rest, supports, metaPrefix, `fields` (type ∈ `Field::TYPES`: string, text, url, email, integer, number, money, boolean, date), metabox, `taxonomies`, `capabilities` (purchasable / orderable), `archive`, `views`, adminColumns |
| `templates`, `views` | template → `blocks/*` from presets; view files the child owns |
| `builder` | `enabled`, `landings[]` — `{file}` or an inline Seeder spec (page, front, menu, layout of catalog blocks + bindings) |
| `output` | `wporg`, `typesPlugin` (`companion` mandatory when `wporg`), `includeDemo`, `playground` |

Names are platform-neutral (`archiveSlug`, `rest`, `order` instead of `has_archive`, `show_in_rest`, `page-attributes`);
the generator maps them.

`bun run manifest:validate [file] [--repo]` (`tools/manifest/validate.ts`, ajv):

1. **Schema** — strict draft 2020-12, formats.
2. **Rules** — textDomain = slug; parent preset/layout and template blocks exist; unique post type / taxonomy names;
   capability fields exist with the right type (price/sale `money`, stock `integer`); purchasable ⇒ `settings.currency`;
   `adminParent` declared; archetype contract (folio needs `project`, shop needs purchasable `product` + orderable
   `shop_order`); landings: Seeder shape, ≤ 1 front page, unique slugs/block ids, block + variant exist in the builder
   catalog, data sources need their post type / taxonomy.
3. **Repo parity** (`--repo`, always for the examples) — the manifest equals `themes/<slug>`: style.css headers,
   `tokens.css` (`:root`, `.dark`), the set of `views/**.latte`, `types/*.php` (dumped by `tools/manifest/dump-types.php`
   without WordPress), and feature switches in `functions.php`.

### Generator round-trip

`bun run theme:roundtrip` regenerates each `manifests/examples/*.json` whose archetype has a package in
`presets/archetypes/` and diffs it against `themes/<slug>`: same file set, identical bytes; only the `.pot`
`POT-Creation-Date` and `X-Generator` lines are normalized. CI `wordpress` also runs Theme Check on the generated
theme (0 REQUIRED). Packaged today: press, folio, shop.

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
