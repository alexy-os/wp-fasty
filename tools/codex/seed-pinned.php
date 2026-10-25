<?php
/**
 * Demo content with a pinned clock, for Codex golden files: every post / attachment date, every term stamp and the
 * upload path are fixed, so a fresh install seeded with the same demo serves the same Codex bytes.
 *   wp eval-file tools/codex/seed-pinned.php <demo seeder .php> [Y-m-d H:i:s UTC]
 * Test tooling only (CI conformance); writes dates straight to the posts table after seeding.
 *
 * @package WPFasty
 */

if (!defined('ABSPATH')) {
    exit;
}

$wpfasty_pin_seeder = (string) ($args[0] ?? '');
$wpfasty_pin_at = (string) ($args[1] ?? '2026-01-01 00:00:00');
if (!is_file($wpfasty_pin_seeder)) {
    WP_CLI::error("seeder not found: $wpfasty_pin_seeder");
}
$wpfasty_pin_iso = str_replace(' ', 'T', $wpfasty_pin_at) . 'Z';
update_option('uploads_use_yearmonth_folders', 0);
global $wpdb;
add_filter('wpfasty_codex_now', static fn (): string => $wpfasty_pin_iso);
require $wpfasty_pin_seeder;

// insertion order stays the date order: ID minutes after the pinned instant
// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- test fixture normalization.
$wpdb->query($wpdb->prepare(
    "UPDATE {$wpdb->posts} SET post_date = DATE_ADD(%s, INTERVAL ID MINUTE), post_date_gmt = DATE_ADD(%s, INTERVAL ID MINUTE),"
    . ' post_modified = DATE_ADD(%s, INTERVAL ID MINUTE), post_modified_gmt = DATE_ADD(%s, INTERVAL ID MINUTE)',
    $wpfasty_pin_at,
    $wpfasty_pin_at,
    $wpfasty_pin_at,
    $wpfasty_pin_at,
));
wp_cache_flush();
WP_CLI::success("dates pinned at $wpfasty_pin_iso");
