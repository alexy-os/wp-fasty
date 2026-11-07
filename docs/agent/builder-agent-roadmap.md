# Builder agent roadmap — local foundation to governed learning

Recorded: 2026-11-07 (Europe/Moscow, UTC+3). Owner decisions t188–t197.
Architecture: [ADR-002](adr-002-builder-agent-governance.md).
This is a plan, not a claim that all features ship. Only Foundation below is implemented;
future milestones require separate implementation approval, evaluation and acceptance.
No Jev calls, dataset collection/export, assessor service, personal-block runtime or new worker were added by this documentation.

## 0. Foundation — implemented in repository, not installed on the owner's machine

- Local search: `cc04c725a76d057988cc099d1ea974d2decaea39`.
  [Contract, source/license and measurements](builder-local-search.md).
  Immutable bundled Stock map and pure local facet engine; updates **only with plugin releases**.
  No separate updater, HTTP/MCP search or network fallback. Growth to about 3 MB is acceptable;
  changing the current bounded file-size/schema limits remains a tested release decision.
- Policy/skills: `cc5eb0f` ([contract](builder-agent-policy.md)). Four layers:
  immutable Platform → Builder product → Site → Page brief. Draft/active versions,
  explicit activation, rollback as a new version, bounded history, CAS conflict checks.
  Administrator/least-privilege Prompt engineer manages site policy; page authors only their permitted briefs.
  Progressive `get_skill` loads only enabled versioned registry entries. Tool intersection is enforced in code;
  these are app-level instructions, not an SDK system-prompt override or additional OS/account permissions.
- Search passes only installed `blockId + variant + target` candidates to the model. Current Stock
  112 block specs/532 variants vs installed 93/482: exclude 50 unavailable variants and 80 screen entries.
  `search_catalog` yields a compact shortlist; `get_block_details` supplies selected authoritative schemas.
  Changed blocks require inspected details before `propose_layout`. PHP remains authoritative at apply.
- Semantic gap is real: “sneakers”/“sushi” may mean hero, product cards or menu content, not a new structural block
  or a valid industry enum. Agent may reason, clarify and requery supported vocabulary; no-match is explicit,
  never fabricated blocks/facets. Lexical synonyms are not a semantic model.
- Sandbox/CSP, schema/revision guards, explicit human apply/publish separation and existing telemetry remain.
  Active policy/brief and loaded skill versions are recorded per turn; old history invents no provenance.
- Acceptance evidence: deterministic non-live worker/editor/PHP tests, real disposable WP integration fixtures,
  source/build/architecture gates and CI. This does not prove new live-agent behavior or Windows operation.
  Plugin/worker releases must ship the whole worker resource (catalog, engine, policy files); updating the plugin
  does not silently refresh an already installed local-wp resource. No new ZIP is delivered in this workstream.

## Theme inheritance — accepted t211, implemented independently of Agent

[ADR-004](adr-004-theme-appearance-inheritance.md), [theme author contract](../theme-authoring.md#7-one-appearance-source-for-live-pages-and-builder).
Live Builder pages/draft iframe inherit active theme tokens and actual supplied font enqueues through the same
WordPress theme pipeline; admin appearance stays native. Preview light/dark is server-rendered and transient,
with unchanged sandbox and no Agent/ModulY/Node requirement. Existing WP Additional CSS is update-safe and scoped
to the exact active child; no implicit transfer to another theme. Import/export of tokens remains **future only**;
no SkinDefinition engine, catalog skin indexing or new settings/updater service is part of this implementation.

## 1. Optional Jev advisor and local evidence dataset — future

[ADR-005: все 12 сценариев Jev для оркестратора](adr-005-jev-orchestrator-scenarios.md) сохраняет точные
названия/идеи owner Page и отделяет будущие адаптации от API и реализованных функций. Это proposed scope,
не включённая интеграция: главный агент вправе отвергать advice и уточнять/повторять запросы.

### Jev decision adapter (not a replacement search provider)

Use local retrieval first, then optionally ask TypeSafe about an ambiguous shortlist of roughly ten compatible
blocks. Typed `choice`, `score`, `noul` questions are advisory: closed candidate IDs, rubric levels and a
“does any candidate fit?” check. The main agent can disagree, ask clarification, or requery with refined criteria;
record the decision, not a forced “Jev selected therefore apply” rule. No free-text explanation generation,
no automatic tool execution and no model-generated tools/permissions. Probabilities/confidence are model outputs,
not guaranteed correctness, calibrated quality for this task, or permission to bypass human review.

Official references verified 2026-11-07 (UTC+3):
[System One request/response](https://docs.typesafe.ai/api/system-one.md),
[models/limits](https://docs.typesafe.ai/models.md),
[model jaggedness](https://docs.typesafe.ai/model-jaggedness.md).
`POST /v1/systemone` accepts model, state and named questions; response includes typed answers, actual model
and input/output token usage. Choice names/descriptions are a closed set (API maximum 255); score has 1–10 ordered
levels, noul is probability of yes. These API maxima are not recommended Builder shortlist sizes.
Documentation currently lists `jev-1.14.0`, 64k request/32k question context, $0.042 per million input tokens,
output free; aliases can move. These are dated published terms, NOT measured Builder costs or a purchase approval.
Use a pinned evaluated version; actual model/usage recorded, unavailable metrics remain unavailable.

Before implementation: explicit opt-in remote processing; minimize/redact state (no keys, private URLs or full
history by default); server-side vault credentials only; candidate/result schema and ID validation against the
same installed catalog version; local-only operation when disabled, unavailable, timed out or over budget.
Define request/token/time budgets, bounded retries, cancellation and cache keys including catalog, rubric,
policy and model version. Distinguish transport/auth/schema errors from genuine no-fit; don't cache errors as
judgments or allow late replies to override a completed local decision. No automatic install of missing blocks.

Acceptance: mocked typed results/no-fit/invalid IDs/timeouts, deterministic replay/provenance, budgets/cache
invalidation and disabled-network tests, then separately authorized live evaluation against expert-labeled cases.
No promised latency/accuracy improvement until measured.

### Local versioned evidence, not hidden memory or training

A proposed dataset event references scenario/page/turn IDs, consent scope and versions of schema, policy,
skills, catalog, target, main model and advisor model. Minimized evidence may include:
- shortlist/query, no-hit and uncertainty; clarification questions and answers;
- Jev question/rubric/result/usage and main-agent accept/disagree/requery decision;
- votes, professional critique, disputed labels and alternative candidates;
- proposal, explicit apply, observed success/failure and **later corrections**, not just initial acceptance.

An apply or webmaster vote is a signal, not proof of design quality. Keep observations, assessor labels,
main-agent decisions and inferred preferences separate. The dataset is neither automatic prompt memory,
model training nor executable instructions. A **separate analysis agent**, if later approved, proposes changes
to policy/skills or derived memory with evidence; offline regression evaluation + human activation is required.
It cannot rewrite active policy, grant tools, publish or silently promote its own recommendation.

Acceptance: append/version/replay fixtures, disputed labels retained, corrections linked, no cross-site leaks,
selective deletion propagates to derived indexes/memory where controlled, analyses never activate themselves.

## 2. Professional assessor/developer cabinet and Stock feedback — future

- Criteria/labels should come from professional design/web assessors and a moderator developer cabinet,
  with scenario-based rubrics, multiple independent assessors, disagreement and adjudication records.
  Do not flatten disagreement into an unexplained average or promote one webmaster's vote to truth.
- Optional Stock signals: unmet demand/no-hit queries, preferences, recommendation usefulness, block use,
  edits and rejection reasons. Popularity ≠ quality; unmet demand ≠ a bad existing block.
  Account for exposure/selection/survivorship bias, niche and accessibility requirements.
- Feedback flows only after explicit voluntary preview/redaction/submission to a named developer recipient;
  **no default telemetry or remote uploads**. Separate collection consent from sharing consent.
- Local default, configurable retention, export, selective deletion and delete-all are requirements to build,
  not features already shipped. Inventory raw records, derived memory/summaries, caches, backups and remote
  recipients. Explain retention windows, backup expiry/restore handling and remote deletion limits; deleting
  a local copy cannot guarantee deletion at an external recipient. No “GDPR compliant” button promise.
- Exclude secrets and unnecessary personal data; check rights/licenses for content, screenshots and datasets.
  Moderation and explicit copyright/licensing approval are required; submission is not automatic ownership transfer.

Dependencies: milestone 1 schema/provenance/privacy controls + expert rubrics before aggregated recommendations.
Acceptance: consent denial sends nothing; exact redacted preview equals payload; moderation and multi-assessor
conflict scenarios; retention/deletion/export tests and documented external-recipient limits.

## 3. Personal blocks and isolated validation — future

Escalate by smallest sufficient change: existing **props → named preset → new spec/recipe + reproducible build**.
Keep canonical UI8Kit primitives, tokens, allowed props and schemas; never execute arbitrary untrusted PHP/JS/CSS.
A generic AST runtime is **not implemented** and is not implicitly authorized by this roadmap.

Concrete current example: `feature-split` already accepts `mediaSide: start`; grid/cards/metrics use 4:3 media,
while stats selects hardcoded 1:1. Reuse props/preset first; a user-selectable ratio on stats requires a tested
source/spec change, not pretending an unsupported ratio prop exists. Evidence:
[`feature-split.latte`](../../plugins/wpfasty-builder/sources/ui8kit/latte/blocks/feature-split.latte),
[`feature-split.html.twig`](../../plugins/wpfasty-builder/sources/ui8kit/twig/blocks/feature-split.html.twig).

A future personal registry must live **outside the plugin update directory**, with user namespace, stable IDs,
immutable versions, source/provenance/license, schema and renderer compatibility. Keep private originals.
Atomic validate → stage → install → index publication; incomplete or incompatible entries never enter search
or an apply candidate list. A preset cannot masquerade as a new renderer-backed spec.

Proposed separate Node validation worker under the shared local-wp manager (NOT built now): bounded jobs,
strict input schema, actual UI8Kit contract/allowed primitives+props, probe rendering, responsive/a11y checks,
time/memory/process isolation, sanitized logs and actionable errors. Schema validity alone is not safety or
visual quality. PHP authoritative draft/apply validation remains; no weakened iframe sandbox/CSP. Repair loop
must have bounded attempts, report exact failures and stop for human decision, not autoaccept endless rewrites.
Optional asset submission to professional moderation is separately consented and licensed.

Dependencies: registry/renderer packaging contract + fixtures + milestone 2 moderation only for shared assets
(private local assets need not be published). Acceptance: namespaced/versioned update survival, path traversal and
injection rejection, target compatibility, atomic failure rollback, budget termination, real responsive/a11y
probes, malicious recipes rejected, original retained, no remote submission without approval.

## Release/decision boundaries

Implemented foundation is not a promise to integrate every future item. Reassess each stage with measured
quality/resource results, security review and owner approval. Keep search purely local unless a new decision
explicitly approves an optional advisor; MCP is not inherently more accurate and is not needed for this MVP.
Shared manager controls future independent workers; no VPS, Docker/Incus or public listener is required here.


### Optional module boundary

Implemented extraction: [ADR-003](adr-003-optional-builder-agent.md). Manual core and
optional ModulY Agent are one codebase. Deployment still requires a trusted-signed
module and a coordinated local-wp resource refresh; neither is silently installed
on a user machine by this source change.
