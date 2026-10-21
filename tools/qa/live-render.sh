#!/usr/bin/env bash
# Live Latte ↔ Twig check on a running WordPress (QA site / CI): for one theme, fetch every URL with the theme
# renderer forced to latte and to twig (mu-plugin tools/qa/renderer-switch.php, option wpfasty_qa_renderer),
# require the expected HTTP code, no PHP notices, Twig really used, and identical normalized HTML.
#   tools/qa/live-render.sh <wp-dir> <base-url> <theme> <path>[|code[|nocompare]] …
# nocompare: builder landings (Latte-only by design) render as a normal page under Twig, so only code/notices count.
set -uo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
WP="$1"; BASE="$2"; THEME="$3"; shift 3
cd "$WP" || exit 2
wp theme activate "$THEME" --quiet
fail=0
for spec in "$@"; do
  IFS='|' read -r path want mode <<<"$spec"; want="${want:-200}"
  for eng in latte twig; do
    wp option update wpfasty_qa_renderer "$eng" --quiet
    code=$(curl -s -o "/tmp/live-$eng.html" -w '%{http_code}' "$BASE$path" || true)
    notices=$(grep -cE '<b>(Warning|Notice|Deprecated|Fatal error)</b>|critical error' "/tmp/live-$eng.html" || true)
    if [ "$code" != "$want" ] || [ "$notices" -ne 0 ]; then
      echo "✗ $THEME $path [$eng]: HTTP $code (want $want), notices $notices"; fail=1
    fi
  done
  if [ "${mode:-}" = nocompare ]; then
    echo "✓ $THEME $path: HTTP $code with both engines (not compared: $mode)"
  elif r=$(php "$ROOT/tools/parity/compare.php" /tmp/live-latte.html /tmp/live-twig.html); then
    echo "✓ $THEME $path: HTTP $code, latte = twig, $r"
  else
    echo "✗ $THEME $path: latte ≠ twig — $r"; fail=1
  fi
done
wp option delete wpfasty_qa_renderer --quiet
# Twig really rendered: compiled templates exist in the parent cache.
if ! ls "$(wp eval 'echo get_template_directory();')/~cache/twig" >/dev/null 2>&1; then echo "✗ no Twig cache — Twig never rendered"; fail=1; fi
exit $fail
