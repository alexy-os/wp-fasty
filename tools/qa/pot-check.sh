#!/usr/bin/env bash
# The committed .pot files are up to date (same targets as `bun run i18n:pot`): regenerate each one and compare the
# strings (msgctxt/msgid/msgid_plural, translator comments, flags). `#:` source references and the creation date are
# ignored, so moving code around does not fail the check; a new, changed or removed string does.
set -euo pipefail
root=$(cd "$(dirname "$0")/../.." && pwd)
tmp=$(mktemp)
trap 'rm -f "$tmp"' EXIT
norm() { grep -vE '^#: |^"POT-Creation-Date: |^"X-Generator: ' "$1"; }
fail=0
check() { # <dir> <pot> <domain> <exclude>
  wp i18n make-pot "$root/$1" "$tmp" --domain="$3" --exclude="$4" --quiet
  if ! diff -u <(norm "$root/$2") <(norm "$tmp"); then
    echo "::error file=$2::$2 is stale: run bun run i18n:pot and commit it"
    fail=1
  else
    echo "$2 up to date ($(grep -c '^msgid ' "$root/$2") msgids)"
  fi
}
for t in wp-fasty latte-press latte-folio latte-shop latte-skin; do
  check "themes/$t" "themes/$t/languages/$t.pot" "$t" vendor,~cache,node_modules
done
check plugins/wpfasty-builder plugins/wpfasty-builder/languages/wpfasty-builder.pot wpfasty-builder \
  editor,node_modules,sources,primitives,assets,starters
exit $fail
