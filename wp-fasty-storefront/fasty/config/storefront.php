<?php
/**
 * Storefront Configuration
 * Configuration for Storefront theme overrides
 */

return [
    // Main settings
    'page_layout' => 'right', // 'left', 'right', or 'full-width'
    'footer_widget_columns' => 4,
    'credit_text' => __('Built with <a href="https://wordpress.org/">WordPress</a> and the FastyChild Framework', FASTY_TEXTDOMAIN),
    
    // Header management
    'override_header' => true, // This flag completely replaces the header with a custom one
    
    // Hooks to override - used in processAllHooksFromConfig
    'hooks' => [
        // Site header hooks - used only if override_header = false
        'storefront_header' => [
            'remove' => [
                'storefront_header_container' => 0,
                'storefront_skip_links' => 5,
                'storefront_site_branding' => 20,
                'storefront_secondary_navigation' => 30,
                'storefront_primary_navigation_wrapper' => 42,
                'storefront_primary_navigation' => 50,
                'storefront_header_cart' => 60,
                'storefront_primary_navigation_wrapper_close' => 68,
            ],
            'add' => [
                'customHeader' => 10,
            ],
        ],
        // Site footer hooks
        'storefront_footer' => [
            'remove' => [
                'storefront_credit' => 20,
            ],
            'add' => [
                'customFooter' => 20,
            ],
        ],
    ],
    
    // Storefront WooCommerce products settings
    'products' => [
        'per_row' => 4,
        'per_page' => 12,
    ],
    
    // Main page components settings
    'homepage' => [
        'show_recent_products' => true,
        'show_featured_products' => true,
        'show_popular_products' => true,
        'show_on_sale_products' => true,
        'show_best_selling_products' => true,
    ],
]; 