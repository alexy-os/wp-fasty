<?php
/**
 * Storefront Configuration
 * Configuration for Storefront theme overrides
 */

return [
    // Основные настройки
    'page_layout' => 'right', // 'left', 'right', или 'full-width'
    'footer_widget_columns' => 4,
    'credit_text' => __('Built with <a href="https://wordpress.org/">WordPress</a> and the FastyChild Framework', 'wp-fasty-storefront'),
    
    // Управление шапкой сайта
    'override_header' => true, // Этот флаг полностью заменяет шапку на кастомную
    
    // Hooks to override - используются в processAllHooksFromConfig
    'hooks' => [
        // Хуки шапки сайта - используются, только если override_header = false
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
        // Хуки подвала сайта
        'storefront_footer' => [
            'remove' => [
                'storefront_credit' => 20,
            ],
            'add' => [
                'customFooter' => 20,
            ],
        ],
    ],
    
    // Настройки для WooCommerce продуктов в Storefront
    'products' => [
        'per_row' => 4,
        'per_page' => 12,
    ],
    
    // Настройки компонентов главной страницы
    'homepage' => [
        'show_recent_products' => true,
        'show_featured_products' => true,
        'show_popular_products' => true,
        'show_on_sale_products' => true,
        'show_best_selling_products' => true,
    ],
]; 