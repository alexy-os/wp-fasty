<?php

/**
 * WP FastY — base theme (Latte engine, UI8Kit primitives).
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__.'/includes/api.php';
require_once __DIR__.'/includes/base-theme.php';
require_once __DIR__.'/includes/deprecated.php';

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'WP FastY theme: run composer install in themes/wp-fasty.',
            'wp-fasty'
        );
        echo '</p></div>';
    });

    return;
}

require_once __DIR__.'/vendor/autoload.php';

wpfasty_base_theme_hooks();
wpfasty_load_base_theme();

WPFasty\Core\Application::getInstance();

add_action('init', static function (): void {
    load_theme_textdomain('wp-fasty', get_template_directory().'/languages');

    if (is_child_theme()) {
        $domain = wp_get_theme()->get('TextDomain');
        if (is_string($domain) && '' !== $domain && 'wp-fasty' !== $domain) {
            load_child_theme_textdomain($domain, get_stylesheet_directory().'/languages');
        }
    }
});
