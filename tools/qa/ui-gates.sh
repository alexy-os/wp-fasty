#!/usr/bin/env bash
# UI8Kit 3 gates (vendored codegen-x/ui8kit ui-gates, tools/ui-gates/VENDORED.md):
#   source — no raw HTML where a primitive exists, Box/Text tag allowlist (views, presets, builder sources; kit and
#            primitives, incl. the ui/ copy of the builder sources, excluded) — must be 0;
#   html   — html-validate + axe-core over every builder block × variant, both engines, and over every theme parity
#            render (full documents: the test wp_head() stub prints <title> like core title-tag) — must be 0.
set -euo pipefail
cd "$(dirname "$0")/../.."
G="node tools/ui-gates/bin/ui-gates.mjs"
OUT=${OUT:-${TMPDIR:-/tmp}/wpfasty-gates}  # not under dist/ (dist/*/ are site packages)
mkdir -p "$OUT"
$G source themes/latte/views presets/blocks presets/archetypes themes/latte-press/views themes/latte-folio/views \
  themes/latte-shop/views themes/latte-skin plugins/wpfasty-builder/sources/ui8kit/latte plugins/wpfasty-builder/sources/ui8kit/twig \
  --exclude themes/latte/kit --exclude themes/latte/twig/kit \
  --exclude plugins/wpfasty-builder/sources/ui8kit/latte/ui --exclude plugins/wpfasty-builder/sources/ui8kit/twig/ui
php tools/qa/gate-cases.php blocks "$OUT/blocks.json"
$G html "$OUT/blocks.json"
rm -rf "$OUT/theme" && PARITY_DUMP="$OUT/theme" php tools/parity/parity.php >/dev/null
php tools/qa/gate-cases.php theme "$OUT/theme" "$OUT/theme.json"
set +e
$G html "$OUT/theme.json" | tee "$OUT/theme-html.txt"
rc=${PIPESTATUS[0]}
set -e
n=$(tail -1 "$OUT/theme-html.txt" | grep -oE "[0-9]+" | head -1)
echo "theme html: ${n:-?} problem(s) (must be 0)"
[ "$rc" -eq 0 ] && [ "${n:-1}" -eq 0 ]
