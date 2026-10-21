#!/usr/bin/env bash
# Isolated engines (ADR-001, K7) on a running WordPress (QA site / CI). Needs mu-plugin tools/qa/engine-marker.php.
#   tools/qa/site-pair.sh fetch <wp-dir> <base-url> <theme> <out-dir> <path>[|code] …
#     activate <theme>, fetch every path: expected HTTP code (default 200), no PHP notices, the site engine's library
#     loaded and the other engine's library NOT loaded (class markers). The engine is the active parent (latte|twig).
#   tools/qa/site-pair.sh compare <dir-a> <slug-a> <dir-b> <slug-b>
#     parity as a quality check: the same site in both engines (X on latte.zip vs its twin on twig.zip) renders the
#     same normalized DOM; both theme slugs and the parent (dir, body class) are normalized before comparing.
set -uo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
key() { echo "$1" | tr -c 'A-Za-z0-9' '_'; }
case "${1:-}" in
fetch)
  WP="$2"; BASE="$3"; THEME="$4"; OUT="$5"; shift 5
  mkdir -p "$OUT"; cd "$WP" || exit 2
  wp theme activate "$THEME" --quiet || { echo "✗ $THEME: wp theme activate failed"; exit 1; }
  eng=$(wp eval 'echo get_template();'); other=$([ "$eng" = twig ] && echo latte || echo twig)
  fail=0
  for spec in "$@"; do
    IFS='|' read -r path want <<<"$spec"; want="${want:-200}"
    f="$OUT/$(key "$path").html"
    code=$(curl -s --retry 3 --retry-delay 1 --retry-all-errors -o "$f" -w '%{http_code}' "$BASE$path" || true)
    notices=$(grep -cE '<b>(Warning|Notice|Deprecated|Fatal error)</b>|critical error' "$f" || true)
    own=$(grep -c "wpfasty-qa: loaded $eng" "$f" || true)
    foreign=$(grep -c "wpfasty-qa: loaded $other" "$f" || true)
    if [ "$code" != "$want" ] || [ "$notices" -ne 0 ] || [ "$own" -ne 1 ] || [ "$foreign" -ne 0 ]; then
      echo "✗ $THEME $path [$eng]: HTTP $code (want $want), notices $notices, $eng loaded $own, $other loaded $foreign"; fail=1
    else
      echo "✓ $THEME $path [$eng]: HTTP $code, only $eng loaded"
    fi
    sed -i '/wpfasty-qa: loaded/d' "$f"
  done
  exit $fail ;;
compare)
  A="$2"; SA="$3"; B="$4"; SB="$5"; fail=0
  for a in "$A"/*.html; do
    case "$a" in *.norm) continue ;; esac
    b="$B/$(basename "$a")"; [ -f "$b" ] || { echo "✗ $SB: no $(basename "$a")"; fail=1; continue; }
    sed -e "s#/themes/twig/#/themes/latte/#g" -e "s#wp-theme-twig#wp-theme-latte#g" -e "s#$SB#@site@#g" "$b" > "$b.norm"
    sed -e "s#/themes/twig/#/themes/latte/#g" -e "s#wp-theme-twig#wp-theme-latte#g" -e "s#$SA#@site@#g" "$a" > "$a.norm"
    if r=$(php "$ROOT/tools/parity/compare.php" "$a.norm" "$b.norm"); then
      echo "✓ $SA = $SB $(basename "$a" .html): $r"
    else
      echo "✗ $SA ≠ $SB $(basename "$a" .html) — $r"; fail=1
    fi
  done
  exit $fail ;;
*) sed -n '2,9p' "$0"; exit 2 ;;
esac
