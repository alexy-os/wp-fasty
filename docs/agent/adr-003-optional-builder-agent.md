# ADR-003 — optional Builder Agent on the real ModulY lifecycle

Status: accepted and implemented in this change. One codebase, not two Builder forks.

## Prior audit evidence, not a claim of a fresh upstream audit

The owner’s conditional prerequisite is met by `phpfasty/wpmodules` main
`0eb0cc71875760512445320c7c36d685b0901d6f`:
[engineering audit](https://github.com/phpfasty/wpmodules/blob/0eb0cc71875760512445320c7c36d685b0901d6f/docs/audit-2026-11-04.md),
[completed DoD](https://github.com/phpfasty/wpmodules/blob/0eb0cc71875760512445320c7c36d685b0901d6f/docs/definition-of-done.md).
This records all eight domains, Symfony PHPCS plus WordPress security, no-baseline
PHPStan, Deptrac, 138 tests / 638 assertions and 96% mutation score. Later safe-mode
query-token removal is commit `3bb39bd17f6529171092beeb455c3ec680a50a78`.
The published host embeds core `e49ff18a7e3356f99d32802ba7adc79966873149`.
Green wp-fasty CI alone would NOT establish this prerequisite.

## Boundaries and lifecycle

* `plugins/wpfasty-builder` owns manual editing, catalog/schema validation,
  draft revisions/locking, rendering and publication. No Chat routes, worker,
  policy registration or Chat imports are installed with the manual core.
* `PageOperations` API 1 is an explicit core-owned page snapshot / locked draft
  replacement port. It checks page permissions, exact installed variants,
  schema and expected revision. Agent idempotency remains inside the existing
  outer draft lock. Human publication, iframe sandbox/CSP and server-rendered
  apply/refresh behavior are unchanged.
* `modules/builder-agent` implements actual `ModuleInterface` with
  `boot/activate/deactivate(ModuleContext)`. ModulY installs it separately;
  installation does not enable it. Boot checks the active core port version.
  Failure is caught by the real host, leaving manual Builder available.
* Chat, policy/skills, local map and worker resources have ONE source in that
  module. No duplicate legacy implementation remains in the plugin.
* Core has a generic optional UI factory contract, not a Chat import. The Agent
  is a separate IIFE/CSS build and mounts only when enabled. Authenticated fixed
  asset routes support a private/custom `MODULY_DIR`, not just public plugin URLs.
* Disable rejects still-loaded callbacks, filters Agent REST endpoints, removes
  its feature and serves no Agent assets. Re-enable supports ModulY’s already
  booted-instance lifecycle. No uninstall/data migration is needed: existing
  `_wpfb_*` page history and `wpfb_agent_policy_v1` keys remain unchanged.
* Deptrac forbids core adapters from depending on Agent layers. All module PHP
  uses the existing Symfony + WordPress security rules, maximum PHPStan and
  Rector configuration. No broad ignores/baselines were introduced.

## Request boundary

The actual ModulY static scanner documents `@moduly-request-handler` for
publisher-reviewed request adapters. ONLY `WordPressInput` carries that marker.
It allows fixed query selectors and fixed host FastCGI names, not arbitrary
filesystem/environment reads. Form access verifies a WordPress nonce in this
adapter; callers enforce capabilities and strict JSON/settings validation.
Opaque host values are not blindly unslashed. Narrow security-sniff exceptions
are explained at the exact boundary expressions. The scanner itself is unchanged,
and actual package installation is tested through it, not a stub installer.

## Worker/resource compatibility and release boundary

Authenticated `/status` advertises protocol `wpfasty-agent/1` and capabilities
`local-search/1`, `policy/1`. PHP preflights them before any turn/history/progress
request. Old/malformed workers fail closed with an Agent-only explanatory error;
manual editing still works. No provider call is needed for the handshake.

`worker/resource.json` declares Node 24, `server.mjs`, locked npm dependencies,
health protocol and no network search. It is a resource description, NOT a claim
that the existing local-wp manager consumes this new manifest.

**Coordinated rollout is required:** `fastygo/local-wp@ea79b83` still pins worker
`873b00d35fca94fed161d690bc7215c960def321`, old
`plugins/wpfasty-builder/worker` path and an eight-file list. It cannot provision
this new module/map/policy bundle. A separately authorized local-wp release must
refresh the pin, resource path and complete file set, then refresh installed
resources/dependencies. Preserve the existing external `WPFB_WORKER_DATA` directory
and history database on that future rollout; never place them inside the module
or delete them when replacing resource code. This change does not modify that repo, an installed
manager, the owner’s vault or machine. Do not deploy just the new core expecting
an old worker to acquire new capabilities automatically.

## Verification and packaging

Tests cover core alone / ModulY without Agent / enabled / disabled / re-enabled,
Node unavailable, old resource mismatch, policy/history preservation and manual
page operations. The real published ModulY ZIP is SHA256 pinned in CI; upstream
replacement fails closed and requires reviewed fixture refresh.

`bun tools/builder/pack-agent.ts` uses the actual published SDK Signer/Packer,
allowlisted Git-tracked source/assets/resource files (stage new source before a local fixture build), excludes untracked/hidden files, npm modules/tests/runtime data,
and reads no signing key. Its output is explicitly an **unsigned DEVELOPMENT
fixture** for an isolated CLI WordPress process using upstream’s development
unsigned mode. It is NOT a trusted production release or delivered user ZIP.
Production requires the existing publisher’s signing/trust release pipeline;
no key generation/access or production trust bypass is part of this change.
Playground/core packages include manual Builder only, not a pretend running Agent.

Non-live fixture model answers are labelled NON-LIVE. Windows resource launch,
owner GUI and actual SDK/model/provider turns remain unverified. A fresh visual
browser acceptance is separate; PHP and static bundle tests are not screenshots.

Related: [governance ADR](adr-002-builder-agent-governance.md),
[roadmap](builder-agent-roadmap.md), [engineering checklist](builder-chat-engineering-checklist.md).
