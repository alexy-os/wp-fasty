# Builder local Node worker — candidate, not live/browser accepted

This extends the existing visual Builder, not the separate Creator plugin. No CR,
external Session backend, VPS, or browser provider key is required. WordPress and
worker must share the SAME host/network namespace. The existing Latte/Twig canvas
and authoritative catalog remain unchanged. Nothing publishes automatically.

## Requirements and installation

- Supported target for this increment: Linux, Node 24+, PHP/WP Builder requirements
  unchanged. `node:sqlite` stores durable page conversations. Direct official
  `@cursor/sdk` **1.3.0** is pinned in package-lock.json (SDK itself requires
  Node >=22.13; this worker requires 24).
- From this `worker` directory run `npm ci --ignore-scripts --workspaces=false`.
  Keep platform optional dependencies; do not copy Linux node_modules to Windows.
  Linux SDK import-only smoke passed; no paid inference has been performed.
- SDK publishes a win32-x64 optional package, but this increment has NOT been
  tested on Windows. Native Windows Node24 and Windows PHP must share loopback.
  Windows service setup is manual (the Linux systemd file does not work there).
  A WSL/Docker worker is NOT the same loopback as native LocalWP. Do not open the
  worker to the LAN or disable browser/network security to work around this.
- PHP request/proxy timeouts must allow >100 seconds (worker hard wall 95s).
  On restrictive shared hosting without an always-running Node process, this
  architecture is not supported; a PHP-only plugin ZIP cannot supply Node.

## Server configuration (operator only; do not paste keys into chat)

1. Create an unprivileged worker user and a private directory OUTSIDE the HTTP
   document root, e.g. `/var/lib/wpfasty-builder`, owned by that user, mode 0700.
2. Create `/etc/wpfasty-builder/worker.env` mode0600 with real values locally:
   ```ini
   WPFB_WORDPRESS_ROOT=/var/www/wordpress
   WPFB_WORKER_DATA=/var/lib/wpfasty-builder
   WPFB_WORKER_TOKEN=<independent cryptographically random shared token, >=32 chars>
   ```
   Generate the shared token into a private file (do not print it into logs).
   Never reuse a provider key as this token. Worker data must be outside the real
   web root, not merely a hidden subdirectory. Startup rejects data within the
   declared WordPress root; the operator must declare its real document root.
3. WordPress → **Settings → Builder chat**:
   `/wp-admin/options-general.php?page=wpfasty-builder-chat`.
   Fields: **Cursor API key**, optional **Jev key (reserved; search unavailable)**,
   and **Local worker shared token (at least 32 characters)** matching the env file.
   Keys are non-autoloaded WordPress options. Inputs always blank/masked;
   blank preserves saved values and explicit Clear removes them. Back up/protect
   your WordPress DB; it contains server-side credentials, not encrypted by this
   plugin. Keys are NOT in HTML, editor JS, plugin artifacts or application logs.
4. Install/adapt `wpfasty-builder-worker.service.example`, then start it locally.
   For foreground operation, securely provide those environment variables and
   run `node server.mjs`. No Cursor key is needed in the service environment;
   WP supplies it only in authenticated loopback IPC for a turn.
5. Open a page in the **existing** Builder and select **Chat**. Missing settings
   or an offline worker show deterministic503 errors, not fake assistant replies.

## Actual boundaries and behavior

- Same-origin WP REST: `/wp-json/wpfasty-builder/v1/chat/<page-id>/history`,
  `/turn`, `/apply`. Explicit `wp_rest` nonce + `edit_page` capability, page-only.
  Settings require manage_options and a settings nonce.
- WP alone contacts fixed `http://127.0.0.1:18991`; no configurable upstream,
  redirects, CORS, client worker token, arbitrary network proxy, or shell tool.
  Worker checks exact local Host, rejects Origin, and authenticates every route.
- The worker calls official SDK model discovery and selects only **grok-4.7**.
  It fails explicitly if this key cannot use it. No fallback provider/model and
  no decorative model picker. `tools:['mcp']` exposes only the two in-process
  callbacks; ambient settings and external MCP servers are disabled.
- Catalog lookup returns actual block ids, variants, props schema/defaults and
  data definitions. `propose_layout` yields structural blocks (id/block/variant/
  props/bindings), never arbitrary HTML or filesystem writes. PHP validates
  structure and runs the actual Builder normalizer before explicit draft apply.
- Pending visual saves are flushed before sending/applying. Saves serialize in
  the UI; visual saves and chat apply use a shared DB-unique page lock plus
  expected version. A changed draft rejects stale proposals with409. Collection
  metadata is retained. Legacy external visual-save callers omitting expectedVersion
  remain backwards-compatible and therefore do not gain stale-client protection.
- Every turn has an idempotency key. Completed exact retries replay; different
  content/context conflicts. Failed/interrupted keys are NEVER auto-retried.
  UI preserves the same key for retry of unchanged text. Only editing the message
  explicitly starts a new turn. History can recover a completed proposal after
  a PHP/browser timeout. Applying an already-applied proposal is idempotent.
- Conversations live in `WPFB_WORKER_DATA/turns.sqlite` (+ WAL/SHM); SDK state in
  `sdk-state/`. Scope is persistent WP site UUID + page id. Latest bounded30 turns
  are displayed (older turns retained); bounded10 supplied as context. WP stores
  proposal/application metadata on that page, not in published content.
- Limits: one active turn, 768k input, 256k structural result, 32k answer, 100
  proposed blocks, 16 custom tool callbacks per turn, 95s hard child-process wall, 512MiB V8 heap; sample systemd adds
  process/RSS limits. SQLite main DB max256MiB; WAL and SDK state still need disk
  quotas/retention operationally. No automatic pruning/deletion is implemented.
- After a process crash, a pending turn/page fails CLOSED. Operator must inspect
  the durable row and provider billing/outcome before manually marking it failed
  in a backed-up DB during maintenance. No automated unlock/reissue command is
  supplied. Similarly, a PHP crash can leave `wpfb_draft_lock_<page-id>`; only
  remove it after confirming no save is active. This is a conservative increment,
  not an exactly-once billing guarantee across an upstream process crash.
- Jev search is deliberately **not implemented**: the installed official
  `@grok/agent-sdk` Jev extension documents typed decisions (`jev-latest`,
  `TYPESAFE_API_KEY`), not search. No guessed endpoint or fake search UI is shipped.

## Validation and release gate

`npm test` uses explicitly NON-LIVE injected runners; never a provider.
`tools/sdk/builder-wp-contract.php` is for OUR isolated WP only: it creates and
removes a disposable page, overrides settings/runtime per request, tests actual
catalog draft persistence and never changes saved credentials/theme activation.
Production worker has no mock-mode HTTP flag.

This candidate still needs owner-approved ONE real SDK/Grok turn and actual
browser acceptance: send → structural proposal → apply → same saved revision in
existing Latte iframe → reload history, stale-edit conflict, no automatic publish.
Do not distribute it as live-accepted or remove the old Creator plugin/data yet.

### Later Windows/LocalWP reproducible foreground setup (not run on the owner's PC)

In LocalWP's site shell, confirm the PHP site is actually local to this Windows
host. Locate `<site>\app\public` and the installed plugin's `worker` folder.
Use PowerShell (adapt the two paths):

```powershell
node --version # must be 24+
Set-Location 'C:\path\to\site\app\public\wp-content\plugins\wpfasty-builder\worker'
npm ci --ignore-scripts --workspaces=false
$env:WPFB_WORDPRESS_ROOT = 'C:\path\to\site\app\public'
$env:WPFB_WORKER_DATA = "$env:LOCALAPPDATA\WPFastYBuilder\data"
# Enter the independently generated shared token already saved in WP settings.
# Secure input avoids a literal secret in PowerShell history; never a provider key.
$secure = Read-Host 'Local worker shared token' -AsSecureString
$env:WPFB_WORKER_TOKEN = [System.Net.NetworkCredential]::new('', $secure).Password
node .\server.mjs
```

In another shell, status checks without any secret:

```powershell
Get-NetTCPConnection -LocalAddress 127.0.0.1 -LocalPort 18991 -State Listen |
  Select-Object LocalAddress,LocalPort,OwningProcess
curl.exe -s -o NUL -w "%{http_code}\n" -X POST http://127.0.0.1:18991/history
# Expected401: unauthenticated access is forbidden (not a failed health test).
```

Keep the worker terminal open; Ctrl+C stops ONLY this worker. For a later
scheduled task/service, load the three environment variables from an ACL-protected
file outside the document root; never put a token/key into task command arguments.
No Windows service/autostart installer is supplied or validated yet. Do not use
WSL, containers, public binding or port-security changes to substitute for the
same-host requirement. Incus orchestration remains explicitly out of scope.

### Availability semantics

Authenticated WP history includes only safe configuration metadata: model id and
whether a Cursor key is configured. The chat input is disabled when unconfigured,
while saved conversations/proposals remain visible. “Configured” is NOT a claim
that credentials are valid, Grok is accessible, or live acceptance has passed.
Provider/model discovery happens on an explicit send, never on history/health.
