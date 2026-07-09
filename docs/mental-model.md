# Mental model

Five ideas. If a design violates one of them, it is wrong for this project.

## 1. Spec / brick definitions drive code

User intent → bounded contract → validation → generated runtimes → verification.

- Codegen: `bricks/<name>/<name>.def.ts` + `*.variants.json` (+ optional `*.data.json`)
- Older Templ: `*.spec.md` + same JSON recipes
- Agents and humans do **not** invent class strings or DOM shapes outside the contract

## 2. One recipe JSON, many ports

`*.variants.json` is the sole source of visual presets (`Variant`, `Size`, …).

- Go embeds it, TS imports it, PHP compiles it into `Classes.php`
- Never twin or hand-copy variant maps per runtime

## 3. Layout grammar is law

Only **Block / Box / Stack / Group** for layout composition in UI blocks.

- No ad-hoc `<div class="flex…">` as a substitute for those primitives
- Exactly **one Block** (landmark entry) per block/partial file
- Stack = column; Group = row; Box = inner non-landmark container

## 4. Static bricks; behavior is opt-in

`ui/` primitives are presentation-only: props → attributes/classes → HTML.

- Interactivity belongs to **`@ui8kit/aria`** at the app/theme level
- Hooks emit as `data-ui8kit-*` when `Behavior` / `DataUI8Kit` is set
- Default: hooks **off** — SSR HTML must be valid without JS

## 5. One contract, many runtimes

Templ, React, Svelte, Vue, Latte, Twig share:

- Same DOM / ARIA / class merge order
- Same children semantics (content in, not positional string args)
- Same tag allow-lists

WordPress Latte is **another port**, not a fork of the design system.

---

## Copy vs chrome

| Lives in | Content |
|----------|---------|
| Registry / generated primitives | Copy-neutral (no product strings) |
| Theme fixtures / context / presets data | Product copy, nav labels, ARIA labels for demos |
| Theme PHP (`ContextFactory`) | WordPress → typed context array |

Templates receive **props/context only**. No `get_the_title()` inside Latte.
