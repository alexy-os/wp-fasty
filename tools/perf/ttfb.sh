#!/usr/bin/env bash
# Server response time (TTFB) of the local dev site: median and p90 of N warm runs per URL (first 3 runs discarded).
#   bash tools/perf/ttfb.sh [base-url] [runs]      e.g. bash tools/perf/ttfb.sh http://127.0.0.1:8080 20
set -euo pipefail
BASE=${1:-http://127.0.0.1:8080}
RUNS=${2:-20}
for u in / "/?p=1" "/?cat=1" "/?s=post" "/?author=1" "/?page_id=999999"; do
  for _ in $(seq 1 $((RUNS + 3))); do curl -s -o /dev/null -w '%{time_starttransfer}\n' "$BASE$u"; done \
    | tail -n "$RUNS" | sort -n \
    | awk -v u="$u" '{a[NR]=$1} END {printf "%-18s median %4.0f ms  p90 %4.0f ms\n", u, a[int((NR+1)/2)]*1000, a[int(NR*0.9)]*1000}'
done
