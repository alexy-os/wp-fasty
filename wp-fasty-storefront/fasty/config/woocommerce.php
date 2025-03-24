<?php
/**
 * WooCommerce Configuration
 * Configuration for WooCommerce customizations
 */

return [
    // Product display
    'product_title_classes' => 'fasty-product-title',
    
    // Sale flash display
    'sale_flash' => __('SALE {percentage}% OFF', 'wp-fasty-storefront'),
    
    // Related products
    'related_products' => [
        'posts_per_page' => 4,
        'columns' => 4,
    ],
    
    // Checkout fields customization
    'checkout_fields' => [
        'billing' => [
            'billing_company' => [
                'priority' => 30,
                'placeholder' => __('Company Name (Optional)', 'wp-fasty-storefront'),
            ],
            'billing_phone' => [
                'priority' => 25,
                'required' => true,
                'placeholder' => __('Phone Number', 'wp-fasty-storefront'),
            ],
            'billing_email' => [
                'priority' => 20,
                'placeholder' => __('Email Address', 'wp-fasty-storefront'),
            ],
        ],
        'shipping' => [
            'shipping_company' => [
                'remove' => true,
            ],
        ],
    ],
    
    // Product tabs
    'product_tabs' => [
        'remove' => [
            'additional_information',
        ],
        'add' => [
            'custom_tab' => [
                'title' => __('Custom Information', 'wp-fasty-storefront'),
                'priority' => 30,
                'callback' => function() {
                    echo '<h2>' . __('Custom Product Information', 'wp-fasty-storefront') . '</h2>';
                    echo '<p>' . __('This is custom product information added by the FastyChild Framework.', 'wp-fasty-storefront') . '</p>';
                },
            ],
        ],
        'reorder' => [
            'description' => 10,
            'reviews' => 20,
            'custom_tab' => 30,
        ],
    ],
    
    // WooCommerce hooks to override
    'hooks' => [
        'woocommerce_before_shop_loop' => [
            'remove' => [
                'woocommerce_result_count' => 20,
                'woocommerce_catalog_ordering' => 30,
            ],
            'add' => [
                'customShopHeader' => 15,
            ],
        ],
        'woocommerce_before_single_product_summary' => [
            'add' => [
                'customProductBadges' => 5,
            ],
        ],
    ],
]; 