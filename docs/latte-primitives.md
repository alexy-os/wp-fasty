# Latte primitives (Codegen)

Authoritative details also in Codegen `docs/` (`runtimes.md`, `php-complex-parts.md`, `latte-bundle.md`).

## Inventory

| Metric | Value |
|--------|-------|
| Bricks | 33 |
| Parts | 63 |
| Latte templates | **60** |
| Skipped | **3** |

### Skipped (use `Classes::*` + manual markup)

| Part | Reason |
|------|--------|
| `breadcrumb/Breadcrumb` | `forEach` over typed items |
| `select/Select` | `forEach` over Options (siblings `SelectOption`, `OptGroup` exist) |
| `icon/Icon` | Branched root (svg / text / class) |

Predicate: `src/domain/php-support.ts` — not a hand-maintained tier list.

## Bundle layout

```
.workspaces/ui8kit-latte/
  ui/<brick>/<Part>.latte
  ui/<brick>/*.variants.json
  php/UI8Kit/Rt.php
  php/UI8Kit/Classes.php      # all 63 parts
  composer.json               # ui8kit/latte-primitives
  manifest.json
```

Regenerate: `bun run ui:primitives` (never hand-edit).

## Composition

```latte
{capture $label}Explore{/capture}
{include 'button/Button.latte', variant: 'default', class: 'rounded-full', children: (string) $label}
```

- Children = pre-rendered HTML string (`|noescape` inside the part)
- Nested trees: capture → pass as `children` to outer include
- Loader roots: theme `views/` + `ui8kit-latte/ui`

## Escape hatch

```php
\UI8Kit\Classes::buttonClasses(['variant' => 'ghost', 'class' => 'mt-2']);
\UI8Kit\Classes::iconClasses(['type' => 'class', 'name' => 'home', …]);
```

Twig-style helper in themes that use Twig: `ui8kit_classes('iconClasses', […])`.  
Latte themes call `Classes::*` from PHP or expose a Latte filter/macro later.

## Prop naming (PHP)

Canonical Go PascalCase in defs → PHP: `$variant`, `$class`, `$ariaLabel`, `$for`, …

## Link styled as button

No React `asChild` in Latte. Apply `Classes::buttonClasses(…)` on a manual `<a>` (same as Go `ButtonClasses`).
