# Builder chat engineering increment — 2026-11-07

Scope: existing WordPress Builder, its local Node SDK adapter and admin chat.
This is not a theme rewrite, a new provider, a vault change or production/live
agent acceptance. No model requests are needed for the checks below.

## Direction and ownership

`WP REST + nonce/capability → authenticated loopback worker → isolated SDK child`

- WordPress owns site/page scope, authoritative catalog, per-page proposal metadata,
  optimistic draft revision, explicit Apply and manual Publish.
- The SQLite turn store owns durable content/answer, request-id replay and final
  reported usage. Retry never creates another send for the same completed request.
- The SDK adapter owns model identity and observes **the same run** via stream().
  It does not forward arbitrary SDK payloads or call a separate billing API.
- `worker/telemetry.mjs` and PHP `ChatTelemetry` are pure allowlisted DTO serializers.
  `AdminPalette` is a pure conversion from registered host colour data to two
  validated CSS tokens. Neither pure PHP class depends on WordPress, persistence,
  the SDK, globals, clocks or HTTP. Their exact dependency exclusion is a blocking
  `BuilderContract` Deptrac layer; global WP function calls are checked separately
  by `tests/ArchitectureTest.php`.
- The existing `Chat` remains a static WordPress adapter/orchestrator. We did not
  introduce a parallel session service or replace the real Latte/Twig renderer.

## Checklist and evidence

| Requirement | Implementation / reproducible check |
| --- | --- |
| Strict PHP contracts, no mixed casts that warn on arrays | Chat validates incoming id/message, narrows WP meta/options through existing Cast helpers, validates JSON encoding and worker response shape; generic DraftGuard lock/save contracts. `composer phpstan` maximum level, zero errors and no new ignore/baseline. |
| Domain/adapter direction | `composer deptrac`; new pure DTO/palette contracts cannot depend on the WP adapter. `composer test` catches global WP function use outside that layer. |
| Security boundary | GET progress uses same per-page edit capability and nonce as history/apply, not lifecycle /status. Host/site/page/request are never supplied by the browser to choose a worker target. Fixed authenticated 127.0.0.1 only. |
| Least disclosure | Worker callback, child IPC, persistence and PHP all allowlist public telemetry; tool args/results/thinking/arbitrary names are dropped. Counts are finite bounded non-negative integers. No keys in JS/bootstrap/history/usage/progress. |
| Durable truth | Missing or old usage is unavailable, not zero; reported zero is retained. Final cumulative SDK usage is persisted once and replayed unchanged. No invented prices/cost estimates. Existing application metadata remains the only applied-state authority. |
| Real progress, no simulated tools | Only SDK tool_call events for search_catalog/get_block_details/get_skill/propose_layout (legacy catalog_lookup accepted for old history). Latest event replaces previous; final answer replaces progress. Unknown/unavailable observation is explicit, never a timer claiming tool work. |
| Race/cleanup | One GET in flight, exact request-id check, monotonic event sequence, terminal cleanup; unmount, timeout and errors abort observation, never resubmit a paid turn. Worker drops transient tool state on failure/success. |
| Safe content rendering | Chat composition is included in the blocking UI8Kit source gate. Small Markdown subset via escaped UI8Kit primitives, no raw model HTML or active Markdown links. JSON is losslessly lexed to escaped spans, bounded scrolling and 11px monospace. |
| Native admin UX | Scoped editor-only WP gutters removed; WordPress menu/topbar stay intact. Selected registered admin colour scheme supplies accent with contrast-selected foreground; all chrome uses tokens. Manual dark mode remains optional, canvas branding stays separate. |
| Accessibility | Named transcript, keyboard scrolling, polite current activity status, native buttons/textarea, focus ring, contrast-selected accent foreground. User and assistant cards/labels differ without relying on colour alone. |
| Preview integrity | Existing 7473a67 revision-aware real iframe Apply refresh remains; sandbox stays allow-same-origin only, with no allow-scripts/CSP relaxation. |
| Regression checks | NON-LIVE SDK fixtures test one send, allowed events, no secrets, cumulative usage, persistence/replay, child IPC, auth and site/page/request isolation; editor tests cover escaping and observation cleanup. Own WP HTTP fixture tests capabilities/nonce/progress/apply/revisions/reload/offline; disposable draft only, not saved live draft12. |
| Build reproducibility | Pinned Bun 1.4.4 and editor lockfile; build twice and compare tracked assets. Full existing CI plus maximum PHPStan and pinned Node24 NON-LIVE worker tests are blocking. |

## Deliberate gaps / not acceptance claims

- No new paid turn was authorized or sent. Real SDK 1.3.0 types/implementation
  define the telemetry/events; injected contract tests are explicitly NON-LIVE.
  The earlier genuine saved Grok reply may therefore show usage unavailable.
- Windows SDK/runtime and live in-flight UI remain unverified. Progress polling
  needs at least two concurrent PHP request slots; a single-slot host queues it
  behind the turn. Do not silently claim streaming works there or expose Node to
  avoid WP authorization. `worker/README.md` records this requirement.
- No actual cost is shown: reliable run token counts suffice; billing can lag and
  no trustworthy actual per-turn cost was fetched. Jev search remains unavailable.
- This increment isolates telemetry/palette and tightens touched chat transport
  contracts, not every historical static WP adapter. Further application service /
  port extraction should be incremental, not a giant unreviewed Chat rewrite.
- Browser visual/keyboard/mobile/palette acceptance is a separate real-browser
  check, not proven by static/source/unit tests. Do not present fixtures as live.
- The host must update its worker resource together with this source. The plugin
  cannot make an older local-wp resource pin contain a new worker module.

## Follow-on: release-only local retrieval + Agent policy

- See `builder-local-search.md` and `builder-agent-policy.md` for pinned MIT source,
  measured NON-LIVE retrieval bounds and reproducible commands.
- `catalog_lookup` was replaced by bounded `search_catalog` + exact installed
  `get_block_details`; historical progress names remain accepted. `get_skill`
  progressively loads enabled versioned app-level instructions, never permissions.
- New `AgentPolicyDefinition` is a pure, blocking BuilderContract domain; WordPress
  roles/nonces/versioned options+meta/atomic CAS/UI stay in the AgentPolicy adapter.
- Global/site and per-page active snapshots, skill/tool intersection before SDK
  creation, audit provenance, no auto-publish, policy preview golden parity,
  unauthorized/CSRF/XSS/CAS/retention/page-isolation regression tests added.
- No direct SDK systemPrompt override, network search/updater, vault/schema migration,
  production Windows/live-model/browser acceptance or owner's resource update claimed.


## Optional Agent extraction (ADR-003)

See [actual boundaries, prior ModulY audit evidence and rollout limitations](adr-003-optional-builder-agent.md).
Regression fixtures use the real published ModulY installer/kernel, not a fabricated
module API. Core-alone and disabled/offline Agent preserve manual editing and existing
policy/history. Symfony/security rules and the immutable sandbox stay enabled.
Production signing, refreshed local-wp resources and Windows GUI verification remain
release prerequisites rather than silently assumed test coverage.

Local extraction evidence: 498 PHPUnit tests / 7723 assertions; 112 editor tests;
26 NON-LIVE worker tests; maximum PHPStan, Deptrac zero violations and Rector clean;
Symfony PHPCS zero errors (existing WordPress/style warnings remain visible).
Both core/Agent JS+CSS bundles rebuild byte-identically. Renderer parity passes,
482 variants per engine match render-block fragments; UI8Kit source, 964 block
cases and 86 theme cases report zero problems. Real ModulY install/enable/disable/
re-enable and PHP→Node HTTP fixtures pass with only expected success audit records.
These are local deterministic results; GitHub's final run remains the CI authority.
