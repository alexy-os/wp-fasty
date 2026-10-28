#!/usr/bin/env bash
# Browser check of the editor preview sandbox (CI): the sandbox value of the preview <iframe> in
# plugins/wpfasty-builder/editor/src/App.svelte is put on a fixture iframe (tools/security/sandbox/), served over HTTP
# and loaded in headless Chrome. The editor side must keep working (contentDocument, its click / scroll listeners,
# the dark class, layout) and nothing of the preview may run in the editor origin: no inline <script>, no onerror of
# the page, no handler of markup swapped in with innerHTML (render-block). A control run without the sandbox must show
# all three running, so the check is not vacuous. Exit 1 on failure.
set -euo pipefail
root="$(cd "$(dirname "$0")/../.." && pwd)"
chrome="${CHROME:-$(command -v google-chrome || command -v chromium || command -v chromium-browser || true)}"
[ -n "$chrome" ] || { echo "sandbox-check: no Chrome/Chromium found" >&2; exit 2; }
sb="$(grep -o '<iframe[^>]*sandbox="[^"]*"' "$root/plugins/wpfasty-builder/editor/src/App.svelte" | sed 's/.*sandbox="\([^"]*\)".*/\1/')"
[ -n "$sb" ] || { echo "sandbox-check: the preview iframe has no sandbox attribute" >&2; exit 1; }
dir="$(mktemp -d)"
cp "$root/tools/security/sandbox/child.html" "$dir/child.html"
sed "s/__SANDBOX__/$sb/" "$root/tools/security/sandbox/index.html" > "$dir/index.html"
sed 's/ sandbox="__SANDBOX__"//' "$root/tools/security/sandbox/index.html" > "$dir/control.html"
port="${SANDBOX_PORT:-$(php -r '$s = stream_socket_server("tcp://127.0.0.1:0"); echo explode(":", stream_socket_get_name($s, false))[1];')}"
php -S "127.0.0.1:$port" -t "$dir" >/dev/null 2>&1 &
srv=$!
trap 'kill $srv 2>/dev/null || true; rm -rf "$dir"' EXIT
for _ in $(seq 1 50); do
  curl -fsS -o /dev/null "http://127.0.0.1:$port/child.html" 2>/dev/null && break
  sleep 0.1
done
probe() {
  timeout 60 "$chrome" --headless=new --no-sandbox --disable-gpu --virtual-time-budget=3000 --dump-dom \
    "http://127.0.0.1:$port/$1" 2>/dev/null | grep -o '<pre id="r"[^>]*>' || true
}
# the scroll listener fires on a timer of the fixture: on a loaded machine one run can miss it — up to three runs
# for the editor-side checks; the "nothing ran" checks below see every run
all=""
for _ in 1 2 3; do
  got="$(probe index.html)"
  all="$all $got"
  [[ "$got" == *'data-scroll="yes"'* ]] && break
done
ctl="$(probe control.html)"
echo "sandbox=\"$sb\": $got"
echo "control (no sandbox): $ctl"
fail=0
for want in 'data-access="yes"' 'data-click="b1"' 'data-scroll="yes"' 'data-dark="yes"' 'data-rect="true"'; do
  [[ "$got" == *"$want"* ]] || { echo "✗ editor side broken: $want missing" >&2; fail=1; }
done
for bad in 'data-script=' 'data-onerror=' 'data-injected=' 'data-svg='; do
  [[ "$all" != *"$bad"* ]] || { echo "✗ preview code ran in the editor origin: $bad" >&2; fail=1; }
done
for ran in 'data-script="ran"' 'data-onerror="ran"' 'data-injected="ran"'; do
  [[ "$ctl" == *"$ran"* ]] || { echo "✗ control run did not execute ($ran): the check would be vacuous" >&2; fail=1; }
done
[ $fail -eq 0 ] && echo "sandbox-check: preview scripts blocked, editor access intact"
exit $fail
