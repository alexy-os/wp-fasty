# Layout grammar

Portable rules from Templ `templ-layout-grammar` / docs learn-04.
Codegen already emits `Block`, `Box`, `Stack`, `Group` as Latte parts.

## Four primitives

| Primitive | Default tag | Direction | Role |
|-----------|-------------|-----------|------|
| **Block** | landmark or `div` | — | **Entry point** — exactly **one per file** |
| **Box** | `div` only | — | Inner non-landmark container |
| **Stack** | `div` / `ul` / `ol` | column | Vertical rhythm |
| **Group** | `div` / `fieldset` / `dl` | row | Horizontal grouping |

Tag allow-lists are shared (`TagGroup` in codegen `src/domain/tags.ts` / `Rt::GROUPS`).

## Six hard rules

1. **Block is the entry point** — one per partial/block file; no nested Block in the same file. Exception: a child composite owns its own landmark Block; parent must not re-wrap it.
2. **Box lives inside Block** — never carries landmark tags (`aside`, `main`, `header`, …).
3. **No raw layout markup** in UI blocks — no free `<div class="flex…">` instead of Stack/Group. Exceptions: true semantic roots (`button`, `form`, `table`, `dialog`, …) and document shells.
4. **Stack is vertical** — do not override to `flex-row`; use Group.
5. **Group is horizontal** — `tag="fieldset"` only for related form controls.
6. **Block at most once** — validators historically used `blockonce`; theme-builder should enforce the same for generated presets.

## Heuristic

Two sibling `Box` inside one `Block` is often a smell — unless the Block already provides column flex (classic sidebar: header / nav / footer sections).

## Latte mapping

```latte
{capture $inner}
  {include 'stack/Stack.latte', class: 'gap-2', children: (string) $navItems}
{/capture}
{include 'block/Block.latte', tag: 'aside', class: 'hidden w-64 md:flex md:flex-col', children: (string) $inner}
```

Children are always pre-rendered HTML strings (`{capture}` → `children`).

## Document shell vs UI block

| Surface | May use raw HTML? |
|---------|-------------------|
| Theme layout (`default.latte`) | Yes — `<html>`, `<body>`, `{wp_head}` |
| Preset / catalog blocks | No — layout grammar only |
| Generated `ui/` parts | Emitted by codegen — do not hand-edit |
