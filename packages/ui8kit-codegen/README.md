# Spec-driven UI Codegen

Spec-driven codegen engine for UI8Kit `ui/` primitives. One typed render
contract per brick — six generated runtimes (plus a static HTML export) with
an identical DOM, design, and ARIA contract:

| Runtime | Output | Composition model |
|---|---|---|
| **Go Templ** | `ui/<brick>/<brick>.templ` + `<brick>_gen.go` | SSR, `{ children... }`, `Attrs` map |
| **React 19** | `ui/<brick>/<brick>.tsx` | `forwardRef`, `asChild` via `Slot`, rest props |
| **Svelte 5** | `ui/<brick>/<Part>.svelte` | runes, snippets, `<svelte:element>` |
| **Vue 3** | `ui/<brick>/<Part>.vue` | `<script setup>`, slots, `<component :is>` |
| **Latte** | `ui/<brick>/<Part>.latte` | typed `{parameters}`, `n:attr`, children as HTML string |
| **Twig** | `ui/<brick>/<Part>.html.twig` | `ui8kit_attr_str`, `include with`, children as HTML string |

All runtimes consume the **same** colocated `*.variants.json` (CVA-style
class recipes) — Go embeds it, the TS runtimes import it, PHP gets it
compiled into `php/UI8Kit/Classes.php`. Attribute logic (default resolution,
ARIA states, boolean presence) is written **once** as a small typed
expression IR and printed idiomatically per runtime.

The PHP runtimes cover **structurally simple parts only** (60 of 63): a
mechanical predicate (`src/domain/php-support.ts`) skips parts whose render
tree needs loops over typed items or a children slot inside conditional
branches — today that's `breadcrumb/Breadcrumb`, `select/Select`, and
`icon/Icon`. There is no hand-maintained tier list; the CLI prints the skip
summary on every generate. Class helpers (`Classes::iconClasses()` etc.) are
still emitted for *every* part as the app-level escape hatch.

## Why an IR instead of four hand-written files

The upstream registry hand-writes `.templ` and `.tsx` pairs against a shared
spec. Adding Svelte and Vue by hand doubles the drift surface. This engine
inverts the workflow:

- **Brick definitions** (`bricks/<brick>/<brick>.def.ts`) are the single
  source of truth: props, derived values, and a render tree written in a
  deliberately tiny, non-Turing-complete expression vocabulary (decision
  tables, string defaults, boolean algebra).
- A **canonical renderer** (`src/domain/render.ts`) executes definitions
  directly and produces the reference HTML — the executable specification of
  the DOM contract.
- Four **emitters** print the same definition as idiomatic runtime code.
- **Parity tests** server-render every generated component (React via
  `react-dom/server`, Svelte via `svelte/server`, Vue via
  `@vue/server-renderer`, Templ via a generated Go harness) and assert the
  normalized DOM equals the canonical renderer's output for every part and
  every showcase fixture.

Identity is therefore not a review convention — it is a property checked by
the test suite on every run.

## Quick start

```bash
npm install
npm run check       # validate all brick definitions
npm run generate    # emit all six runtimes into generated/
npm test            # domain units + 6-runtime DOM parity (Go + PHP optional)
npm run verify      # check + typecheck (engine and generated) + tests
```

Seven local welcome previews (generated primitives + shadcn tokens) live in
[`examples/`](examples/) — see [`examples/README.md`](examples/README.md).

```bash
bun run generate && cd examples && bun install && bun run build:css
bun run dev:templ   # :8080  ·  dev:react :5173  ·  dev:svelte :5174  ·  dev:vue :5175
bun run dev:latte   # :5176  ·  dev:twig :5177   ·  build:html → examples/html/
```

CLI flags:

```bash
npx tsx src/infrastructure/cli.ts generate \
  --out generated \
  --go-module github.com/ui8kit/ui \
  --runtimes templ,react,svelte,vue,latte,twig
```

PHP parity tests need `php` ≥ 8.1 and `composer` on the PATH (they are
skipped otherwise, same policy as the Go Templ suite):

```bash
sudo apt-get install -y php-cli php-xml php-mbstring composer
```

## Generated tree

```
generated/
  go.mod                      # module for the Templ output (--go-module)
  composer.json               # Latte + Twig deps (vendor-dir: php/vendor)
  cmd/parity/main.go          # Go render harness used by parity tests
  cmd/parity-latte/main.php   # Latte render harness (same JSON protocol)
  cmd/parity-twig/main.php    # Twig render harness
  php/UI8Kit/                 # PHP runtime: Rt.php, Classes.php (generated),
                              # TwigExtension.php
  utils/                      # runtime support layer (Go + TS, shipped as-is)
    utils.go tags.go variants_json.go expr.go heading.go
    cn.ts variants.ts tags.ts expr.ts recipe-types.ts env.ts index.ts
  ui/
    index.ts                  # React barrel
    index.svelte.ts           # Svelte barrel
    index.vue.ts              # Vue barrel
    shared.ts                 # re-exports every brick's shared contract
    slot/slot.tsx             # React-only composition primitive
    button/
      button.variants.json    # the shared CVA recipe (copied verbatim)
      button.shared.ts        # variant literal types + classes helpers (TS)
      button.templ            # Go Templ runtime
      button_gen.go           # //go:embed recipe -> ButtonVariants
      button.tsx              # React runtime
      Button.svelte           # Svelte 5 runtime
      Button.vue              # Vue 3 runtime
      Button.latte            # Latte runtime (PHP)
      Button.html.twig        # Twig runtime (PHP)
    card/
      Card.svelte CardHeader.svelte …   # one file per part (SFC constraint)
      card.tsx card.templ …             # single file for Go/React
```

Consume the Templ output with `templ generate && go build`, the TS outputs
with any Vite-based setup (`@vitejs/plugin-vue`,
`@sveltejs/vite-plugin-svelte`), and the PHP outputs with
`composer install` in `generated/` plus a `FileLoader`/`FilesystemLoader`
rooted at `generated/ui` (register `UI8Kit\TwigExtension` for Twig). Peer
deps per runtime: `react`, `svelte`, `vue`, plus `clsx` + `tailwind-merge`
for the shared `cn()`; `latte/latte ^3` or `twig/twig ^3` for PHP.

PHP children are pre-rendered HTML strings (`children` parameter, printed
with `|noescape` / `|raw`) — compose nested bricks with `{capture}` (Latte)
or `{% set %}` (Twig) as shown in `examples/latte` and `examples/twig`.
Class merging in PHP is plain concatenation like Go's `Cn`; parity is
asserted on the *effective* Tailwind utility set.

## Architecture

Clean architecture with the dependency arrow pointing inward:

```
src/
  domain/          # pure model, zero I/O
    expr.ts        # expression IR (lit, prop, mapOr, cond, isSet, …)
    model.ts       # PropDef, PartDef, BrickDef, render-tree nodes
    recipe.ts      # VariantRecipe validation + composition
    tags.ts        # TagGroup allow-lists (mirrors utils/tags.json)
    naming.ts      # per-runtime prop naming conventions
    render.ts      # canonical renderer — the executable spec
    validate.ts    # definition invariants (fail before emitting)
  emitters/        # one printer per runtime + shared analysis
    templ.ts react.ts svelte.ts vue.ts
    ts-expr.ts go-expr.ts       # expression printers
    ts-common.ts                # shared TS analysis + <brick>.shared.ts
    go-harness.ts               # Go parity harness generator
  application/     # generate pipeline (validate -> emit -> write)
  infrastructure/  # CLI
bricks/            # 33 brick definitions + variants.json + data.json fixtures
runtime/           # support files copied verbatim into generated/utils
tests/             # domain units + cross-runtime parity suites
```

- **DDD**: bricks, parts, props, recipes, and tag groups are the ubiquitous
  language of the domain; definitions are data, not templates.
- **SOLID**: emitters implement one `Emitter` interface; adding a runtime is
  a new printer, not a change to the model (open/closed). The expression IR
  is closed by design so every emitter provably covers the whole vocabulary
  (exhaustive `switch` over a discriminated union).
- **DRY**: class recipes live in one JSON; attribute logic lives in one IR;
  the three TS runtimes share one `<brick>.shared.ts` (literal-union variant
  types + `<part>Classes()` helpers) and one tiny helper runtime
  (`utils/expr.ts` mirroring `utils/expr.go`).
- **KISS**: the IR has no loops-with-state, no user functions, no templating
  language — thirteen expression kinds and five node kinds cover all 33
  bricks (63 parts).
- **TDD**: the canonical renderer is the spec; 189 tests assert recipes,
  tags, expressions, validation, and full DOM parity for every part across
  all four runtimes using the upstream showcase fixtures.

## The cross-runtime contract

Decided once, applied to every brick (see `src/domain/naming.ts` and the
emitters):

1. **Prop naming.** Canonical names are Go PascalCase (`Variant`, `Class`,
   `AriaLabel`, `HTMLFor`). React uses `variant`, `className`, `aria-label`,
   `htmlFor`. Svelte and Vue use HTML-native names: `class`, `aria-label`,
   `for` (closer to Go than to React).
2. **Variants.** Every `cva: true` prop resolves through the shared
   `*.variants.json`. TS runtimes get literal-union types derived from the
   JSON (`RecipeKey`), Go gets an embedded `Variants` value.
3. **Class merge order.** Recipe base → variant/size classes → static base →
   computed fragments → state classes → caller class. TS runtimes and the
   canonical renderer merge Tailwind-conflict-aware (`cn`); Go concatenates
   (`Cn`) — parity tests compare the *effective* utility set after conflict
   resolution, which is identical.
4. **Children.** Templ `{ children... }`, React `children`, Svelte 5
   `{@render children?.()}` snippets, Vue `<slot />`. Childless bricks render
   no slot anywhere.
5. **Root tag resolution.** Tier-B bricks resolve their tag against the same
   `TagGroup` allow-lists in every runtime: Go emits a local `switch`, React
   a `resolveTag(...) as ElementType`, Svelte `<svelte:element this>`, Vue
   `<component :is>`.
6. **Boolean vs ARIA-state attributes.** Native boolean attrs (`disabled`,
   `checked`, `open`, …) are present-or-absent in all runtimes. ARIA state
   attrs (`aria-checked`, …) are always stringified `"true"`/`"false"`, never
   omitted.
7. **Attrs / rest escape hatch.** Go `templ.Attributes` spread, React/Svelte
   `...rest`, Vue `v-bind` of non-class attrs with `inheritAttrs: false`
   (class is consumed into the `cn` merge so caller classes win
   deterministically). The spread always comes last: the caller wins.
8. **Passthrough props.** Props that are plain attribute forwards (`ID`,
   `Name`, `AriaLabel`, `data-ui8kit`, …) are explicit struct fields in Go
   and flow through rest props in TS runtimes — the validator enforces that
   they are never referenced by logic and always forward under their native
   attribute name.
9. **Behavior hooks stay off by default.** `DataUI8Kit` (dialog, breadcrumb)
   emits `data-ui8kit="…"` only when explicitly set. The registry ships no
   client JS; client behavior belongs to `@ui8kit/aria` at the app level.
10. **Reusability.** Components are static and presentation-only. Every part
    also exports its class composition (`ButtonClasses` in Go,
    `buttonClasses()` in `button.shared.ts`) so apps can re-root markup, wrap
    with their own state/`useEffect`, or attach `@ui8kit/aria` behaviors
    without forking the brick. React ships `asChild`/`Slot` for the same
    purpose; Svelte/Vue intentionally follow the Go pattern (classes helper on
    a manual root) per the documented policy.

## Brick definition anatomy

```ts
// bricks/button/button.def.ts (abridged)
export default brick({
  id: "ui.button",
  dir: "button",
  recipes: { button: { file: "button.variants.json", recipe: buttonVariants } },
  parts: [{
    name: "Button",
    recipeId: "button",
    asChild: true, // React-only ergonomics; other runtimes use ButtonClasses
    classes: {
      recipe: { variant: "Variant", size: "Size" },
      state: [{ test: prop("Disabled"), classes: "pointer-events-none opacity-50" }],
    },
    props: [pVariant(), pSize(), pClass(), pStr("Type"), pBool("Disabled"),
            ...controlPassthroughProps(), pAttrs(), pChildren()],
    render: el("button", [
      attrExpr("type", oneOfOr(prop("Type"), ["submit", "reset"], "button")),
      attrClass(),
      attrBool("disabled", prop("Disabled")),
      fwd("id", "ID"), fwd("aria-label", "AriaLabel"),
      attrRest(),
    ], [slot()]),
  }],
});
```

Everything a runtime needs is derivable from this: Go struct + templ
component, React props type + component, Svelte/Vue SFCs, the shared classes
helper, and the canonical reference DOM.

### Adding a brick

1. Create `bricks/<name>/<name>.def.ts` and colocate its
   `<name>.variants.json` (and optional `<name>.data.json` showcase
   fixtures — they automatically become parity test cases).
2. Register it in `bricks/index.ts`.
3. `npm run check` — the validator enforces recipe integrity, prop
   references, passthrough rules, and slot/children symmetry before anything
   is emitted.
4. `npm test` — parity across all four runtimes is asserted automatically;
   no per-runtime test writing needed.

## Deliberate deviations from the upstream hand-written pair

These are contract *improvements* applied uniformly (and verified) across all
four runtimes:

- Empty string attributes are omitted (`name=""`, `class=""` are never
  emitted where the value is empty) — upstream Templ occasionally rendered
  them.
- `IconBadge`'s deprecated legacy props (`Name`, `IconType`, `BaseClass`,
  `Prefix`, `Text`, `Title`, `AriaLabel`) were already documented as ignored
  and are dropped from the generated API.
- React `<textarea>` maps the content contract to `defaultValue` (React
  forbids textarea children); SSR output is identical.
- The rest/`Attrs` spread is last everywhere, so the caller wins
  deterministically in all runtimes (upstream Go had mixed per-brick
  behavior).
- `Title`/`CardTitle` React-only `H1`–`H6` convenience wrappers are not
  generated; use `as={1..6}`.

## Scope

`ui/` primitives only (33 bricks, 63 exported parts, Tiers A–E of the
upstream inventory, including multi-part composites: card, table, form,
form controls, select, breadcrumb). Behavior-driven `components/` composites
(Sheet, Tabs, Popover, Combobox, Menu, Toast) need the `@ui8kit/aria` client
contract and are a separate phase — the engine's IR and emitters are ready to
host them once that contract is specified.

MIT © UI8Kit.