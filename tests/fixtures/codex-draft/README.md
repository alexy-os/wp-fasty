# codex-draft — draft, заменить на vectors из @ui8kit/contract

Draft vectors until ui8kit/contract publishes the normative ones (XGenBot owns the contract; nothing here is committed there).

- `readpath.json` — readPath / pipes, generated from the TS reference: `bun tools/codex/readpath-vectors.ts`
  (codegen-x `runtimes/core/assets/app/core.ts` + the Q4 draft pipes `excerpt-fallback`, `reading-minutes`,
  `term-names:`, `settings:`, `in-stock`). Run by `tests/CodexReadPathTest.php` against `WPFastyBuilder\Codex\ReadPath`.
  `{"undefined": true}` = the TS result is `undefined` (PHP `null`).
- `golden/latte-{press,folio,shop}/` — Codex REST bytes of the demo sites (every route of `/wpfasty/v1/codex`, entry
  bodies as served), fresh install + packaged demo with a pinned clock (`tools/codex/seed-pinned.php`); the site
  origin is stored as `{base}`. Checked in CI by `tools/codex/conform-sites.sh` (with the schemas and
  `validateCollectionV04` of ui8kit/contract at a pinned commit). Regenerate:
  `CODEX_CONTRACT=<built contract> tools/codex/conform-sites.sh <wp dir> <base url> <demo prefix> --write`.
