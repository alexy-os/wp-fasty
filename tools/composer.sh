#!/usr/bin/env bash
# Run Composer from PATH, or php composer.phar at repo root / tools/.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if command -v composer >/dev/null 2>&1; then
  exec composer "$@"
fi
if [[ -f "$ROOT/composer.phar" ]]; then
  exec php "$ROOT/composer.phar" "$@"
fi
if [[ -f "$ROOT/tools/composer.phar" ]]; then
  exec php "$ROOT/tools/composer.phar" "$@"
fi

echo "ERROR: Composer not found. Install composer or place composer.phar at repo root." >&2
exit 1
