#!/bin/sh
set -eu

ROOT="/var/www/wpfasty"
LATTE="$ROOT/.workspaces/ui8kit-latte"
THEME="$ROOT/themes/wp-fasty-ym"

if [ ! -f "$LATTE/composer.json" ]; then
  echo "ERROR: $LATTE/composer.json missing."
  echo "Run on the host first:  bun run ui:primitives"
  exit 1
fi

echo "==> composer install: ui8kit-latte"
composer install --working-dir="$LATTE" --no-interaction --prefer-dist

echo "==> composer install: wp-fasty-ym"
composer install --working-dir="$THEME" --no-interaction --prefer-dist

echo "==> done"
ls -la "$THEME/vendor" | head -5
echo "UI8Kit primitives:"
ls "$LATTE/ui/button/Button.latte"
