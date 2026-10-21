#!/usr/bin/env bash
# Packs a local builder source for remote hosting: <out>/catalog.json + <out>/latte.zip and, when the source has a
# Twig package, <out>/twig.zip (blocks/, ui/; no PHP).
#   bash tools/builder/pack-source.sh [source dir] [out dir]
set -euo pipefail
src="${1:-plugins/wpfasty-builder/sources/ui8kit}"
out="${2:-dist/builder-source}"
mkdir -p "$out"
out="$(cd "$out" && pwd)"
cp "$src/catalog.json" "$out/catalog.json"
rm -f "$out/latte.zip"
(cd "$src/latte" && zip -qr "$out/latte.zip" blocks ui -i '*.latte' '*.json')
rm -f "$out/twig.zip"
[ ! -d "$src/twig/blocks" ] || (cd "$src/twig" && zip -qr "$out/twig.zip" blocks ui -i '*.twig' '*.json')
echo "$out: catalog.json $(du -h "$out/catalog.json" | cut -f1), latte.zip $(du -h "$out/latte.zip" | cut -f1)$([ -f "$out/twig.zip" ] && echo ", twig.zip $(du -h "$out/twig.zip" | cut -f1)")"
