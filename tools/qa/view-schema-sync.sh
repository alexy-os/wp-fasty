#!/usr/bin/env bash
# schema/theme-context.schema.json is a committed byte copy of the view profile schema of @ui8kit/contract
# (schemas/view.schema.json, $id https://schema.buildy.tw/view.schema.json), kept so the raw GitHub URL keeps working.
# The source is the npm package pinned exactly in package.json devDependencies (recorded in schema/VIEW_SCHEMA_SOURCE).
# Never edit the copy: bump the contract version (bun add -d --exact @ui8kit/contract@X), then run with --update.
set -euo pipefail
cd "$(dirname "$0")/../.."
read -r _src pkg version path < schema/VIEW_SCHEMA_SOURCE
src="node_modules/${pkg}/${path}"
[ -f "$src" ] || { echo "view-schema-sync: $src missing — run bun install" >&2; exit 1; }
installed="$(node -p "require('./node_modules/${pkg}/package.json').version" 2>/dev/null || bun -e "console.log(require('./node_modules/${pkg}/package.json').version)")"
pinned="$(bun -e "console.log(require('./package.json').devDependencies['${pkg}'])")"
[ "$installed" = "$version" ] && [ "$pinned" = "$version" ] || { echo "view-schema-sync: VIEW_SCHEMA_SOURCE says $version, package.json pins $pinned, node_modules has $installed" >&2; exit 1; }
if [ "${1:-}" = "--update" ]; then cp "$src" schema/theme-context.schema.json; echo "view-schema-sync: updated from $pkg@$version"; exit 0; fi
if cmp -s "$src" schema/theme-context.schema.json; then echo "view-schema-sync: OK ($pkg@$version)"; else echo "view-schema-sync: schema/theme-context.schema.json drifted from $src" >&2; exit 1; fi
