<?php

/**
 * Plugin Name: WP FastY QA — offline
 * Description: QA/CI only (never shipped): no wordpress.org update / API calls, so a wordpress.org outage cannot
 * log "An unexpected error occurred" warnings into the PHP-notice gate.
 *
 * @package Latte
 */

if (!defined('ABSPATH')) {
    exit;
}

$wpfastyQaChecked = static fn (): object => (object) [
    'last_checked' => time(),
    'checked' => [],
    'response' => [],
    'translations' => [],
    'updates' => [],
    'version_checked' => get_bloginfo('version'),
];
add_filter('pre_site_transient_update_themes', $wpfastyQaChecked);
add_filter('pre_site_transient_update_plugins', $wpfastyQaChecked);
add_filter('pre_site_transient_update_core', $wpfastyQaChecked);
$wpfastyQaApi = static fn (mixed $res): mixed => $res ?: new WP_Error('wpfasty_qa_offline', 'Offline QA');
add_filter('themes_api', $wpfastyQaApi, 99);
add_filter('plugins_api', $wpfastyQaApi, 99);
