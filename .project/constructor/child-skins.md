# Child skins — product contract

Child themes restyle and override Latte views **without** Composer or a second PHP runtime.

## Layout

```
themes/latte/                 PARENT — Composer, Latte, UI8Kit, PHP runtime
themes/latte-<skin>/          CHILD  — no composer.json, no vendor/
  style.css                   Header: Template: latte
  functions.php               Thin: optional filters only (no Application boot)
  assets/css/tokens.css       Token / skin overrides (optional)
  views/…                     Optional overrides (same relative paths as parent)
  languages/                  Optional child text domain
  screenshot.png              Optional
```

## Rules

Child **must not**:

- Run `composer install` or ship `vendor/`
- Boot `Application::getInstance()` / a second DI container
- Duplicate `classes/`
- Ship codegen TypeScript or hand-edit `ui8kit-latte`

Child **may**:

- Override any file under `views/` (stylesheet root wins)
- Override / enqueue skin CSS (`tokens.css`, `style.css`)
- Filter `wpfasty_context`
- Filter `wpfasty_latte_roots`

## Loader roots (first match wins)

```php
$roots = array_values(array_filter([
    get_stylesheet_directory() . '/views',  // child
    get_template_directory() . '/views',    // parent
    $primitivesDir,                         // UI8Kit
]));
$roots = apply_filters('wpfasty_latte_roots', $roots);
```

WordPress loads parent `functions.php` automatically when a child is active. Parent owns Latte + UI8Kit.

## Example

See `themes/latte-skin/` — demo filter + hero override, zero PHP deps.
