#!/usr/bin/env bash
# Publish playground/*.zip to the preview gist as <name>.zip.b64 (base64, one line) — the raw URLs the blueprints and
# playground/urls.txt point at (no revision in the URL, so they never change).
#
#   tools/playground-publish.sh <gist-checkout> [--push]
#
# A zip is rewritten only when its content changed: entries are compared by name + bytes, ignoring what differs on
# every build (zip timestamps, the .pot POT-Creation-Date line, Composer's autoloader suffix and the source commit in
# vendor/composer/installed.*). Without --push
# the gist checkout is updated and the diff shown, nothing is committed. With --push: one commit (author Grok Designer)
# when something changed, a plain push, then every raw URL is checked — the revision-pinned one must be 200 with the
# zip's sha256 at once; the plain one (what the blueprints load) must be 200 and is waited for while the gist CDN still
# serves the old file (up to ~6 min; a stale copy after that is a warning, it clears by itself).
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
GIST="${1:?gist checkout dir}"
PUSH="${2:-}"
OWNER_ID="alexy-os/534c59bcd01563580218570cfac273ca"
RAW="https://gist.githubusercontent.com/$OWNER_ID/raw"

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
        b = re.sub(rb"([\x27\"]reference[\x27\"]\s*(=>|:)\s*)[\x27\"][0-9a-f]{40}[\x27\"]", rb"\1null", b)  # the repo commit
    h.update(n.encode() + b"\0" + hashlib.sha256(b).digest())
print(h.hexdigest())'
}

NAMES="moduly moduly-cart moduly-orders moduly-types moduly-downloads moduly-consent-log moduly-cookie-consent moduly-reviews wp-fasty-market demo latte latte-folio latte-press latte-shop latte-shop-twig twig wpfasty-builder wpfasty-builder-twig wpfasty-types"
for n in $NAMES; do [ -s "$ROOT/playground/$n.zip" ] || { echo "::error::playground/$n.zip missing — run tools/playground-build.sh"; exit 1; }; done

changed=()
for zip in "$ROOT"/playground/*.zip; do
  name="$(basename "$zip")"; b64="$GIST/$name.b64"
  if [ -f "$b64" ] && [ "$(base64 -d "$b64" | fingerprint)" = "$(fingerprint < "$zip")" ]; then
    echo "same     $name"
    continue
  fi
  base64 -w0 "$zip" > "$b64"
  changed+=("$name"); echo "changed  $name ($(stat -c %s "$zip") B)"
done

cd "$GIST"
if [ -z "$(git status --porcelain)" ]; then
  echo "gist up to date — nothing to publish"
  [ "$PUSH" = "--push" ] || exit 0
else
  git status --short
  [ "$PUSH" = "--push" ] || { echo "(dry run: not committed)"; exit 0; }
  SRC="$(git -C "$ROOT" rev-parse --short HEAD)"
  git add -A
  git -c user.name='Grok Designer' -c user.email='grokbot@users.noreply.github.com' commit -q \
    -m "Rebuild $(date -u +%F) from wp-fasty $SRC: ${changed[*]}"
  git push -q origin HEAD
  REV="$(git rev-parse HEAD)"
  echo "gist revision $REV"
fi

fail=0
for zip in "$ROOT"/playground/*.zip; do
  name="$(basename "$zip")"; want="$(sha256sum "$zip" | cut -d' ' -f1)"
  if [ -n "${REV:-}" ] && [[ " ${changed[*]} " == *" $name "* ]]; then
    got="$(curl -sSfL --retry 3 "$RAW/$REV/$name.b64" | base64 -d | sha256sum | cut -d' ' -f1)" || got="(fetch failed)"
    if [ "$got" = "$want" ]; then echo "pinned ok $name"; else echo "::error::$name pinned raw sha256 $got ≠ $want"; fail=1; fi
  fi
  # the URL the blueprints load; a just-replaced file may be served stale by the gist CDN for a few minutes
  for i in $(seq 1 13); do
    code="$(curl -sSL -o /tmp/pg-raw.b64 -w '%{http_code}' "$RAW/$name.b64" || echo 000)"
    got="$(base64 -d /tmp/pg-raw.b64 2>/dev/null | sha256sum | cut -d' ' -f1)"
    [ "$code" = 200 ] && [ "$got" = "$want" ] && break
    [ "$code" = 200 ] && [ "$(base64 -d /tmp/pg-raw.b64 2>/dev/null | fingerprint)" = "$(fingerprint < "$zip")" ] && break
    sleep 30
  done
  if [ "$code" != 200 ]; then echo "::error::$name raw URL HTTP $code"; fail=1
  elif [ "$got" != "$want" ] && [ "$(base64 -d /tmp/pg-raw.b64 | fingerprint)" != "$(fingerprint < "$zip")" ]; then
    echo "::warning::$name raw URL still serves the previous file (gist CDN cache, clears within minutes)"
  else echo "raw ok    $name ($( [ "$got" = "$want" ] && echo "sha256 $want" || echo "same content, gist copy kept"))"; fi
done
exit $fail
