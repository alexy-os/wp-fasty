#!/usr/bin/env bash
# Install PHP deps for ui8kit-latte (+ themes/latte when present).
# Works without a global `composer` binary (WSL / Windows).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

run_composer() {
  local dir="$1"
  shift
  if command -v composer >/dev/null 2>&1; then
    composer install --working-dir="$dir" --no-interaction --prefer-dist "$@"
    return
  fi

  if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    echo "composer not on PATH — using Docker (dev:composer)"
    docker compose --profile tools run --rm composer
    return 2  # signal: docker script already installed everything
  fi

  # Last resort: composer.phar + php
  local phar="$ROOT/tools/composer.phar"
  if [[ ! -f "$phar" ]]; then
    if ! command -v php >/dev/null 2>&1; then
      echo "ERROR: need one of: composer on PATH, Docker Compose, or php + network."
      echo "  WSL:  bun run dev:composer"
      echo "  or:   docker compose --profile tools run --rm composer"
      exit 127
    fi
    echo "Downloading composer.phar → tools/composer.phar"
    php -r "copy('https://getcomposer.org/installer', 'tools/composer-setup.php');"
    php tools/composer-setup.php --install-dir=tools --filename=composer.phar --quiet
    rm -f tools/composer-setup.php
  fi
  php "$phar" install --working-dir="$dir" --no-interaction --prefer-dist "$@"
}

if [[ ! -f .workspaces/ui8kit-latte/composer.json ]]; then
  echo "ERROR: .workspaces/ui8kit-latte missing — run: bun run ui:primitives"
  exit 1
fi

echo "==> ui8kit-latte"
set +e
run_composer .workspaces/ui8kit-latte
status=$?
set -e
if [[ $status -eq 2 ]]; then
  # Docker path already installed latte theme + ym
  echo "==> done (via Docker)"
  exit 0
fi
if [[ $status -ne 0 ]]; then
  exit "$status"
fi

if [[ -f themes/latte/composer.json ]]; then
  echo "==> themes/latte"
  run_composer themes/latte
fi

echo "==> done"
