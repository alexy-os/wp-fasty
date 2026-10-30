#!/usr/bin/env bash
# Build WordPress Playground preview artifacts into playground/:
#   latte.zip (parent, vendored), latte-press.zip, latte-folio.zip, latte-shop.zip, wp-fasty-market.zip, wpfasty-types.zip,
#   wpfasty-builder.zip, demo.zip (seeders, images, builder landings);
#   Twig shop: twig.zip (Twig parent, no Latte), latte-shop-twig.zip (generated Twig twin of latte-shop, only .html.twig
#   views), wpfasty-builder-twig.zip (Twig runtime only)
# Blueprints (playground/*.json) install them straight from the `improved` branch.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/playground"
cd "$ROOT"

bun run theme:pack -- latte >/dev/null 2>&1
cp dist/latte.zip "$OUT/latte.zip"
bun run theme:pack -- latte --engine twig >/dev/null 2>&1
cp dist/twig.zip "$OUT/twig.zip"
# Files on disk that git does not track (stale build leftovers) never go into a preview zip.
untracked() { git ls-files --others --exclude-standard -- "$1" | sed "s#^$1/##"; }

for t in latte-press latte-folio latte-shop wp-fasty-market; do
  rm -f "$OUT/$t.zip"
  (cd themes && zip -qr "$OUT/$t.zip" "$t" -x "*/~cache/*" "*/node_modules/*")
done

# Companion plugin (post types for folio/shop — wordpress.org plugin territory).
rm -f "$OUT/wpfasty-types.zip"
(cd plugins && zip -qr "$OUT/wpfasty-types.zip" wpfasty-types)

# Page builder (wp-admin only; the editor sources and node_modules stay out, the built assets go in).
rm -f "$OUT/wpfasty-builder.zip"
BUILDER_X=("wpfasty-builder/editor/*" "wpfasty-builder/primitives/*/ui8kit.lock.json" "wpfasty-builder/primitives/*/ui8kit.config.json")
while read -r f; do [ -n "$f" ] && BUILDER_X+=("$f"); done < <(untracked plugins)
(cd plugins && zip -qr "$OUT/wpfasty-builder.zip" wpfasty-builder -x "${BUILDER_X[@]}")
# Twig build of the builder: the Latte runtime's bundled source and primitives stay out (ADR-001, K7).
rm -f "$OUT/wpfasty-builder-twig.zip"
(cd plugins && zip -qr "$OUT/wpfasty-builder-twig.zip" wpfasty-builder -x "${BUILDER_X[@]}" "wpfasty-builder/sources/ui8kit/latte/*" "wpfasty-builder/primitives/latte/*")

# Twig twin of latte-shop (tools/matrix/engine-variants.ts → theme:package): child zip with Template: twig.
bun tools/matrix/engine-variants.ts >/dev/null
TWIN="$(mktemp -d)"
bun run theme:package dist/answers-matrix/manifest-latte-shop-twig.json --out "$TWIN" >/dev/null
cp "$TWIN/latte-shop-twig.zip" "$OUT/latte-shop-twig.zip"
rm -rf "$TWIN"

STAGE="$(mktemp -d)"
mkdir -p "$STAGE/wpfasty-demo/images"
cp tools/demo/*.php "$STAGE/wpfasty-demo/"
# Builder landings (WPFastyBuilder\Seeder::landing in the blueprints).
mkdir -p "$STAGE/wpfasty-demo/landings"
cp tools/builder/landings/*.json "$STAGE/wpfasty-demo/landings/"
# Seeds used by tools/demo/*.php — fetched once, bundled so Playground needs no network.
php -r '
foreach (glob("tools/demo/*.php") as $f) {
  $src = file_get_contents($f);
  preg_match_all("/\x27([a-z0-9-]+)\x27\]/", $src, $m1);           // last element of tuple rows = image seed
  preg_match_all("/wpfasty_demo_image\(\x27([a-z0-9-]+)\x27/", $src, $m2);
  $seeds = array_merge($m1[1], $m2[1]);
  $prefix = str_contains($f, "shop.php") ? "shop-" : "";
  foreach ($seeds as $s) { echo ($prefix && !str_starts_with($s, "shop-") ? $prefix : "") . $s, "\n"; }
}' | sort -u > "$STAGE/seeds.txt"
while read -r seed; do
  curl -sSL --max-time 30 -o "$STAGE/wpfasty-demo/images/$seed.jpg" "https://picsum.photos/seed/$seed/1000/750.jpg" || true
done < "$STAGE/seeds.txt"
# Images committed with the demos (market-*.jpg) win over the fetched seeds.
cp tools/demo/images/*.jpg "$STAGE/wpfasty-demo/images/"
rm -f "$OUT/demo.zip"
(cd "$STAGE" && zip -qr "$OUT/demo.zip" wpfasty-demo)
rm -rf "$STAGE"
# ModulY (public phpfasty/wpmodules): plugin + signed module zips. A local wpmodules build wins ($WPMODULES_DIST,
# default ../wpmodules/dist); otherwise the public release assets of $WPMODULES_RELEASE (default "edge", rebuilt by
# wpmodules CI on every push to main) are downloaded and checked against their SHA256SUMS.
MD="${WPMODULES_DIST:-$ROOT/../wpmodules/dist}"
MODULES="types cart orders downloads consent-log cookie-consent reviews"
if [ -d "$MD/modules" ]; then
  cp "$MD"/moduly-*.zip "$OUT/moduly.zip"
  for id in $MODULES; do cp "$MD"/modules/$id-*.zip "$OUT/moduly-$id.zip"; done
else
  REL="https://github.com/phpfasty/wpmodules/releases/download/${WPMODULES_RELEASE:-edge}"
  DL="$(mktemp -d)"
  curl -fsSL --retry 3 -o "$DL/SHA256SUMS" "$REL/SHA256SUMS"
  for f in moduly $MODULES; do curl -fsSL --retry 3 -o "$DL/$f.zip" "$REL/$f.zip"; done
  (cd "$DL" && grep -E " (moduly|$(echo $MODULES | tr ' ' '|'))\.zip$" SHA256SUMS | sha256sum -c -)
  cp "$DL/moduly.zip" "$OUT/moduly.zip"
  for id in $MODULES; do cp "$DL/$id.zip" "$OUT/moduly-$id.zip"; done
  rm -rf "$DL"
fi
ls -la "$OUT"/*.zip

# Inline-blueprint preview URLs (playground/urls.txt).
# <site>-builder: the same blueprint opening the builder's page list in wp-admin (the blueprint logs in).
{
  for t in press folio shop shop-twig shop-modules market; do
    printf '%s https://playground.wordpress.net/#%s\n' "$t" "$(php -r 'echo rawurlencode(json_encode(json_decode(file_get_contents($argv[1])), JSON_UNESCAPED_SLASHES));' "$OUT/$t.json")"
  done
  for t in shop shop-twig; do
    printf '%s-builder https://playground.wordpress.net/#%s\n' "$t" "$(php -r '$b = json_decode(file_get_contents($argv[1])); $b->landingPage = "/wp-admin/edit.php?post_type=page&page=wpfasty-builder"; echo rawurlencode(json_encode($b, JSON_UNESCAPED_SLASHES));' "$OUT/$t.json")"
  done
} > "$OUT/urls.txt"
