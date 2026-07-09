<?php

declare(strict_types=1);

/**
 * Latte — clean WP FastY theme (UI8Kit primitives).
 *
 * @package Latte
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Latte theme: run composer install in themes/latte (and bun run ui:primitives from the monorepo root).',
            'latte'
        );
        echo '</p></div>';
    });
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

WPFasty\Core\Application::getInstance();

add_action('init', static function (): void {
    load_theme_textdomain('latte', get_template_directory() . '/languages');
});
