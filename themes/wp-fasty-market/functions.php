<?php

declare(strict_types=1);

/**
 * WP FastY Market — digital-goods marketplace child theme of Latte (ready-made WordPress sites, themes and plugins).
 *
 * @package WP_FastY_Market
 */

if (!defined('ABSPATH')) {
    exit;
}

foreach (['money', 'fixtures', 'catalog', 'cart', 'orders', 'handlers', 'front'] as $file) {
    require_once __DIR__ . "/inc/{$file}.php";
}

WpfastyMarket\Orders::register();
WpfastyMarket\Handlers::register();
WpfastyMarket\Front::register();

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('wp-fasty-market', get_stylesheet_directory() . '/languages');
    add_theme_support('wpfasty-full-width');
});

// Cookie consent banner script (self-hosted, no external requests).
add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_script(
        'wp-fasty-market-cookie-consent',
        get_stylesheet_directory_uri() . '/assets/js/cookie-consent.js',
        [],
        (string) wp_get_theme()->get('Version'),
        ['in_footer' => true, 'strategy' => 'defer']
    );
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
            'wp-fasty-market'
        )
    );
});
