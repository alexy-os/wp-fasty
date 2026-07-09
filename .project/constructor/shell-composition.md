# Shell composition — full page recipe

Target: wp-fasty **presets** composed from Codegen Latte primitives.  
Shared grammar/ARIA: [`docs/layout-grammar.md`](../../docs/layout-grammar.md), [`docs/aria.md`](../../docs/aria.md).

## Document shell (theme layout)

```
<html>
  <head> {wp_head} + theme CSS + ui8kit.js </head>
  <body {body_class}>
    {content from page template}
    {wp_footer}
  </body>
</html>
```

Raw HTML allowed **only** here. No duplicate site headers inside every page block.

## Block page recipe (catalog / app shell)

```
Block(tag="main", shell background)          ← one Block
├── MobileSheet(props)                       ← overlay sibling (first)
└── Group(min-h-screen, items-stretch)       ← horizontal shell
    ├── Sidebar(props)                       ← aside, md+ only
    └── Box(flex-1 column)
        ├── Header(props)                    ← includes SheetTrigger
        └── Box(content, gap-8, p-4 md:p-8)
            └── [page sections…]
```

### Responsive split

| Surface | Classes / behavior |
|---------|-------------------|
| Sidebar | `hidden md:flex` (desktop) |
| Mobile sheet | `md:hidden` panel |
| Header center nav | `hidden md:block` |
| Sheet trigger | In header left group (mobile) |

## Sidebar recipe

```
Block(tag="aside", w-64, border-r, bg-card, hidden md:flex md:flex-col)
├── Box (brand row, h-16, border-b)
│   ├── IconBadge / brand mark
│   └── Stack (brand + subtitle)
├── Box (flex-1) → PrimaryNav(items)
└── Box (footer, border-t) → link / ButtonClasses on <a>
```

Three sibling `Box` sections are OK when the Block is already a column flex.

## Header recipe

```
Block(tag="header", h-16, grid 3 columns)
├── Group (start) — SheetTrigger + workspace label
├── Nav (center, md+) — HeaderNav items
└── Group (end) — Badge + profile control
```

## Mobile sheet recipe

```
Sheet(id=panelID, side=left, behavior=ui8kit, md:hidden)
├── SheetOverlay(panelId)
└── SheetContent
    ├── SheetTitle(id=titleID) + SheetClose(panelId)
    ├── PrimaryNav(same as sidebar)
    └── HeaderNav(same as header center, with border-t)
```

**DRY:** Same nav partials in sidebar and sheet.  
**IDs:** Twin constants `{block}-mobile-sheet-{trigger|panel|title}`.

## Nav partials

| Partial | ARIA | Data | Active |
|---------|------|------|--------|
| PrimaryNav | `aria-label="Primary navigation"` | `Sidebar[]` | e.g. current item / first |
| HeaderNav | `aria-label="Header navigation"` | `HeaderNav[]` | usually none |

Use `list/List` + `link/Link` (or future `components/Nav`) — data-driven from context JSON.

## Data envelope (runtime-neutral)

```json
{
  "id": "blocks.home",
  "showcase": {
    "default": {
      "props": {
        "Brand": "…",
        "Sidebar": [{ "Label": "…", "Href": "…", "Icon": "…" }],
        "HeaderNav": […],
        "Hero": { … }
      }
    }
  }
}
```

- Demo/product copy in fixtures or WP context — **not** in generated primitives
- Accessible names for icon-only controls live in data/context

## WordPress mapping

| Templ examples | wp-fasty |
|----------------|----------|
| `page.templ` document | `views/layout/default.latte` |
| `blocks/home/page.templ` | `presets/layouts/*` + `presets/blocks/*` or theme page view |
| `*.data.json` | `ContextFactory` + optional JSON fixtures |
| `ui8kit.js` | Enqueued in theme assets |
| Twin sheet-ids | `presets/.../sheet-ids.php` or generated constants |

## Content vs shell

| Shell (reusable) | Content (page-specific) |
|------------------|-------------------------|
| MobileSheet, Sidebar, Header | Hero, cards, tables, notices |
| Brand, Sidebar[], HeaderNav[] | Section props from context |

Theme-builder should emit shell once per layout preset; swap content blocks via theme manifest.
