#!/usr/bin/env bash
# Publish playground/*.zip as assets of the rolling GitHub release `playground` (prerelease, not a version) — the
# URLs playground/shop/blueprint.json loads through the WordPress Playground CORS proxy:
#   https://wordpress-playground-cors-proxy.net/?https://github.com/alexy-os/wp-fasty/releases/download/playground/<name>.zip
#
#   tools/playground-release.sh [--push]      (needs gh with a token that can write releases: GH_TOKEN)
#
# A zip is replaced only when its content changed (same normalization as tools/playground-publish.sh: zip
# timestamps, .pot dates, Composer autoloader suffixes and the source commit are ignored). Without --push only the
# diff is shown. With --push every changed zip is uploaded (--clobber), then every asset is downloaded again through
# the proxy and must match the local sha256 — any failure exits non-zero (the workflow step fails).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PUSH="${1:-}"
REPO="${GITHUB_REPOSITORY:-alexy-os/wp-fasty}"
TAG=playground
URL="https://github.com/$REPO/releases/download/$TAG"
PROXY="https://wordpress-playground-cors-proxy.net/?"

fingerprint() { # stdin: zip bytes → sha256 of normalized entries
  python3 -c '
import hashlib, io, re, sys, zipfile
z = zipfile.ZipFile(io.BytesIO(sys.stdin.buffer.read()))
h = hashlib.sha256()
for n in sorted(i.filename for i in z.infolist() if not i.is_dir()):
    b = z.read(n)
    if n.endswith(".pot"): b = re.sub(rb"(?m)^\"(POT-Creation-Date|X-Generator):.*\n", b"", b)
    if "/vendor/" in n: b = re.sub(rb"(ComposerAutoloaderInit|ComposerStaticInit)[0-9a-f]{32}", rb"\1", b)
    if n.endswith(("/vendor/composer/installed.php", "/vendor/composer/installed.json")):
        b = re.sub(rb"([\x27\"]reference[\x27\"]\s*(=>|:)\s*)[\x27\"][0-9a-f]{40}[\x27\"]", rb"\1null", b)
    h.update(n.encode() + b"\0" + hashlib.sha256(b).digest())
print(h.hexdigest())'
}

NAMES="wp-fasty latte-shop latte-press latte-folio latte-shop-twig twig demo wpfasty-types wpfasty-builder wpfasty-builder-twig wpfasty-market moduly moduly-cart moduly-orders moduly-types moduly-downloads moduly-consent-log moduly-cookie-consent moduly-reviews moduly-licenses"
gh release view "$TAG" --repo "$REPO" >/dev/null 2>&1 || {
  [ "$PUSH" = "--push" ] || { echo "release $TAG missing (dry run)"; exit 0; }
  gh release create "$TAG" --repo "$REPO" --prerelease --title "Playground preview assets (rolling)" \
    --notes "Rolling zips for the WordPress Playground previews; replaced by the Playground workflow. Not a release."
}
tmp="$(mktemp -d)"; trap 'rm -rf "$tmp"' EXIT
changed=()
for n in $NAMES; do
  zip="$ROOT/playground/$n.zip"
  [ -s "$zip" ] || { echo "::error::playground/$n.zip missing — run tools/playground-build.sh"; exit 1; }
  if curl -sSfL -o "$tmp/$n.zip" "$URL/$n.zip" 2>/dev/null && [ "$(fingerprint < "$tmp/$n.zip")" = "$(fingerprint < "$zip")" ]; then
    echo "same     $n.zip"
  else
    echo "changed  $n.zip ($(stat -c %s "$zip") B)"; changed+=("$zip")
  fi
done
[ "$PUSH" = "--push" ] || { echo "(dry run: nothing uploaded)"; exit 0; }
[ ${#changed[@]} -eq 0 ] || gh release upload "$TAG" "${changed[@]}" --repo "$REPO" --clobber

fail=0
for n in $NAMES; do
  want="$(fingerprint < "$ROOT/playground/$n.zip")"
  got=""
  for i in 1 2 3 4 5; do
    code="$(curl -sSL -o "$tmp/check.zip" -w '%{http_code}' -H 'Origin: https://playground.wordpress.net' "$PROXY$URL/$n.zip" || echo 000)"
    [ "$code" = 200 ] && got="$(fingerprint < "$tmp/check.zip" 2>/dev/null || true)"
    [ "$got" = "$want" ] && break
    sleep 10
  done
  if [ "$got" = "$want" ]; then echo "ok       $n.zip (200 via CORS proxy)"; else echo "::error::$n.zip via proxy: HTTP $code, content differs"; fail=1; fi
done
exit "$fail"
