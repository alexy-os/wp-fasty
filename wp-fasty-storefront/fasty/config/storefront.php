<?php
/**
 * Storefront Configuration
 * Configuration for Storefront theme overrides
 */

return [
    // Storefront page layout
    'page_layout' => 'right', // 'left', 'right', or 'full-width'
    
    // Footer widget columns
    'footer_widget_columns' => 4,
    
    // Footer credit text
    'credit_text' => __('Built with <a href="https://wordpress.org/">WordPress</a> and the FastyChild Framework', 'wp-fasty-storefront'),
    
    // Whether to override the Storefront header
    'override_header' => true,
    
    // Storefront hooks to override
    'hooks' => [
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
        'storefront_footer' => [
            'remove' => [
                'storefront_credit' => 20,
            ],
            'add' => [
                'customFooter' => 20,
            ],
        ],
    ],
    
    // Product display
    'products_per_row' => 4,
    'products_per_page' => 12,
    
    // Homepage components
    'homepage' => [
        'show_recent_products' => true,
        'show_featured_products' => true,
        'show_popular_products' => true,
        'show_on_sale_products' => true,
        'show_best_selling_products' => true,
    ],
]; 