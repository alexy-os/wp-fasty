<?php

/**
 * Catalog: product CPT, categories, price/sale/SKU/stock meta.
 *
 * @package Latte_Shop
 */

declare(strict_types=1);

namespace LatteShop;

if (!defined('ABSPATH')) {
    exit;
}

final class Catalog
{
    public const META = [
        'price' => 'Price',
        'sale_price' => 'Sale price',
        'sku' => 'SKU',
        'stock' => 'Stock (empty = unlimited)',
    ];

    public static function register(): void
    {
        add_action('init', [self::class, 'types']);
        add_action('add_meta_boxes_product', [self::class, 'metaBox']);
        add_action('save_post_product', [self::class, 'save']);
        add_filter('manage_product_posts_columns', static fn (array $c): array
            => array_slice($c, 0, 2, true) + ['price' => __('Price', 'latte-shop'), 'stock' => __('Stock', 'latte-shop')] + $c);
        add_action('manage_product_posts_custom_column', static function (string $col, int $id): void {
            if ($col === 'price') {
                echo esc_html(Money::format(self::price($id)));
            } elseif ($col === 'stock') {
                $s = self::stock($id);
                echo esc_html($s === null ? '∞' : (string) $s);
            }
        }, 10, 2);
    }

    public static function types(): void
    {
        register_post_type('product', [
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
        ]);
        register_taxonomy('product_cat', 'product', [
            'labels' => ['name' => __('Product categories', 'latte-shop'), 'singular_name' => __('Product category', 'latte-shop')],
            'public' => true,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'shop/category', 'with_front' => false],
        ]);
        foreach (array_keys(self::META) as $key) {
            register_post_meta('product', "_shop_{$key}", [
                'type' => 'string',
                'single' => true,
                'sanitize_callback' => [self::class, 'sanitize' . ($key === 'sku' ? 'Text' : 'Number')],
                'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
            ]);
        }
    }

    public static function sanitizeText(mixed $v): string
    {
        return sanitize_text_field((string) $v);
    }

    public static function sanitizeNumber(mixed $v): string
    {
        $v = trim(str_replace(',', '.', (string) $v));
        return $v === '' || !is_numeric($v) ? '' : (string) max(0, round((float) $v, 2));
    }

    public static function metaBox(): void
    {
        add_meta_box('latte-shop-product', __('Product data', 'latte-shop'), static function (\WP_Post $post): void {
            wp_nonce_field('latte_shop_product', 'latte_shop_product_nonce');
            foreach (self::META as $key => $label) {
                printf(
                    '<p><label for="shop-%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="%3$s" step="0.01" min="0" id="shop-%1$s" name="shop[%1$s]" value="%4$s"></p>',
                    esc_attr($key),
                    esc_html($label),
                    $key === 'sku' ? 'text' : 'number',
                    esc_attr((string) get_post_meta($post->ID, "_shop_{$key}", true))
                );
            }
        }, 'product', 'side', 'high');
    }

    public static function save(int $postId): void
    {
        $nonce = isset($_POST['latte_shop_product_nonce']) ? sanitize_text_field(wp_unslash($_POST['latte_shop_product_nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'latte_shop_product') || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !current_user_can('edit_post', $postId)) {
            return;
        }
        $input = isset($_POST['shop']) && is_array($_POST['shop']) ? wp_unslash($_POST['shop']) : [];
        foreach (array_keys(self::META) as $key) {
            $raw = $input[$key] ?? '';
            update_post_meta($postId, "_shop_{$key}", $key === 'sku' ? self::sanitizeText($raw) : self::sanitizeNumber($raw));
        }
    }

    public static function isPurchasable(int $id): bool
    {
        $p = get_post($id);
        return $p instanceof \WP_Post && $p->post_type === 'product' && $p->post_status === 'publish' && self::price($id) > 0
            && (self::stock($id) === null || self::stock($id) > 0);
    }

    /** Effective unit price in cents (sale wins when lower). */
    public static function price(int $id): int
    {
        $regular = Money::toCents((string) get_post_meta($id, '_shop_price', true));
        $sale = Money::toCents((string) get_post_meta($id, '_shop_sale_price', true));
        return $sale > 0 && $sale < $regular ? $sale : $regular;
    }

    public static function regularPrice(int $id): int
    {
        return Money::toCents((string) get_post_meta($id, '_shop_price', true));
    }

    public static function stock(int $id): ?int
    {
        $raw = (string) get_post_meta($id, '_shop_stock', true);
        return $raw === '' ? null : max(0, (int) $raw);
    }
}
