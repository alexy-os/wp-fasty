# Latte Skin

Demo **child theme** for `themes/latte`.

- No `composer.json`, no `vendor/`, no `classes/`
- Does **not** boot `Application`
- Overrides `views/blocks/hero-centered.latte` (title uses `text-primary`)
- Optional token overrides in `assets/css/tokens.css`
- Demo filter on `wpfasty_context`

## Activate

1. Install and activate parent **Latte** (with `vendor/`).
2. Appearance → Themes → activate **Latte Skin**.

Parent `functions.php` still boots the engine; this skin only filters context and supplies view/CSS overrides.
