<?php
/**
 * WP Fasty Theme functions and definitions
 *
 * @package WPFasty
 */

if (! defined('ABSPATH')) {
    exit;
}

// Error reporting for development
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// Composer autoload (theme + path repo ui8kit/latte-primitives)
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    throw new RuntimeException(
        'Missing vendor/autoload.php. Run: composer install in themes/wp-fasty-ym ' .
        '(and bun run ui:primitives from the monorepo root).'
    );
}
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap the application
if (class_exists('WPFasty\\Core\\Application')) {
    WPFasty\Core\Application::getInstance();
}

// Loading the text domain
add_action('init', function (): void {
    load_theme_textdomain('wp-fasty-ym', get_stylesheet_directory() . '/languages');
});

// Include helper functions
if (file_exists(__DIR__ . '/includes/wp-helpers.php')) {
    require_once __DIR__ . '/includes/wp-helpers.php';
}