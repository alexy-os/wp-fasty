# Principles — Three WhalY

Every change in wp-fasty should reinforce:

## 1. Zero redundant code

- One source of truth for UI primitives (ui8kit/codegen → Latte bundle)
- No parallel component kits or transpile pipelines
- Theme views compose; they do not re-implement Button/Card

## 2. Zero wasteful work

- Context built once per request; Latte is presentation-only
- Prefer static CSS delivery; avoid client-side theme runtimes
- Skip regenerating primitives unless bricks changed

## 3. Perfect PageSpeed goal

Target 100/100 Performance, Accessibility, Best Practices, SEO:

- Minimal DOM depth; semantic HTML5
- Server-rendered Latte; no FSE React payload for theme chrome
- Accessible primitives (ARIA contract from UI8Kit)
- Critical CSS / token-based theming without bloat

## FlightPHP note

Historical docs mentioned Flight CMS. **This repository is WordPress-first.**
Flight-related principles apply only if a future headless/Flight adapter is added;
do not assume Flight DI or routing inside theme PHP.
