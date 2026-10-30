<?php

declare(strict_types=1);

/**
 * WP FastY Market — brand child: tokens, logo, footer. Shop templates come from Latte Shop ("Extends: latte-shop",
 * layered by Latte), digital features from ModulY modules through Latte Shop extension points.
 *
 * @package WPFasty_Market
 */

// Loaded at once (not on after_setup_theme): the base-theme layer can request strings earlier,
// which would cache a no-op domain.
load_textdomain('wpfasty-market', __DIR__ . '/languages/' . determine_locale() . '.mo');

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', static function (): void {
    register_nav_menus(['legal' => __('Legal documents (footer)', 'wpfasty-market')]);
});

// Footer data: seller details (option `wpfasty_company`, filled by the site owner or the demo) and labels.
add_filter('wpfasty_context', static function (array $context): array {
    $company = (array) get_option('wpfasty_company', []);
    $context['company'] = array_map(
        'strval',
        $company + ['name' => '', 'ogrn' => '', 'inn' => '', 'address' => '', 'email' => '']
    );
    $context['legal_menu'] = [];
    $locations = get_nav_menu_locations();
    $items = isset($locations['legal']) ? wp_get_nav_menu_items($locations['legal']) : false;
    foreach (is_array($items) ? $items : [] as $item) {
        $context['legal_menu'][] = ['title' => (string) $item->title, 'url' => (string) $item->url];
    }
    $context['labels'] = (array) ($context['labels'] ?? []) + [
        'footer_about' => __(
            'Ready-made WordPress sites, themes and plugins. Fast, accessible, no page builders.',
            'wpfasty-market'
        ),
        'footer_market' => __('Catalog', 'wpfasty-market'),
        'footer_docs' => __('Documents', 'wpfasty-market'),
        'footer_company' => __('Seller', 'wpfasty-market'),
        'ogrn' => __('OGRN', 'wpfasty-market'),
        'inn' => __('INN', 'wpfasty-market'),
        'rights' => __('All rights reserved.', 'wpfasty-market'),
    ];
    return $context;
}, 20);
