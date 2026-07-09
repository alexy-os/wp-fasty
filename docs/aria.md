# ARIA boundary — static vs `@ui8kit/aria`

From Templ `templ-aria` + Codegen passthrough / `DataUI8Kit` rules.

## Three layers (never collapse)

| Layer | Owner | Examples |
|-------|-------|----------|
| **1. Static semantics** | Generated primitives | Native elements, roles, labels, `aria-current` |
| **2. Explicit ARIA props** | Brick API | `AriaLabel`, `AriaLabelledBy`, `AriaDescribedBy` |
| **3. Behavior hooks** | App / theme runtime | `behavior="ui8kit"`, `data-ui8kit-*` |

## Rules

- Layer 3 MUST NOT be required for valid SSR HTML
- MUST NOT implement custom widget JS inside generated `ui/` parts
- Prefer typed Aria* props over raw `$attrs` in public APIs
- Icon-only controls MUST have an accessible name
- Behavior hooks default **off** (`DataUI8Kit` empty → no attribute)
- Boolean native attrs: present-or-absent; ARIA state attrs: always `"true"` / `"false"` strings

## Sheet / dialog DOM contract (portable)

When wiring mobile nav (or any sheet) with `@ui8kit/aria`:

```html
<!-- Panel -->
<div id="panel-id" role="dialog" aria-modal="true"
     data-ui8kit="sheet" data-ui8kit-dialog="true"
     data-state="closed" hidden>…</div>

<!-- Trigger -->
<button type="button"
        data-ui8kit-dialog-open="true"
        data-ui8kit-dialog-target="panel-id"
        aria-controls="panel-id"
        aria-haspopup="dialog"
        aria-expanded="false">Open</button>

<!-- Close -->
<button type="button"
        data-ui8kit-dialog-close="true"
        data-ui8kit-dialog-target="panel-id">Close</button>
```

**Critical:** With `behavior="ui8kit"`, `open` / `Open` is **initial SSR state only**, not controlled React-style state. The runtime owns `hidden`, `data-state`, focus, and ARIA after mount.

## Theme responsibilities

1. Enqueue `@ui8kit/aria` (or built `ui8kit.js`) once in the document shell
2. Emit matching `data-ui8kit-*` from preset Latte / composites
3. Keep stable IDs in a twin constants file per shell (`{slug}-mobile-sheet-{trigger|panel|title}`)
4. Optional CSS scroll lock: `body:has(#panel-id:not([hidden])) { overflow: hidden; }`

## Codegen today

- Primitives emit static ARIA and optional `data-ui8kit` when props set
- Behavior composites (Sheet, Tabs, Menu, …) are a **later phase** in Codegen (`components/` + `@ui8kit/aria`)
- Until Sheet is generated: theme-builder may emit sheet markup that matches this contract by hand in **presets** (not by forking Button/Card)

## Other patterns (same boundary)

Tabs, Popover, Combobox, Menu, Toast — declarative markup + hooks; no custom JS inside bricks. Native `<dialog>` centered Dialog may differ from Sheet.
