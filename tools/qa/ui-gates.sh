#!/usr/bin/env bash
# UI8Kit 3 gates (vendored codegen-x/ui8kit ui-gates, tools/ui-gates/VENDORED.md):
#   source — no raw HTML where a primitive exists, Box/Text tag allowlist (views, presets, builder sources; kit and
#            primitives excluded) — must be 0;
#   html   — html-validate + axe-core over every builder block × variant, both engines — must be 0; over the theme
#            parity renders — must not exceed THEME_HTML_BUDGET (known: fixture documents without <title>, WP stub
#            markup, repeated landmarks / image-only links; see docs) .
set -euo pipefail
cd "$(dirname "$0")/../.."
G="node tools/ui-gates/bin/ui-gates.mjs"
OUT=${OUT:-${TMPDIR:-/tmp}/wpfasty-gates}  # not under dist/ (dist/*/ are site packages)
THEME_HTML_BUDGET=${THEME_HTML_BUDGET:-212}
mkdir -p "$OUT"
$G source themes/latte/views presets/blocks presets/archetypes themes/latte-press/views themes/latte-folio/views \
  themes/latte-shop/views themes/latte-skin plugins/wpfasty-builder/sources/ui8kit/latte plugins/wpfasty-builder/sources/ui8kit/twig \
  --exclude themes/latte/kit --exclude themes/latte/twig/kit
php tools/qa/gate-cases.php blocks "$OUT/blocks.json"
$G html "$OUT/blocks.json"
rm -rf "$OUT/theme" && PARITY_DUMP="$OUT/theme" php tools/parity/parity.php >/dev/null
php tools/qa/gate-cases.php theme "$OUT/theme" "$OUT/theme.json"
n=$( ($G html "$OUT/theme.json" || true) | tee "$OUT/theme-html.txt" | tail -1 | grep -oE "[0-9]+" | head -1)
echo "theme html: $n problem(s) (budget $THEME_HTML_BUDGET)"
[ "$n" -le "$THEME_HTML_BUDGET" ]
