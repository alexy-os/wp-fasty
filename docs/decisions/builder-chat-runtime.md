# Builder chat first increment — 2026-11-07

## Browser hypothesis: tested, not supported by the installed SDK

Offline `tools/sdk/browser-bundle-probe.mjs` tested installed `@grok/agent-sdk@0.6.0`
and its `@cursor/sdk@1.3.0` dependency, keeping actual exports (not erased TS types).
`esbuild platform=browser` fails for the agent package, Cursor default entry and
Cursor flat `/bundled` entry. Examples include `node:module`, `fs/promises`, `os`,
`path`, `child_process`, and `bun:sqlite`. No browser polyfills or empty-module
stubs are used. A Node-target build is a different claim, not a browser runtime.
No agent creation, login, key lookup or inference was performed.

Official docs checked: https://cursor.com/docs/sdk/typescript
- Node >=22.13; filesystem, optional native sandbox/ripgrep helpers, durable store.
- `/bundled` supports single-file Node/Bun applications, not a browser agent.
- Even cloud agent calls need a server-held Cursor API key.
- The SDK is an agent harness, not a raw browser chat-completions client.

Reproduce against an existing SDK install (no credentials needed):
```
node tools/sdk/browser-bundle-probe.mjs /path/to/agent-project /tmp/sdk-evidence
```
A successful Node bundle is NOT an executed agent. `bun:sqlite` is external in the
Node comparison; the official JSONL-store path is required on Node without it.
No SDK/provider substituted, no marketplace plugin installed.

## Implemented without runtime assumptions

Builder has Blocks/Chat palette modes alongside its SAME existing iframe canvas,
inspector, save flow and undo stack. Chat is visibly unavailable (disabled input
and send), never canned assistant output. Its read-only context comes directly
from EditorStore and the current source catalog: instanceId, blockId, variant,
props, bindings, catalog-resolved template; unknown variants are flagged.
No summary-PageDTO substitution. No CR or external backend dependency was added.
Existing protected REST catalog/layout routes and nonce/capability checks remain
unchanged. No new write/model route was introduced.

Native generated Svelte primitives are copied unchanged from existing Creator
`buildytech/chat` c7c93ff (`ui/{box,text,button,textarea}`, utility closure).
The small Block composition only adjusts relative imports. The legacy Builder
screen is not broadly retagged/redesigned; the new panel uses primitives and copy
from chat/content.ts. New block schemas and Latte templates were not changed.

## Choice before accepting keys / enabling chat

1. Run Node >=22.13 on the WP host as a local protected agent worker (no separate
   VPS/CR required), with tight custom-tool access to validated Builder changes.
2. A separately hosted worker / officially supported cloud agent via WP server
   proxy. This remains an external runtime, not PHP-only hosting.

Pure PHP WordPress plus browser JS cannot execute this Node SDK. Picking another
raw model API would change the requested product/provider and is not done here.
Cursor/Jev key settings and message execution intentionally wait for this choice;
collecting unused keys would not make chat functional. Secrets must stay on the
WP server, never in admin bootstrap, assets or release ZIP. No existing Creator
plugin, database, session, process or VPS was removed or modified.

## Acceptance boundary

Editor typecheck, editor tests and production build are required. Browser runtime,
paid model operation, full repository parity/phpstan/deptrac/codex gates and
replacement-plugin acceptance are NOT claimed by this scaffold increment.

Validation on this increment: editor check 0 errors / 0 warnings; editor tests
93 passed / 0 failed; existing PHP BuilderTest 16 tests / 635 assertions passed;
new ChatPanel source gate 0 problems; Svelte autofixer no issues; production
IIFE editor.js/editor.css built. No browser or paid runtime acceptance performed.
