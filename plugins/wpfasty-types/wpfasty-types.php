<?php
/**
 * Plugin Name:       WP FastY Types
 * Description:       Data layer for WP FastY post-type modules: post types, taxonomies, typed meta, metaboxes.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Author:            godudy
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpfasty-types
 *
 * @package WPFasty_Types
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/src/Plugin.php';

add_action('wpfasty_types_loaded', [WPFastyTypes\Plugin::class, 'attach']);

add_action('init', static function (): void {
    load_plugin_textdomain('wpfasty-types', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

register_activation_hook(__FILE__, static function (): void {
    add_option('wpfasty_types_flush', 1);
});
add_action('init', static function (): void {
    if (get_option('wpfasty_types_flush')) {
        delete_option('wpfasty_types_flush');
        flush_rewrite_rules();
    }
}, 99);
