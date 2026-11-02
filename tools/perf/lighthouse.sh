#!/usr/bin/env bash
# Lighthouse scores (performance, accessibility, best-practices, seo) of the local dev site, mobile and desktop.
# Needs Chrome and the lighthouse CLI (bunx/npx lighthouse@12 is fetched on demand).
#   bash tools/perf/lighthouse.sh [base-url] [out-dir]
set -euo pipefail
BASE=${1:-http://127.0.0.1:8080}
OUT=${2:-var/lighthouse}
mkdir -p "$OUT"
LH=${LIGHTHOUSE:-"bunx lighthouse@12"}
for u in / "/?p=1" "/?cat=1"; do
  for preset in mobile desktop; do
    name=$(echo "$u" | tr '/?=&' '____')
    extra=(); [ "$preset" = desktop ] && extra=(--preset=desktop)
    $LH "$BASE$u" --quiet --chrome-flags="--headless=new --no-sandbox" --output=json --output-path="$OUT/$preset$name.json" \
      --only-categories=performance,accessibility,best-practices,seo "${extra[@]}" >/dev/null 2>&1 || true
    php -r '$j=json_decode((string)@file_get_contents($argv[1]),true); if(!$j){echo "$argv[2] $argv[3]: failed\n";exit;} $c=$j["categories"]; printf("%-8s %-10s perf %3d  a11y %3d  bp %3d  seo %3d\n",$argv[2],$argv[3],$c["performance"]["score"]*100,$c["accessibility"]["score"]*100,$c["best-practices"]["score"]*100,$c["seo"]["score"]*100);' \
      "$OUT/$preset$name.json" "$preset" "$u"
  done
done
