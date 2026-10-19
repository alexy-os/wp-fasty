<?php

declare(strict_types=1);

/**
 * Latte Shop — WooCommerce-free shop child theme of Latte.
 *
 * @package Latte_Shop
 */

if (!defined('ABSPATH')) {
    exit;
}

foreach (['money', 'catalog', 'cart', 'orders', 'handlers', 'front'] as $file) {
    require_once __DIR__ . "/inc/{$file}.php";
}

LatteShop\Orders::register();
LatteShop\Handlers::register();
LatteShop\Front::register();

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('latte-shop', get_stylesheet_directory() . '/languages');
    add_theme_support('wpfasty-full-width');
});

// Post types are plugin territory (wordpress.org): they are registered by the companion plugin.
add_action('admin_notices', static function (): void {
    if (class_exists('WPFastyTypes\\Plugin') || !current_user_can('activate_plugins')) {
        return;
    }
    printf(
        '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
        esc_html__(
            'This theme needs the "WP FastY Types" plugin (plugins/wpfasty-types) to register its content types.',
            'latte-shop'
        )
    );
});
