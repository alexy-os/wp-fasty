# ADR 006 — One-run diagnostics and bounded cancellation

Status: accepted and implemented in Agent 0.1.1. No diagnosis of the original provider/transport failure is claimed.

## Evidence and scope

The first Windows live request returned generic HTTP 502 after approximately 90–95 seconds. Local audit reached `run_created`; official offline SDK `Agent.getRun` subsequently returned `running`, with no duration, error or usage. This is a persisted snapshot after a killed process, not proof of current remote execution, successful inference, or zero cost. The sandbox script warning is unrelated to the backend failure: iframe scripts remain prohibited.

SDK 1.3 supports both `run.stream()` and `run.wait()`. The original adapter awaited stream completion before observing the wait result, discarded unsuccessful terminal result errors, and recorded only success. Therefore the evidence does not distinguish a stalled stream, pending inference, SDK failure or watchdog termination.

## Decision

- Observe **the same Run** with independent stream and terminal wait; never issue another send, resume, retry or model request. A finite stream has a bounded 25ms drain after successful wait; an indefinitely open stream cannot mask a terminal result.
- Record terminal metadata before status validation, including `error`/`cancelled`, known SDK error code and actual structured usage when supplied. Preserve failed-turn usage in scoped history; missing usage remains unknown, not zero or a price estimate.
- Inner execution deadline remains at most 90 seconds and now shares the parent's start deadline, leaving the existing 95-second watchdog a cleanup window. Do not blindly raise either limit. On observation failure/deadline request supported SDK `run.cancel()` for that new run only; allow at most one second for cancellation and one second for close. Record cancellation completion/error/timeout. Never edit SDK persistence or falsely mark its run terminal.
- Parent distinguishes spawn/IPC failure, premature child exit (bounded code/signal), and 95-second watchdog. It kills the isolated child on settlement. This prevents the local SDK child surviving its worker, but cancellation is best effort: remote termination and billing cannot be guaranteed after lost transport. Existing interrupted runs are not cancelled by an update.
- Local `live-audit.jsonl` correlates request, validated run/agent identifiers, elapsed time, closed status/event vocabulary and last supported tool name/status. At most 128 stream-event records per turn. No prompt, tool arguments/results, thinking text, raw errors/stacks, credentials or unrestricted child stdout/stderr. Unknown SDK codes are `present_redacted`.
- Existing authenticated, page-scoped REST boundary exposes only closed actionable failure messages, never audit contents or raw SDK details. No new route, permission, client storage or sandbox relaxation. `diagnostics/1` handshake rejects an old worker before a new model request.
- Release as a new immutable phpfasty-signed Agent 0.1.1 and matching local-wp resource pin. Core, theme, page/history/policy/vault formats are unchanged. No automatic activation/install on a user's machine.

## Verification and remaining limits

Non-live regressions exercise SDK error/cancelled, stream/wait errors, stuck stream and wait, cancellation timeout, parent spawn/exit/watchdog, late messages, redaction and usage persistence. PHP integration tests verify the public error allowlist and unknown-error redaction under the existing access boundary. Real Windows/provider completion is **not** established by these fixtures or green CI.

Owner approved at most one live smoke after this patch, with no automatic retry. If credentials are available only in the owner's local-wp vault, the owner performs it after updating; no credential transfer or vault extraction. Before the smoke review the current page and ask only to rewrite existing block text, preserving structure. Review the proposal/draft, never auto-publish. Collect safe audit stages and actual usage if reported. A failure remains a failure, not an excuse for another paid turn.

Reference: https://cursor.com/docs/api/sdk/typescript (Run stream/wait/cancel and local Agent.getRun).
