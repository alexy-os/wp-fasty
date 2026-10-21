#!/usr/bin/env bash
# Twig site without the Latte library (QA site / CI). Needs mu-plugins tools/qa/renderer-switch.php + no-latte.php.
#   tools/qa/no-latte.sh ref   <wp-dir> <base-url> <theme> <path>[|code] …  — Twig render with Latte installed → reference
#   tools/qa/no-latte.sh check <wp-dir> <base-url> <theme> <path>[|code] …  — after Latte is removed from the parent:
# expected HTTP code, no PHP notices, no `Latte\` class loaded (marker), DOM-identical to the reference.
# In `ref` mode the marker is checked too: the Twig path must not touch Latte even when it is installed.
set -uo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
MODE="$1"; WP="$2"; BASE="$3"; THEME="$4"; shift 4
OUT=/tmp/no-latte; mkdir -p "$OUT"
cd "$WP" || exit 2
wp theme activate "$THEME" --quiet || { echo "✗ $THEME: wp theme activate failed"; exit 1; }
wp option update wpfasty_qa_renderer twig --quiet
fail=0
for spec in "$@"; do
  IFS='|' read -r path want <<<"$spec"; want="${want:-200}"
  key="$OUT/$THEME$(echo "$path" | tr -c 'A-Za-z0-9' '_')"
  f="$key.$MODE.html"
  code=$(curl -s --retry 3 --retry-delay 1 --retry-all-errors -o "$f" -w '%{http_code}' "$BASE$path" || true)
  notices=$(grep -cE '<b>(Warning|Notice|Deprecated|Fatal error)</b>|critical error' "$f" || true)
  latte=$(grep -c 'wpfasty-qa: latte-loaded' "$f" || true)
  if [ "$code" != "$want" ] || [ "$notices" -ne 0 ] || [ "$latte" -ne 0 ]; then
    echo "✗ $THEME $path [$MODE]: HTTP $code (want $want), notices $notices, Latte loaded $latte"; fail=1; continue
  fi
  if [ "$MODE" = ref ]; then echo "✓ $THEME $path [twig, Latte installed]: HTTP $code, no Latte class loaded"; continue; fi
  if r=$(php "$ROOT/tools/parity/compare.php" "$key.ref.html" "$f"); then
    echo "✓ $THEME $path [twig, Latte absent]: HTTP $code, = with-Latte render, $r"
  else
    echo "✗ $THEME $path [twig, Latte absent] ≠ with-Latte render — $r"; fail=1
  fi
done
wp option delete wpfasty_qa_renderer --quiet
exit $fail
