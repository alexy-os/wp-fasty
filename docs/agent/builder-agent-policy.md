# Agent policy / progressive skills — application-level MVP

## What this is (and is not)

A versioned instruction DATA layer above the existing local Builder tools; NOT
an SDK systemPrompt replacement, native SDK filesystem-skill loader, autonomous
policy editor, new provider, account permission grant or executable plugin code.
No shell/HTTP/browser/install/publish/vault/policy-writing tools exist here.
No paid turns or secret access were used to test this increment.

## Four layers and authoring UI

1. **Platform**: immutable code. Custom-only allowlisted tools,16-call/90s run limit,
   installed catalog/schema, nonce/capabilities, durable idempotency, draft CAS,
   human Apply/Publish and restrictive iframe sandbox. UI displays these protections
   read-only; editable instructions cannot grant permissions or weaken them.
2. **Builder product**: conversational behavior, structural-selection guidance.
3. **Site**: language, tone, audience, brand, factual-source instructions. These are
   supplied facts/instructions, NOT permission to fetch any URL. Product and Site
   are separate fields scoped to the current WP site, not a multisite network policy.
4. **Page brief**: versioned per-page task; editors may manage only pages for which
   WordPress grants `edit_post`. It cannot contain policy/tool configuration fields.

- Separate top-level **Agent policy**, `/wp-admin/admin.php?page=wpfasty-agent-policy`.
  Native WordPress textarea presents the typed JSON document (advanced/MVP editor,
  not a visual policy designer). Existing field names only; no secrets here.
- Pages → **Agent brief**, choose an editable page id; direct route:
  `/wp-admin/edit.php?post_type=page&page=wpfasty-agent-brief&post=12` on OUR fixture.
- Save new draft → inspect effective ACTIVE instruction data → explicitly activate
  selected new version. Saving does not change active behavior. Rollback copies an
  old version into a new inactive draft; activate that new version explicitly.
- History shows immutable document version, actor and timestamp; at most20 retained
  entries, keeping the active one. Activating an older version directly is rejected.
- Skill instruction/purpose/trigger/enabled/tool edits require a new skill version;
  same id+version cannot silently change within retained history.
- `manage_builder_agent_policy` is explicitly granted to administrators. New
  **Prompt engineer** role has ONLY `read` + that capability; it gets no page edit,
  publishing, settings, user management or credential rights. It is not assigned to
  any existing user automatically. Existing editors do not gain the global cap.

## Contracts / boundaries

`AgentPolicyDefinition`: pure normalization/defaults/version transition domain;
no WordPress, storage, SDK, clock or global permissions. Blocking BuilderContract
Deptrac and global-WP-function architecture tests include it.

`AgentPolicy`: WP adapter for nonautoload nonsensitive option
`wpfb_agent_policy_v1` and page meta `_wpfb_agent_brief_v1`. Options use atomic SQL
compare-and-swap on prior serialized state; page meta uses unique add/conditional
update. Both carry revision tokens, reject stale writes409 and preserve backslashes
through WP metadata APIs. All native form writes check admin nonce; REST handlers
also check nonce and capability internally. JSON is strictly schema/size validated;
output is escaped, never raw model/policy HTML. No credential schema/migration.

REST (same-origin WordPress authorization, no worker keys in these endpoints):
- `GET/POST /wpfasty-builder/v1/agent-policy`: global capability + REST nonce.
- `GET/POST /wpfasty-builder/v1/agent-brief/<page>`: edit_post + REST nonce.
- POST: `expectedRevision`, `action` draft/activate/rollback; draft takes `definition`,
  activate/rollback take integer `version`. Unknown fields/tools/skill paths rejected.

## Actual agent behavior / lock / preview

PHP snapshots active global+page versions into the server-only turn context.
`worker/policy.mjs` validates again, snapshots the input, intersects configured tools
with the immutable three-tool platform set and removes disabled tools BEFORE SDK
Agent.create. `get_skill` exists only when an enabled compatible registry entry is
available. A skill cannot re-enable a tool by declaring it as required.

Initial instruction context contains product/site/brief and compact skill index
(id/version/purpose/triggers/requiredTools), NOT every skill's instructions.
`get_skill({id})` loads only enabled registered entries whose requirements fit the
actual allowed tools. No arbitrary path, source lookup or executable instructions.
Starter skills: block-selection, text-editing, page-review; they reference only
actual search_catalog/get_block_details/propose_layout capabilities. Niche words
such as sushi/sneakers are content goals, not invented industry enums or templates;
no matches stay honest, and no Jev integration/network dependency was added.

Effective preview is explicitly the ACTUAL initial instruction DATA payload, NOT
an alleged complete SDK/system prompt. It lists separately the additional runtime
layout/history/catalog vocabulary/user request; global preview uses empty page brief.
PHP preview and Node initial assembler share a blocking golden fixture. Bundled
worker default JSON is checked against pure PHP defaults. Skill text is fetched
progressively and is intentionally absent from that initial preview.

Each result/history carries policyVersion, briefVersion, policyDigest and only the
ids/versions of actually loaded skills. Full instruction text is not added to the
public turn-history DTO. The assistant footer displays those real versions; old
history invents none. Run/replay keeps its original policy snapshot/provenance;
editing or activating policy later never resubmits a turn. Tools cannot edit policy.

## Evidence / deployment caveats

- NON-LIVE pure ledger tests: draft/activation/rollback, CAS, retention, skill version,
  unknown fields/tools, inert XSS data, bundled defaults and preview parity.
- NON-LIVE Node tests: enabled/intersection gate, progressive discovery/loading,
  unknown ids/path/privilege rejection, snapshot mutation isolation, one send via
  injected fake SDK, durable provenance/replay/page isolation, standalone resources.
- `tools/sdk/builder-policy-contract.php`: OUR real WP only, disposable users/pages,
  restores nonsensitive global policy. Checks role minimum, anonymous/editor/nonce
  denial, per-page rights, XSS escaping, activation, actual storage CAS conflict,
  page brief isolation and slash preservation. CI runs it against packaged Builder
  on PHP8.2/8.5. Existing WP-worker HTTP fixture also carries policy provenance.
- No owner post13, old Creator plugin/data, credentials/vault, local-wp source,
  paid model, VPS, owner machine, browser or iframe sandbox changes.
- Native form/browser UX and real agent skill-choice effectiveness remain UNVERIFIED;
  deterministic injected fixtures are not live acceptance. JSON authoring is the MVP.
- Plugin and separately installed local-wp worker resource must both refresh to this
  code (including policy.mjs/policy-default.json/catalog). No owner's installed
  resource was changed and no ZIP delivery/update service is part of this task.
