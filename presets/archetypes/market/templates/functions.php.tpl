<?php

declare(strict_types=1);

/**
 * {{theme.name}} — {{summary}}.
 *
 * @package {{packageName}}
 */

if (!defined('ABSPATH')) {
    exit;
}

// Presentation only: prices, license tiers, cart, checkout, orders, downloads, consents and reviews come from the
// ModulY modules (wpfasty_shop_* / wpfasty_cookie_consent filters, see inc/front.php). Without them the theme shows
// a read-only catalog and "temporarily unavailable" on the cart and checkout pages.
foreach (['fixtures', 'front'] as $file) {
    require_once __DIR__ . "/inc/{$file}.php";
}

WpfastyMarket\Front::register();

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('{{theme.textDomain}}', get_stylesheet_directory() . '/languages');
{{#if features.fullWidth}}
    add_theme_support('wpfasty-full-width');
{{/if}}
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
            '{{theme.textDomain}}'
        )
    );
});
