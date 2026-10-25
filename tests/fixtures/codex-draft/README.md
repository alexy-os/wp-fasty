# codex-draft — draft, заменить на vectors из @ui8kit/contract

Draft vectors until ui8kit/contract publishes the normative ones (XGenBot owns the contract; nothing here is committed there).

- `readpath.json` — readPath / pipes, generated from the TS reference: `bun tools/codex/readpath-vectors.ts`
  (codegen-x `runtimes/core/assets/app/core.ts` + the Q4 draft pipes `excerpt-fallback`, `reading-minutes`,
  `term-names:`, `settings:`, `in-stock`). Run by `tests/CodexReadPathTest.php` against `WPFastyBuilder\Codex\ReadPath`.
  `{"undefined": true}` = the TS result is `undefined` (PHP `null`).
