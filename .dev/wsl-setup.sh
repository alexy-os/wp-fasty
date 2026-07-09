#!/usr/bin/env bash
# Optional helper — run from monorepo root inside WSL:
#   bash .dev/wsl-setup.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "==> repo: $ROOT"

if ! command -v docker >/dev/null; then
  echo "ERROR: docker not found. Enable Docker Desktop → WSL integration for this distro."
  exit 1
fi

if [ ! -f .workspaces/ui8kit-latte/manifest.json ]; then
  if command -v bun >/dev/null; then
    echo "==> bun run ui:primitives"
    bun run ui:primitives
  else
    echo "ERROR: .workspaces/ui8kit-latte missing and bun not installed."
    echo "Install bun (https://bun.sh) or copy a generated ui8kit-latte tree."
    exit 1
  fi
fi

echo "==> docker compose up -d"
docker compose up -d

echo "==> composer install (theme + latte)"
docker compose --profile tools run --rm composer

echo ""
echo "Open http://localhost:8080 and finish the WordPress installer."
echo "Then activate theme WP FastY YM:"
echo "  docker compose --profile tools run --rm wpcli theme activate wp-fasty-ym"
echo "Done."
