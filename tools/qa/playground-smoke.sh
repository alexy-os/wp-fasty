#!/usr/bin/env bash
# Local smoke of a Playground blueprint with @wp-playground/cli (PHP 8.5): pages answer 200 without a fatal error,
# ModulY and module versions are printed from the ModulY admin page, shop forms post the moduly_* actions.
# The CLI answers the first request with `302 Location: /` and a session cookie (auto-login), so every request goes
# through one cookie jar; without it curl loops on the redirect.
# Usage: tools/qa/playground-smoke.sh [blueprint=playground/shop/blueprint.json] [port=9400] [paths...]
set -euo pipefail
BP="${1:-playground/shop/blueprint.json}"; PORT="${2:-9400}"; shift $(( $# > 2 ? 2 : $# ))
PATHS=("$@"); [ ${#PATHS[@]} -gt 0 ] || PATHS=(/ /shop/ /cart/ /checkout/)
JAR=$(mktemp); LOG=$(mktemp); BODY=$(mktemp)
npx -y @wp-playground/cli@latest server --php=8.5 --port="$PORT" --blueprint="$BP" >"$LOG" 2>&1 &
PID=$!
trap 'kill $PID 2>/dev/null || true; pkill -f -- "--port=$PORT" 2>/dev/null || true; rm -f "$JAR" "$LOG" "$BODY"' EXIT
for _ in $(seq 1 200); do grep -q 'Ready!' "$LOG" && break; kill -0 $PID 2>/dev/null || { cat "$LOG"; exit 1; }; sleep 3; done
grep -q 'Ready!' "$LOG" || { echo "FAIL Playground did not start"; tail -20 "$LOG"; exit 1; }
get() { curl -sS -m 120 -L -b "$JAR" -c "$JAR" -o "$BODY" -w '%{http_code}' "http://127.0.0.1:$PORT$1"; }
fail=0
for p in "${PATHS[@]}"; do
  code=$(get "$p")
  if [ "$code" != 200 ] || grep -qiE 'fatal error|critical error' "$BODY"; then echo "FAIL $p: HTTP $code"; fail=1; else echo "ok   $p: 200"; fi
  grep -o 'name="action" value="moduly_[a-z]*"' "$BODY" | sort | uniq -c | sed 's/^/       /' || true
  if grep -q 'value="latte_shop_' "$BODY"; then echo "FAIL $p: a form posts a removed latte_shop_* action"; fail=1; fi
done
get /wp-admin/plugins.php >/dev/null
echo "ModulY plugin: $(tr '\n' ' ' < "$BODY" | grep -oE 'data-slug="moduly".{0,3000}' | grep -oE 'Version [0-9][0-9.]*' | head -1)"
get '/wp-admin/admin.php?page=moduly' >/dev/null
tr '\n' ' ' < "$BODY" | grep -oE '<code>[a-z-]+</code><p class="description">[^<]*</p></td><td>[0-9][0-9.]*</td><td>[A-Za-z]+' \
  | sed -E 's#<code>([a-z-]+)</code>.*<td>([0-9.]+)</td><td>([A-Za-z]+)#module \1 \2 \3#' || { echo "FAIL no modules listed"; fail=1; }
exit $fail
