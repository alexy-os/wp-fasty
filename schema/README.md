# schema/

- `site.manifest.schema.json`: site manifest, schema v0.2.
- `theme-context.schema.json`: the template / SSG context, view profile **view/v1** (`$id` `https://schema.buildy.tw/view.schema.json`).
  - It is a byte copy of `@ui8kit/contract` `schemas/view.schema.json`. The package is pinned exactly in `package.json` and recorded in `VIEW_SCHEMA_SOURCE`.
  - It is committed so that https://raw.githubusercontent.com/alexy-os/wp-fasty/refs/heads/main/schema/theme-context.schema.json keeps working.
  - Do not edit it here. CI runs `tools/qa/view-schema-sync.sh`, which fails on drift. To update: `bun add -d --exact @ui8kit/contract@X`, set X in `VIEW_SCHEMA_SOURCE`, then run `tools/qa/view-schema-sync.sh --update`.
- `examples/*.json`: one context per page type (`{kind, context}`), validated in CI (`ContextSchemaTest`, `php tools/qa/context-schema.php schema/examples`).
