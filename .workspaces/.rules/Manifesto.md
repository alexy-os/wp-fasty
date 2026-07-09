# WP FastY Manifesto

*Copy nothing — generate your own.*

## Vision

A WordPress theme framework for people who want **unique** sites without
copying bloated starters. You pick a layout, blocks, and a color style;
the generator assembles a lean Latte theme on top of UI8Kit primitives.

## Why not Underscores alone?

`_s` gives a PHP skeleton. We add:

- Shared **UI primitives** with a verified DOM/ARIA contract
- A **preset catalog** (layouts × blocks × styles)
- **Latte** presentation with WordPress as a thin data adapter
- A path to ship many themes from one monorepo

## Why not FSE-first?

Full Site Editing couples view and model in nested blocks and ships a
heavy client runtime. FastY keeps presentation in Latte, data in typed
context, and behavior optional via `@ui8kit/aria` at the app level.

## Non-negotiables

1. Primitives come from codegen — not hand-forked CSS class soups
2. Templates stay free of WordPress queries
3. Generated Latte bundles are regenerated, never patched in place
4. Security and accessibility beat clever abstractions

## Audience

Theme authors and agencies who want a generator, not another theme to fork.
