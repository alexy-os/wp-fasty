# wpfasty-builder

Landing pages assembled from a UI8Kit block catalog, compiled to the site's engine (Latte or Twig, isolated runtimes —
ADR-001) and rendered on the front end. Registry format and import rules: `docs/agent/builder-registry.md`.

## Editor (wp-admin → Pages → Builder)

Svelte 5 + Vite, one IIFE bundle (`assets/editor.js`, global `WPFastyBuilder`) and `assets/editor.css`; no React, no
runtime dependency besides Svelte. Source: `editor/src`, build `bun run --cwd plugins/wpfasty-builder/editor build`,
type check `… check`, unit tests `… test` (bun test, `editor/tests`).

The model follows Puck (config of typed fields per block, live canvas, overlay, history) without its React runtime:

| Part | Where | What |
|------|-------|------|
| Live preview | `App.svelte` (`commit`, `syncPreview`, `renderBlock`) | The preview is the real page in a same-origin iframe (`?wpfb_preview=1`, the draft). A change re-renders only the changed block: `POST /wpfasty-builder/v1/render-block` returns the block's HTML and the editor swaps the `innerHTML` of its `[data-wpfb-block]` wrapper; add / remove / reorder / duplicate move or create wrappers in place. The draft is saved 600 ms after the last change; the iframe reloads only when it has no builder page yet or after a bulk template-page replace. Theme JS (`ui8kit.js`) uses delegated listeners, so swapped blocks stay interactive. |
| `render-block` | `src/Rest.php`, `src/Fragment.php` | `{source?, id?, block, variant, props, bindings}` → `{id, html, issues}`. Same normalization as a saved layout (`Layout::normalize`), the props of `Compiler::blockProps`, bindings resolved by `DataSources::resolve`, rendered by `Front::block` with the active engine's `BlockRenderer` — the fragment equals the block in the full page (`tools/builder/render-block-check.php`: 474 variants × Latte and Twig, offline in CI and `--live` against a WP). Permission: `edit_page` on `page` when given, else `edit_pages`; REST nonce. |
| Typed fields | `editor/src/fields/` | `schema.ts` turns the catalog (`blocks[].props` TypeScript types, `meta.records` record field lists, `bindings`) into a config `{fields, defaultProps}`: literal union → select (≤ 3 short options: radio), `boolean` → toggle, `number`, `string` → text / textarea (by name and sample length), `T[]` → array of record items, object → object, `Record<string, T>` → map, bound list paths → data binding (source + limit). `registry.ts` maps a field type to its component; anything not understood falls back to JSON. "Advanced (JSON)" edits the raw overrides. Text inputs commit after 300 ms. |
| Overlay | `editor/src/Overlay.svelte` | Hover and selection outlines, the block label and toolbar (drag grip, up, down, duplicate, remove) and the drop line are drawn in the editor document over the iframe from the wrapper's `getBoundingClientRect` (re-measured once per frame on scroll, resize, `ResizeObserver`, block render, drag). Nothing is injected into the page. |
| Drag and drop | `App.svelte` (`pointerDown` …) | Pointer events, no library: palette variant → structure list or preview; structure grip or overlay grip → list or preview. Keyboard: Alt+↑/↓ on a structure item, Esc deselects. |
| Store and history | `store.svelte.ts`, `history.ts` | `EditorStore` on runes (blocks, selection, hover). Every change is one `commit`; `History` keeps 50 JSON snapshots, changes with the same key within 1 s (typing in one field) are one step, sealed when the field loses focus. Ctrl/Cmd+Z, Ctrl/Cmd+Shift+Z, Ctrl+Y (also with focus in the preview) and the Undo/Redo buttons; text inputs keep their native undo while focused. Undo re-renders only the blocks whose JSON changed. |
| IDE layout | `layout.ts`, `layout.svelte.ts`, `Splitter.svelte` | Column separators (WAI-ARIA `separator`, focusable): drag, ←/→ by 8px (Shift: 32px), Home/End, double click or Enter resets; blocks 200–480px (default 248), inspector 280–560px (default 336). Columns collapse with the bar buttons, `[` and `]`; `F` is focus mode (canvas over the whole window, the block toolbar stays; `F` or Esc leaves). Single-key shortcuts never fire in input, textarea, select or contenteditable and match the physical key (any keyboard layout). Widths and collapsed columns live in localStorage under `wpfasty-builder:layout:<user id>` (`config.user`; shared key without it); focus mode is not remembered. |
| Design | `editor/src/tokens.css`, `editor.css` | Colours only from tokens (mapped to the wp-admin theme colour), 8 px layout grid (4 px only inside controls) — checked by `bun run lint:design`; icons: Lucide (`lucide` node data rendered by `Icon.svelte`, tree-shaken). |
| Dark mode | `theme.ts`, `theme.svelte.ts`, `src/Admin.php` (`darkSwitch`) | A toggle button (Lucide moon / sun inline, `aria-pressed`, label "Dark mode" via `__()`) is the last node of the wp-admin toolbar (`top-secondary`, `admin_bar_menu` priority 100000), only on the Builder screen with a page open (screen id = the hook of Pages → Builder). The editor wires it up: class `dark` on `.wpfb-app` (dark `--wpfb-*` tokens in `tokens.css`; wp-admin buttons, textareas, selects and the page around the editor follow), on the preview page's `<html>` (the theme's shadcn `.dark` tokens — `themes/latte`; a child palette without `.dark` stays light) and the block cards (`preview.dark`, light shot as fallback). Remembered in localStorage under `wpfasty-builder:dark:<user id>`: a display preference of this browser like the panel layout — no REST round trip or nonce, read before the editor's first paint. |
| New pages | `src/Pages.php`, `src/Starters.php`, `starters/*.json`, `NewPage.svelte`, `NewPageScreen.svelte`, `slug.ts` | "Add New with Builder" next to "Add New" on Pages (inline script after `.page-title-action`) → Builder `?new=1`; row action "Edit with Builder" (`page_row_actions`, `edit_page`); "New page" on the Builder page chooser and in the editor bar. The panel (native modal `<dialog>`, right-hand sheet): Title, Slug (from the title until edited, like `sanitize_title`), Start with: Empty or a demo landing available on this site. `POST wpfasty-builder/v1/pages` (`edit_pages`, REST nonce) creates the draft page with a unique slug and puts the starter's blocks into its Builder draft (`Store::save`, not published) in one request, removing the page if the layout cannot be saved; the editor then opens it. Starters = the `layout` of `tools/builder/landings/{shop,folio,press}.json` (kept equal by `tests/PagesTest.php`), offered only when every block / variant is in the catalog and every bound data source exists (filter `wpfasty_builder_starters`). A starter is only blocks: the WordPress page template is never set. |

### Next step: nesting (slots) — not implemented

Blocks are a flat list today (contract v0.4 allows depth, the editor does not). The plan, in Puck's terms:

1. **Catalog:** a block variant declares named slots (`slots: [{name, accepts?: [blockId…], max?}]`) — generated by
   codegen-x from the spec, like `records`; the template renders a slot with a placeholder the compiler fills
   (`{block slot-name}` / Twig `{% block %}`-free include of the children's HTML, no engine-specific logic in the
   catalog).
2. **Layout:** `Item.slots?: Record<string, Item[]>`; `Layout::normalize` validates depth (≤ 8), `accepts`, `max` and
   the 40-block total across the tree; the compiler renders children first and passes them as raw HTML props.
3. **Editor:** the store addresses an item by a path (`[index, slot, index, …]`) instead of an index; the structure
   column becomes a tree (indent, collapse); drop targets are computed per slot wrapper (`[data-wpfb-slot]`) by the
   same overlay measurement; `render-block` renders a subtree (the parent with its children), so a change inside a
   slot re-renders the nearest top-level ancestor.
4. **Checks:** parity and `render-block-check` over generated nested samples; undo stays snapshot-based (the tree is
   still small JSON).
