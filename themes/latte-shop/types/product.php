<?php
/**
 * Post-type module: product (purchasable). Registered by the parent WPFasty\Types\Registry.
 *
 * @package Latte_Shop
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

return [
    'name' => 'product',
    'text_domain' => 'latte-shop',
    'meta_prefix' => 'shop',
    'args' => [
        'labels' => [
            'name' => __('Products', 'latte-shop'),
            'singular_name' => __('Product', 'latte-shop'),
            'add_new_item' => __('Add product', 'latte-shop'),
            'edit_item' => __('Edit product', 'latte-shop'),
        ],
        'public' => true,
        'has_archive' => 'shop',
        'rewrite' => ['slug' => 'product', 'with_front' => false],
        'menu_icon' => 'dashicons-cart',
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'],
    ],
    'taxonomies' => [
        'product_cat' => [
            'labels' => ['name' => __('Product categories', 'latte-shop'), 'singular_name' => __('Product category', 'latte-shop')],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'shop/category', 'with_front' => false],
        ],
    ],
    'fields' => [
        'price' => ['type' => 'money', 'label' => __('Price', 'latte-shop')],
        'sale_price' => ['type' => 'money', 'label' => __('Sale price', 'latte-shop')],
        'sku' => ['type' => 'string', 'label' => __('SKU', 'latte-shop')],
        'stock' => ['type' => 'integer', 'label' => __('Stock (empty = unlimited)', 'latte-shop')],
    ],
    'metabox' => ['title' => __('Product data', 'latte-shop'), 'context' => 'side', 'priority' => 'high'],
    'supports' => [
        'purchasable' => ['price_field' => 'price', 'sale_field' => 'sale_price', 'stock_field' => 'stock'],
    ],
    'views' => ['single' => 'pages/product', 'archive' => 'pages/shop', 'card' => 'blocks/product-card'],
    'archive' => [
        'per_page' => 12,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'title' => __('Shop', 'latte-shop'),
        'prefix' => false,
    ],
    'columns' => [
        'price' => ['label' => __('Price', 'latte-shop'), 'value' => static fn (int $id): string => LatteShop\Money::format(LatteShop\Catalog::price($id))],
        'stock' => ['label' => __('Stock', 'latte-shop'), 'value' => static function (int $id): string {
            $stock = LatteShop\Catalog::stock($id);
            return $stock === null ? '∞' : (string) $stock;
        }],
    ],
    'context' => [LatteShop\Catalog::class, 'viewModel'],
];
