#!/bin/sh
set -eu

ROOT="/var/www/wpfasty"
LATTE="$ROOT/.workspaces/ui8kit-latte"
THEME_YM="$ROOT/themes/wp-fasty-ym"
THEME_LATTE="$ROOT/themes/latte"

if [ ! -f "$LATTE/composer.json" ]; then
  echo "ERROR: $LATTE/composer.json missing."
  echo "Run on the host first:  bun run ui:primitives"
  exit 1
fi

echo "==> composer install: ui8kit-latte"
composer install --working-dir="$LATTE" --no-interaction --prefer-dist

if [ -f "$THEME_LATTE/composer.json" ]; then
  echo "==> composer install: latte"
  composer install --working-dir="$THEME_LATTE" --no-interaction --prefer-dist
fi

if [ -f "$THEME_YM/composer.json" ]; then
  echo "==> composer install: wp-fasty-ym"
  composer install --working-dir="$THEME_YM" --no-interaction --prefer-dist
fi

echo "==> done"
ls "$LATTE/ui/button/Button.latte"
if [ -d "$THEME_LATTE/vendor" ]; then
  ls -la "$THEME_LATTE/vendor" | head -5
fi
