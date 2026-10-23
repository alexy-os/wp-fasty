# AGENTS.md — WP FastY

- Primitives are **UI8Kit 3** (`npx ui8kit@3`, registry 3.2.0): `themes/latte/ui8kit` (Latte), `themes/latte/twig/ui8kit` (Twig).
  They are unstyled and generated: never hand-edit; update with `add --all --force`, verify with `npx ui8kit@3 check` in both dirs.
- Styling lives in the kit: `presets/components/*.json` → `php tools/ui8kit3/presets.php` → `themes/latte/kit/presets.json`;
  use `kit('<component>', {variant, size, …}, extra)` and pass the result as `class`.
- Middle layer (legacy prop API): `themes/latte/kit/ui` and `themes/latte/twig/kit/ui`. Twig includes of kit compositions use `only`.
- Engines are isolated: no Twig in latte.zip, no Latte in twig.zip (CI greps enforce it).
- Checks before commit: `bun run test`, `vendor/bin/phpcs`, `bun run lint:design`, `bun run parity`, `php tools/ui8kit3/presets.php --check`,
  `npx ui8kit@3 check` (both dirs), `bun run theme:pack -- latte` and `-- latte --engine twig`.
- The builder plugin blocks (`plugins/wpfasty-builder/sources/ui8kit`) are still xgen-generated with their own runtime (not yet v3).
- Registries: build and verify locally; never overwrite a published version or `latest` on registry.buildy.tw.
- Rules: `.cursor/rules/` (see `ui8kit3.mdc`).
