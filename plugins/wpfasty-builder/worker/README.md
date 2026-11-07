# Builder local Node worker — candidate, not production/user-machine accepted

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
  Linux SDK import and one explicitly authorized local Grok turn passed. No further paid tests are authorized by that acceptance.
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
   # optional, default 18991; must equal Settings → Builder chat → Local worker port
   WPFB_WORKER_PORT=18991
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

## Secrets without plaintext (local-wp and other hosts)

- Worker secrets source (first match): `WPFB_WORKER_SECRETS=stdin` -> one JSON object
  `{"token":"...","secrets":{"NAME":"value"}}` (names `[A-Z0-9_]{1,64}`; `CURSOR_API_KEY` is used for the SDK, other secrets are kept in memory only and never used or reported; legacy `cursorKey`/`jevKey` are mapped to `CURSOR_API_KEY`/`JEV_API_KEY`) on stdin then EOF (local-wp feeds it from its age vault);
  `WPFB_WORKER_TOKEN_FILE=/path` (file must be 0600 on POSIX); legacy `WPFB_WORKER_TOKEN` env.
  Secret env vars are deleted from `process.env` after reading. With `cursorKey` the worker uses it
  when a turn arrives without credentials, so WordPress never needs to store or send the key.
- WordPress reads, before its options: `$_SERVER['WPFB_WORKER_TOKEN']`, `$_SERVER['WPFB_WORKER_PORT']`,
  `$_SERVER['WPFB_WORKER_KEY']==='managed'` (server/FastCGI params set by the host, e.g. local-wp per
  request). Then nothing secret is stored in `wpfb_chat_settings`; settings show "Managed by host".
- `POST /status` (authenticated, empty body) -> `{"active":bool,"activeTurnId":string|null,"credentials":"managed"|"request","secrets":<count>}` (count of host secrets held; never names or values). `activeTurnId` is an opaque random id per running turn (not the requestId; no prompt, page or secret). No drain/interlock: a host stop during a turn still interrupts it.
- WordPress (`Chat.php`): with `WPFB_WORKER_KEY=managed` the host is the only credential source. An empty/absent `WPFB_WORKER_TOKEN` fails closed (`wpfb_secret_unavailable`, 503, "Vault locked / secret unavailable"), with no fallback to `wpfb_chat_settings`. The settings page then shows availability only and never accepts or saves the Cursor key, token or port (Clear of an old stored copy is allowed). Legacy/manual settings apply only without the marker.
  Hosts check it before stop/restart/port change so a running turn is not cut silently.
- SDK child env: PATH, HOME, NODE_ENV, TEMP/TMP/TMPDIR (data dir), and on Windows SystemRoot, SystemDrive,
  windir, ComSpec, PATHEXT, USERPROFILE/APPDATA/LOCALAPPDATA (data dir). The child exits when the
  worker's IPC channel closes (no orphan after a worker crash).

## Actual boundaries and behavior

- Same-origin WP REST: `/wp-json/wpfasty-builder/v1/chat/<page-id>/history`,
  `/turn`, `/apply`. Explicit `wp_rest` nonce + `edit_page` capability, page-only.
  Settings require manage_options and a settings nonce.
- WP alone contacts `http://127.0.0.1:<worker_port>` (default 18991). Only the port is
  configurable: worker env `WPFB_WORKER_PORT` and WP option `wpfb_chat_settings.worker_port`
  must match (integer 1024-65535; anything else is rejected). Host stays fixed loopback; no configurable upstream,
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

One authorized local SDK/Grok turn completed: its validated structural proposal was
applied to draft only; real Latte output and durable history passed, and the browser
confirmed the saved title. The follow-up applied-history presentation fix still
needs a read-only browser recheck. Windows/user-machine installation and production
release are NOT accepted by those local results. Any new paid turn needs separate
authorization. Do not remove the old Creator plugin/data yet.

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

### Reloaded proposal state

Worker history records generation, not WordPress draft application. WP joins each
history proposal with its saved application marker and current draft revision.
Applied proposals show the historical applied revision and have no Apply button;
stale proposals are disabled. Replaying an already-applied request never writes
again, even if the current draft has since advanced. Configuration-unavailable
copy preserves/acknowledges existing history instead of claiming no request ever ran.


## Response usage and real tool activity

SDK `@cursor/sdk` 1.3.0 exposes `Run.stream()` (`tool_call` events) and
`RunResult.usage` (cumulative token counts for that run). Observing a stream is
not a second `send()`. We persist the final reported counts once, not a sum of
possibly repeated events. Older history and missing/invalid telemetry return
`usage: null`; no token estimates, prices or fabricated zeroes are displayed.
No billing API is called. Counts include input/output/cache read/cache write;
reasoning tokens, when reported, are a subset and are not added again.

Authenticated POST `/progress` takes `{site, page, requestId}` and returns only
`{requestId, state, sequence, tool}`. Tool is null or the allowlisted
`{name: search_catalog|get_block_details|get_skill|propose_layout, status: running|completed|error}`.
No arguments, results, arbitrary tool names, thinking text or credentials cross
this transport. Activity is ephemeral, only the latest event is retained and
it is discarded on completion/failure; terminal state is read from the durable
turn store. Another site/page/request gets `unknown` and no activity.

WordPress exposes same-origin GET `/wpfasty-builder/v1/chat/<page>/progress` with
a REST nonce plus the same `edit_page` capability check as history/apply. The
browser polls one request at a time, bounded to 100 seconds. Tab unmount,
completion, observation failure and timeout abort polling. This **does not
cancel or retry** the paid agent request; after navigating away, check history.
The existing draft revision lock, explicit Apply, idempotent replay and manual
Publish are unchanged.

Visible progress during a long turn requires the WordPress host to serve at
least two concurrent PHP requests. A single-process `php -S` or single-slot
FastCGI host queues GET progress behind POST turn and cannot show intermediate
events. This is a host concurrency requirement, not permission to bypass WP
nonce checks, expose the worker port or weaken the iframe sandbox. Windows
host/live streaming has not been accepted with this increment. The local-wp
worker resource must include the new `telemetry.mjs` and use this commit, not an
older pinned worker build; its vault contract and SDK lockfile are unchanged.

## Release-bundled local catalog search (no update service)

`catalog/stock.v0.json`, `catalog/manifest.json`, `catalog/engine/*.mjs` and
`catalog/LICENSE` ship WITH this worker inside the Builder plugin. Updates happen
only with a reviewed plugin/worker release. There is no HTTP/MCP search service,
updater, network fallback, auto-install, separate mutable cache or model search.
Search runs without npm access; the agent still needs the existing locked SDK
installation described above. The host's **installed worker resource must be
refreshed** to this release too: updating WP PHP alone cannot replace an older
local-wp resource. No resource on the owner's computer was updated here.

Upstream: `codegen-x/ui8kit@6b2e71b56375cf3aab161a755c8eee806e7309fb`, root MIT
license, Copyright (c) 2026 CodeGen-X. Its npm `private:true` flag prevents package
publication, not use under that license. Original engine TS and provenance hashes
are in `tools/vendor/facet-search`; the snapshot and license retain upstream bytes.
Build/check offline with pinned Bun 1.4.4:

```
bun tools/builder/local-search-build.ts
bun tools/builder/local-search-build.ts --check
node --test plugins/wpfasty-builder/worker/test/*.test.mjs
node tools/builder/local-search-bench.mjs
```

The pure upstream engine preserves OR-within/AND-across facets, lexical prefix
ranking, multilingual labels/synonyms, soft industry ranking and deterministic
sort/collapse. The supported block facets/values are exposed as bounded query
schema + descriptors, not instructions or a semantic model. Search tools replace
the old full-catalog `catalog_lookup`: `search_catalog` returns 5 cards by default,
maximum10; `get_block_details` requires exact case-preserving blockId+variant and
returns only that installed prop schema/defaults/data/records (48 KB cap). Both
remain within the shared16-call budget. Proposal changes require inspected details;
unchanged existing blocks can be preserved without inventing replacements.

Canonical mapping: Stock `block.hero/twoActions` maps to WordPress block `hero`,
variant `twoActions`, NOT a registry organism alias. Target is the actual catalog's
`runtime()` (`latte` or `twig` only), with matching installed template extension.
The current bundled WP catalog has93 blocks/482 variants; all482 intersect Stock,
which contains112 blocks/532 variants. The other50 variants and all80 screens are
not proposed as installed blocks. Other catalog sources are explicitly unmapped,
not interpreted as UI8Kit by coincidental ids. PHP schema/revision/Apply/Publish
validation remains authoritative and unchanged.

At load, fixed local resources are size/schema/hash/digest checked (snapshot ≤3MiB).
Corruption/mismatch fails with `catalog_map_unavailable`, not a catalog dump or a
network/model retry. A module-local immutable map is reused within its process;
site/page installed intersections and inspected-detail sets are never shared.
The existing SDK child is still created per turn; there is no cross-turn cache
service. Deterministic fixtures are NON-LIVE; Windows/live SDK use is not proven.

## Application Agent policy and progressive skills

See `docs/agent/builder-agent-policy.md` in the source repository. WordPress's separate
Agent policy UI versions nonsensitive Builder/Site instructions and enabled skill
registry; Page Agent brief is edit_post-scoped. Active versions are snapshotted per
turn, not re-read mid-run. `policy.mjs` intersects configured tools with the code
allowlist BEFORE SDK Agent.create. `get_skill` loads only enabled entries whose
required tools are actually allowed; it cannot grant tools or change policy.
This is application-level prompt assembly, not a native SDK systemPrompt override
or filesystem skills. Initial context contains only the index, not all skill text.
Result/history has version-only provenance and actually loaded skill versions.
The current resource needs policy.mjs + policy-default.json as well as catalog/;
refresh the host's separately pinned worker resource too. No model/browser/Windows
acceptance or owner-machine update is implied by NON-LIVE fixtures.
