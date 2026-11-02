#!/usr/bin/env bash
# Fails when the WordPress core under test is older than the security minimum.
# CVE-2026-119977 (core RCE, 4.7–7.1.3, fixed in 7.1.4): https://www.tenable.com/cve/CVE-2026-119977
# Usage: tools/qa/wp-min-version.sh <wordpress dir> [minimum, default 7.1.4]
set -euo pipefail
dir="${1:?wordpress dir}"
min="${2:-7.1.4}"
ver=$(sed -n "s/^\$wp_version = '\([^']*\)';/\1/p" "$dir/wp-includes/version.php")
if [ -z "$ver" ] || [ "$(printf '%s\n%s\n' "$min" "$ver" | sort -V | head -1)" != "$min" ]; then
  echo "::error::WordPress ${ver:-unknown} < ${min} (CVE-2026-119977, https://www.tenable.com/cve/CVE-2026-119977)"
  exit 1
fi
echo "WordPress $ver >= $min"
