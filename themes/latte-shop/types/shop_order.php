<?php
/**
 * Post-type module: shop_order (private). Admin screens: LatteShop\Orders.
 *
 * @package Latte_Shop
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return [
    'name' => 'shop_order',
    'text_domain' => 'latte-shop',
    'args' => [
        'labels' => [
            'name' => __('Orders', 'latte-shop'),
            'singular_name' => __('Order', 'latte-shop'),
            'edit_item' => __('Order', 'latte-shop'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'edit.php?post_type=product',
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => ['create_posts' => 'do_not_allow'],
        'map_meta_cap' => true,
    ],
    'metabox' => false,
    'supports' => ['orderable'],
];
