#!/usr/bin/env bash
# Codex conformance of the demo sites on a fresh install each: reset the DB, install, seed the packaged demo with a
# pinned clock (tools/codex/seed-pinned.php), then tools/codex/conform.ts (schemas + validateCollectionV04 of
# @ui8kit/contract, canonical bytes, byte-for-byte golden), the Go cross-check (tools/codex/gocheck) and the Codex list
# N+1 guard.
#   tools/codex/conform-sites.sh <wp dir> <base url> <demo prefix> [--write] [site…]
# <demo prefix>latte-<site>/wpfasty-demo/<site>.php = the unpacked demo.zip of theme:package (CI: /tmp/demo-). CODEX_CONTRACT = a
# built ui8kit/contract checkout (see conform.ts). Golden: tests/fixtures/codex-draft/golden/latte-<site>/ (draft).
set -euo pipefail
WP_DIR=$1 BASE=$2 DEMO=$3; shift 3
WRITE=""; [ "${1:-}" = "--write" ] && { WRITE="--write"; shift; }
SITES=${*:-press folio shop}
ROOT=$(cd "$(dirname "$0")/../.." && pwd)
# --url: WP-CLI sets HTTP_HOST from it (no wp_guess_url warning while the tables are gone)
wp() { command wp --path="$WP_DIR" --url="$BASE" "$@"; }
fail=0
for site in $SITES; do
  # fresh DB: drop the install's tables (no mysql client needed)
  if wp core is-installed 2>/dev/null; then
    wp eval 'global $wpdb; foreach ($wpdb->get_col("SHOW TABLES LIKE \"{$wpdb->prefix}%\"") as $t) { $wpdb->query("DROP TABLE `$t`"); }'
  fi
  rm -rf "$WP_DIR/wp-content/uploads"
  wp core install --url="$BASE" --title=CI --admin_user=admin --admin_password=admin --admin_email=ci@example.com \
    --skip-email --quiet
  # ids independent of what core install created (version, MySQL vs MariaDB): everything after it starts at 100,
  # including pages the theme creates on activation
  wp eval 'global $wpdb; foreach ([$wpdb->posts, $wpdb->terms, $wpdb->term_taxonomy] as $t) { $wpdb->query("ALTER TABLE $t AUTO_INCREMENT = 100"); }'
  wp plugin activate wpfasty-types wpfasty-builder --quiet
  wp theme activate "latte-$site" --quiet
  wp rewrite structure '/%postname%/' --hard --quiet
  wp eval-file "$ROOT/tools/codex/seed-pinned.php" "${DEMO}latte-$site/wpfasty-demo/$site.php"
  if ! (cd "$ROOT" && bun tools/codex/conform.ts "$BASE" "latte-$site" \
      --golden "tests/fixtures/codex-draft/golden/latte-$site" $WRITE); then
    fail=1
  fi
  # the same bytes through the Go reference (fastygo/codex v0.4.2): canonical fixed points + CollectionV04
  if command -v go > /dev/null; then
    (cd "$ROOT/tools/codex/gocheck" && go run . "$ROOT/tests/fixtures/codex-draft/golden/latte-$site" \
      "$ROOT/themes/latte-$site/types/codex.manifest.json") || fail=1
  else
    echo "go not found: Go cross-check skipped"
  fi
  kind=$(case $site in folio) echo project;; shop) echo product;; *) echo post;; esac)
  wp eval-file "$ROOT/tools/perf/codex-queries.php" "$kind" 2 4 || fail=1
done
exit $fail
