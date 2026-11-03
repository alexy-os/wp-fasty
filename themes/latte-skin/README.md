# Latte Skin

Demo **child theme** for `themes/wp-fasty`.

- No `composer.json`, no `vendor/`, no `classes/`
- Does **not** boot `Application`
- Overrides `views/blocks/hero-centered.latte` and its Twig twin `hero-centered.html.twig` (title uses `text-primary`), so it renders the same with either engine (parity suite `skin`)
- Optional token overrides in `assets/css/tokens.css`
- Demo filter on `wpfasty_context`

## Activate

1. Install and activate parent **Latte** (with `vendor/`).
2. Appearance → Themes → activate **Latte Skin**.

Parent `functions.php` still boots the engine; this skin only filters context and supplies view/CSS overrides.

See `docs/theme-authoring.md` for building your own Latte / Twig theme against the view profile view/v1.
