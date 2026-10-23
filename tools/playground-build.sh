#!/usr/bin/env bash
# Build WordPress Playground preview artifacts into playground/:
#   latte.zip (parent, vendored), latte-press.zip, latte-folio.zip, latte-shop.zip, wpfasty-types.zip,
#   wpfasty-builder.zip, demo.zip (seeders, images, builder landings)
# Blueprints (playground/*.json) install them straight from the `improved` branch.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/playground"
cd "$ROOT"

bun run theme:pack -- latte >/dev/null 2>&1
cp dist/latte.zip "$OUT/latte.zip"

for t in latte-press latte-folio latte-shop; do
  rm -f "$OUT/$t.zip"
  (cd themes && zip -qr "$OUT/$t.zip" "$t" -x "*/~cache/*" "*/node_modules/*")
done

# Companion plugin (post types for folio/shop — wordpress.org plugin territory).
rm -f "$OUT/wpfasty-types.zip"
(cd plugins && zip -qr "$OUT/wpfasty-types.zip" wpfasty-types)

# Page builder (wp-admin only; the editor sources and node_modules stay out, the built assets go in).
rm -f "$OUT/wpfasty-builder.zip"
(cd plugins && zip -qr "$OUT/wpfasty-builder.zip" wpfasty-builder -x "wpfasty-builder/editor/*" "wpfasty-builder/primitives/*/ui8kit.lock.json" "wpfasty-builder/primitives/*/ui8kit.config.json")

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
rm -f "$OUT/demo.zip"
(cd "$STAGE" && zip -qr "$OUT/demo.zip" wpfasty-demo)
rm -rf "$STAGE"
ls -la "$OUT"/*.zip

# Inline-blueprint preview URLs (playground/urls.txt).
for t in press folio shop; do
  printf '%s https://playground.wordpress.net/#%s\n' "$t" "$(php -r 'echo rawurlencode(json_encode(json_decode(file_get_contents($argv[1])), JSON_UNESCAPED_SLASHES));' "$OUT/$t.json")"
done > "$OUT/urls.txt"
