# docs/agent — knowledge base for humans and coding agents

**Why here:** `docs/` is already the project's documentation root (shipped with the repo, not with theme zips,
so it never ends up in a wordpress.org package). `agent/` is the part written to be *machine-consumable*:
short, factual, every external claim has a source link and a "verified" date, every repo claim has a file path.
Anything not verified is marked **UNVERIFIED**.

Rules for editing this folder:
1. Only add facts you checked (ran the command / opened the source). Put the date (UTC+3) and the source.
2. Decisions go to `adr-*.md` (one decision record per file, status + date).
3. Keep files short; link instead of copying long upstream text.

| File | Content |
|---|---|
| [requirements.md](requirements.md) | Product vision + design rules (8/4 spacing, tokens, Lucide, UI8Kit) |
| [wporg-theme-review.md](wporg-theme-review.md) | wordpress.org theme requirements, Theme Check (how to run, current results), TUT data |
| [demo-content.md](demo-content.md) | Demo content completeness checklist, image licensing |
| [tooling.md](tooling.md) | Every verification command in the repo, what it checks, CI coverage |
| [adr-001-architecture.md](adr-001-architecture.md) | Architecture decisions (renderer, post-type modules, performance, skins) + current-state audit |
| [ci.proposed.yml](ci.proposed.yml) | Proposed CI workflow (not yet applied — needs `workflow` token scope) |
| [schema/theme-manifest.schema.json](schema/theme-manifest.schema.json) | Draft JSON Schema for external generation (form / CLI / MCP) |
| [schema/example.manifest.json](schema/example.manifest.json) | Example manifest validating against the schema |
