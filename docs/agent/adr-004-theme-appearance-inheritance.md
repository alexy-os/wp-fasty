# ADR-004 — theme-owned appearance, not a duplicate Builder skin

Status: accepted (owner t211) and implemented in this change. App layer; no new skin engine.

## Existing behavior and actual gaps

Builder Front already renders published/draft pages through the active WordPress theme and only adds block CSS.
Parent/base/child tokens already cascade. AdminPalette is intentionally separate. Previously the iframe's root
mode was patched by the parent after load; the theme's preference script cannot execute in the safe sandbox.
There was no conventional optional font-file stylesheet loader, child style depended only on parent tokens,
and base-theme resolution cached one result even across `switch_theme()` in a process.

## Decision

* One active theme source for live page and draft iframe: parent → optional Extends base → child.
  Each can provide `assets/css/fonts.css` with local @font-face family/style/weight/file definitions;
  theme functions may still enqueue fonts normally. Semantic font/color tokens remain theme-owned.
  Dependencies explicitly order base/child fonts, tokens and final child style. No duplicate plugin skin.
* Authenticated preview URLs carry a transient allowlisted light/dark mode. A theme request filter renders
  the root class server-side, preserving existing attributes via the WordPress HTML Tag Processor.
  A forced mode omits only the unnecessary theme preference script. All sandbox/CSP protections stay intact;
  no allow-scripts, Node, Agent or ModulY dependency. Live browser preference behavior is unchanged.
* Preview mode participates in reload identity. Parent DOM root patches are removed. During Apply, the toolbar
  cannot switch the navigation target out from under the existing revision-aware successful-load waiter.
* Preserve explicit overrides with existing WordPress theme-scoped Additional CSS, not updateable CSS files
  or a second settings engine. Different stylesheet slugs do not share overrides; returning to a child restores
  its own record. No automatic import across themes, permission broadening or claim of schema-checking raw CSS.
  Same-slug incompatible redesigns still need human override review.
* Admin chrome remains WordPress palette/fonts. Public page font enqueues never become admin enqueues.
  Font names alone do not provide licensed font files; use actual supplied faces or honest fallback stacks.
* Token import/export is approved future direction only. SkinDefinition/catalog skin indexing and a pinned-site
  brand mode are not built. Existing page drafts, histories, policy and theme data formats remain unchanged.

## Evidence / limits

Editor navigation regression was red before implementation; it covers scheme, nonce/cache identity and old-load
rejection. The same real-WP fixture also rejects the original main AssetsHooks implementation (isolated renamed
class, no shared source revert), specifically at the missing optional-base-font enqueue assertion. `tools/qa/theme-appearance-contract.php` runs real WP queries, validated manual Builder Store,
published/draft ThemeService rendering, light/dark root output, both weights of original synthetic font fixtures,
optional base/child enqueue dependencies, nonce/anonymous/invalid-mode guards, theme-scoped Additional CSS and
switch-away/back/parent-only behavior. CI runs it before installing optional ModulY: manual core alone.
All disposable theme/page fixture data are cleaned up and the prior theme is restored.
PHP/Svelte/security/architecture/build gates apply without baseline growth or security downgrade.
This is source/integration evidence, not a visual browser/font-rendering, Windows rollout or live-model test.
The existing shared checkout/local WP are not deleted. No owner-machine, worker resource or manager update.

Deployment: ship/update the matching parent theme and Builder core together for server-side preview mode.
An old parent cannot consume the new request-local color-scheme filter; plugin-only rollout is not claimed.
Existing child themes need no duplicate Builder skin; custom shells must retain language_attributes/wp_head.

Fixture isolation: invoke with `wp --skip-themes eval ...`; the fixture switches to its disposable child and
explicitly requires the actual parent's `functions.php`. This prevents the previous inactive theme's callbacks
(e.g. Twenty Twenty-Five looking for its style.min.css under WP FastY) from contaminating the simulated request.
No product/theme/security gate or PHP notice is disabled. This is a CLI cold-boot correction, not a frontend fix.

## t216 — follow interface with a memory-only preview override

Default selection is «Как в интерфейсе»: it follows the existing WordPress admin-bar Builder button
`Admin::darkSwitch` (`#wpfb-dark-toggle`) and `ThemeMode.dark`. That source exists, is scoped to the Builder
screen and already remembers its interface preference per user. This repository does not supply a universal
WordPress dark-mode switch for every admin screen; no such system is invented here. The public theme's
`wpfasty-color-scheme` preference is separate and unchanged.

The control above the iframe offers follow / Light / Dark. Only a component-owned immutable selection is
replaced; reload constructs follow again. No additional localStorage/sessionStorage/cookie/Application entry,
page metadata or site option. An override changes only the iframe's effective server scheme, not admin/chat,
block cards, the existing global toggle, saved layout or the published site. Switching back to follow uses
current interface state, not a captured old value. Both controls retain the existing Apply navigation guard.

The same authenticated server preview URL carries the resolved light/dark value; the URL helper refuses
non-preview/missing-nonce URLs. Public View page continues to use config.permalink; canonical links and the
server-provided base preview URL have no temporary preference. PHP still checks nonce and edit capability;
no scripts are added to the sandbox and no Node/ModulY dependency is introduced.

Regression evidence: default/follow transitions, both overrides, reset/new instance, invalid selection,
no persistence dependency, immutable public URL, old-mode navigation rejection, App/toolbar wiring and real
WordPress canonical/base URL separation. No visual GUI verification is claimed.
