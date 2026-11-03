#!/usr/bin/env bash
# schema/theme-context.schema.json is a byte copy of the view profile schema of @ui8kit/contract
# (schemas/view.schema.json, https://schema.buildy.tw/view.schema.json) at the commit pinned in schema/VIEW_SCHEMA_SOURCE.
# Never edit it here: change the contract, then bump the pin and re-copy (tools/qa/view-schema-sync.sh --update).
set -euo pipefail
cd "$(dirname "$0")/../.."
read -r repo sha path < schema/VIEW_SCHEMA_SOURCE
url="https://raw.githubusercontent.com/${repo}/${sha}/${path}"
tmp="$(mktemp)"; trap 'rm -f "$tmp"' EXIT
curl -fsSL "$url" -o "$tmp"
if [ "${1:-}" = "--update" ]; then cp "$tmp" schema/theme-context.schema.json; echo "view-schema-sync: updated from $url"; exit 0; fi
if cmp -s "$tmp" schema/theme-context.schema.json; then echo "view-schema-sync: OK ($repo@${sha:0:7})"; else echo "view-schema-sync: schema/theme-context.schema.json drifted from $url" >&2; exit 1; fi
