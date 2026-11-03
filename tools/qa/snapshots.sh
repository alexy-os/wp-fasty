#!/usr/bin/env bash
# HTML snapshot parity of the theme: render the same pages under several themes and compare two captures.
#   bash tools/qa/snapshots.sh capture <out-dir> <wp-path> [base-url]   render THEMES × URLS into <out-dir>
#   bash tools/qa/snapshots.sh compare <dir-a> <dir-b>                  exit 1 when a page differs
# Normalized before comparing: antispambot() mailto obfuscation (random per request) and line order (posts with the
# same date come back in any order); tags are split one per line first.
# THEMES / URLS can be overridden through the environment.
set -euo pipefail
THEMES=${THEMES:-"wp-fasty latte-press latte-folio latte-shop"}
URLS=${URLS:-"/ /?p=1 /?cat=1 /?s=hello /?author=1 /?page_id=999999 /?cat=1&paged=2"}

normalize() { sed 's/></>\n</g; s/mailto:[^"]*/mailto:X/g' "$1" | sort; }

case "${1:-}" in
  capture)
    out=$2; wp=$3; base=${4:-http://127.0.0.1:8080}
    mkdir -p "$out"
    active=$(wp --path="$wp" theme list --status=active --field=name)
    for t in $THEMES; do
      wp --path="$wp" theme activate "$t" --quiet
      for u in $URLS; do
        name="$t$(echo "$u" | tr '/?&=' '____').html"
        curl -sL --retry 3 --retry-all-errors -o "$out/$name" -w "%{http_code} $t $u\n" "$base$u" >> "$out/status.txt"
      done
    done
    wp --path="$wp" theme activate "$active" --quiet
    if grep -lE 'Fatal error|Warning:|Notice:|Deprecated:' "$out"/*.html; then echo "PHP messages in $out" >&2; exit 1; fi
    echo "captured $(ls "$out"/*.html | wc -l) pages into $out"
    ;;
  compare)
    a=$2; b=$3; n=0; total=0
    for f in "$a"/*.html; do
      total=$((total + 1)); g="$b/$(basename "$f")"
      if [ ! -f "$g" ] || ! cmp -s <(normalize "$f") <(normalize "$g"); then
        n=$((n + 1)); echo "DIFF $(basename "$f")"
        [ -f "$g" ] && diff <(normalize "$f") <(normalize "$g") | head -20 || true
      fi
    done
    cmp -s "$a/status.txt" "$b/status.txt" || { echo "HTTP status differs:"; diff "$a/status.txt" "$b/status.txt" || true; n=$((n + 1)); }
    echo "snapshots: $total pages, $n differ"
    [ "$n" -eq 0 ]
    ;;
  *) sed -n '2,7p' "$0"; exit 2 ;;
esac
