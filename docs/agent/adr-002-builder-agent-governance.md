# ADR-002 — Local search, governed policy and evidence-driven evolution

Date: 2026-11-07 (Europe/Moscow, UTC+3).
Status: **accepted architecture direction** (owner t188/t189/t197), with explicit implementation boundaries.
Companion: [staged roadmap and acceptance](builder-agent-roadmap.md).
Supersedes no renderer or sandbox decision in [ADR-001](adr-001-architecture.md).

## Context

The Builder needs bounded catalog context, configurable instructions and a route to improve selection without
letting feedback, a second model or user-generated blocks bypass runtime safety. Stock metadata is richer than
the installed WP registry. Query niches are not structural taxonomy. Model confidence is not design truth.

## Decisions

1. Ship a pinned, licensed, versioned immutable Stock snapshot + pure facet engine in the plugin worker.
   Update only in plugin releases, allowing growth to about 3 MB with validated limits. No updater/network
   fallback/MCP service. Search filters against installed block/variant/target before exposing candidates;
   exact details before changed proposals; no fictitious no-match substitutes. Snapshot is data, not instructions.
2. Four instruction layers: Platform immutable in code, Builder product, Site, Page. Versioned draft/active,
   explicit human activation, rollback by new version, CAS and bounded history. Separate policy capability and
   minimal Prompt engineer role; own-page rights do not grant global settings, secrets or publishing.
   Progressive enabled skills are app-level data; code intersects tools, never prose-granted permissions.
   Lock provenance per turn/replay; effective preview must match the actual assembler and label omitted runtime data.
3. Optional future Jev is a **typed advisor**, not authority: shortlist choice/score/noul, no generated explanation
   or automatic tools. Main agent may disagree/requery/clarify. Versioned candidate validation, budgets/privacy,
   real usage, local fallback and expert evaluation precede activation. No Jev integration in this implementation.
4. Future local evidence dataset records decisions, uncertainty, disputes, critiques, applies and subsequent
   corrections with provenance. It is not instruction memory or model training. Independent analysis proposes
   changes; regression evaluation and human activation are mandatory. No autonomous self-modifying policy.
5. Expert assessors + moderator cabinet define scenario rubrics and preserve disagreement. Webmaster votes,
   popularity and Stock demand/use/edit/no-hit signals are evidence, not truth. All external submissions are
   voluntary, previewed, minimized/redacted and rights-checked; no default telemetry. Local retention/export/
   deletion must cover derived stores/backups and honestly state external deletion limits.
6. Personal blocks progress props → preset → validated spec/recipe build using canonical UI8Kit contracts,
   not arbitrary executable code. Personal registry outside plugin updates: stable namespaced IDs, versions,
   provenance, schema+renderer compatibility, atomic install/index publication, retained private originals.
   Future separate bounded Node validation worker under local-wp checks rules/render/responsive/a11y with
   actionable failures and bounded repair. PHP validation and restrictive sandbox remain authoritative.

## Implementation status

- **Implemented:** decisions 1–2 in `cc04c725a76d057988cc099d1ea974d2decaea39` and `cc5eb0f`.
  See [search](builder-local-search.md), [policy](builder-agent-policy.md) and repository tests/CI.
  Owner's installed worker resource is not refreshed by these commits.
- **Planned only:** decisions 3–6. This ADR authorizes documentation, not runtime integration, uploads,
  dataset collection, new services or arbitrary user-code execution. Separate implementation approval required.
- Current feature-split props already solve left-media grid/cards/metrics 4:3; stats ratio is hardcoded 1:1.
  Generic AST runtime and personal registry are not present. Roadmap links actual template sources.

## Alternatives and consequences

- Remote MCP/search/updater rejected for current small catalog: availability/version complexity without inherent
  relevance improvement. A downloadable metadata map cannot supply absent renderer code or confer licenses.
- Full catalog prompt dumping rejected: shortlist + selected details reduce context without dropping PHP guards.
- Forced advisor choice and automatic “learning” activation rejected: reinforce errors, bias and escalation risk.
- Writing user assets into plugin directories rejected: upgrade loss and inconsistent permissions.
- Local-first costs some lexical/semantic recall and requires explicit worker-resource refresh with releases.
  Future advisor adds network/privacy/billing risk; evaluate against expert labels, not published confidence alone.
- Dataset/privacy/validation features need real implementation/tests; documentation is not compliance certification.

## Verification and references

[Roadmap](builder-agent-roadmap.md) owns dependencies, acceptance and official TypeSafe references verified
2026-11-07 (UTC+3). Existing PHPCS/PHPStan/Deptrac/Unit/WP/editor/worker/build gates continue unchanged.
This documentation was written without model/API calls, secret access or changes to local-wp/user machines.
