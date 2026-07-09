#!/bin/sh
set -eu

ROOT="/var/www/wpfasty"
PRIMITIVES="$ROOT/.workspaces/ui8kit-latte"
THEME="$ROOT/themes/latte"

if [ ! -f "$PRIMITIVES/composer.json" ]; then
  echo "ERROR: $PRIMITIVES/composer.json missing."
  echo "Run on the host first:  bun run ui:primitives"
  exit 1
fi

echo "==> composer install: ui8kit-latte"
composer install --working-dir="$PRIMITIVES" --no-interaction --prefer-dist

if [ ! -f "$THEME/composer.json" ]; then
  echo "ERROR: $THEME/composer.json missing."
  exit 1
fi

echo "==> composer install: latte"
composer install --working-dir="$THEME" --no-interaction --prefer-dist

echo "==> done"
ls "$PRIMITIVES/ui/button/Button.latte"
ls -la "$THEME/vendor" | head -5
