<?php

/**
 * Front-end: view routing, shop pages, product view-model, context.
 *
 * @package Latte_Shop
 */

declare(strict_types=1);

namespace LatteShop;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display params (notices, order key checked with hash_equals).

final class Front
{
    public const PAGES = ['cart' => 'Cart', 'checkout' => 'Checkout'];

    public static function register(): void
    {
        add_filter('wpfasty_resolve_view', [self::class, 'view']);
        add_filter('wpfasty_post_data', [self::class, 'productData'], 10, 2);
        add_filter('wpfasty_context', [self::class, 'context']);
        add_action('pre_get_posts', static function (\WP_Query $q): void {
            if (!is_admin() && $q->is_main_query() && ($q->is_post_type_archive('product') || $q->is_tax('product_cat'))) {
                $q->set('posts_per_page', 12);
                $q->set('orderby', ['menu_order' => 'ASC', 'date' => 'DESC']);
            }
        });
        add_action('after_switch_theme', [self::class, 'ensurePages']);
        add_filter('get_the_archive_title_prefix', '__return_empty_string');
        add_filter('post_type_archive_title', static fn (string $t, string $type): string
            => $type === 'product' ? __('Shop', 'latte-shop') : $t, 10, 2);
        // Cart/checkout must never be cached by proxies.
        add_action('template_redirect', static function (): void {
            if (is_page(array_keys(self::PAGES))) {
                nocache_headers();
            }
        });
    }

    public static function ensurePages(): void
    {
        foreach (self::PAGES as $slug => $title) {
            if (!get_page_by_path($slug)) {
                wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title]);
            }
        }
        flush_rewrite_rules();
    }

    public static function pageUrl(string $slug): string
    {
        $page = get_page_by_path($slug);
        return $page ? (string) get_permalink($page) : home_url('/' . $slug . '/');
    }

    public static function shopUrl(): string
    {
        return (string) get_post_type_archive_link('product');
    }

    public static function view(string $view): string
    {
        return match (true) {
            is_post_type_archive('product') || is_tax('product_cat') => 'pages/shop',
            is_singular('product') => 'pages/product',
            is_page('cart') => 'pages/cart',
            is_page('checkout') => 'pages/checkout',
            default => $view,
        };
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function productData(array $data, \WP_Post $post): array
    {
        if ($post->post_type !== 'product') {
            return $data;
        }
        $price = Catalog::price($post->ID);
        $regular = Catalog::regularPrice($post->ID);
        $stock = Catalog::stock($post->ID);
        $data['product'] = [
            'price' => Money::format($price),
            'regular' => $regular > $price ? Money::format($regular) : '',
            'on_sale' => $regular > $price,
            'sku' => (string) get_post_meta($post->ID, '_shop_sku', true),
            'stock' => $stock,
            'in_stock' => $stock === null || $stock > 0,
            'purchasable' => Catalog::isPurchasable($post->ID),
        ];
        $data['product_cats'] = [];
        foreach (get_the_terms($post, 'product_cat') ?: [] as $t) {
            $data['product_cats'][] = ['name' => $t->name, 'slug' => $t->slug, 'url' => (string) get_term_link($t)];
        }
        return $data;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public static function context(array $context): array
    {
        $context['widgets']['sidebar'] = '';
        $summary = Cart::summary();

        $context['shop'] = [
            'url' => self::shopUrl(),
            'cart_url' => self::pageUrl('cart'),
            'checkout_url' => self::pageUrl('checkout'),
            'action' => admin_url('admin-post.php'),
            'cart_nonce' => wp_create_nonce('latte_shop_cart'),
            'checkout_nonce' => wp_create_nonce('latte_shop_checkout'),
            'count' => $summary['count'],
            'notice' => self::notice(),
            'categories' => [],
            'current_cat' => is_tax('product_cat') ? (string) get_queried_object()->slug : '',
            'free_shipping' => Money::format((int) apply_filters('latte_shop_free_shipping_cents', 10000)),
        ];
        foreach (get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]) ?: [] as $t) {
            if ($t instanceof \WP_Term) {
                $context['shop']['categories'][] = ['name' => $t->name, 'slug' => $t->slug, 'url' => (string) get_term_link($t)];
            }
        }

        if (is_page(['cart', 'checkout'])) {
            $context['cart'] = $summary + [
                'subtotal_display' => Money::format($summary['subtotal']),
                'shipping_display' => $summary['shipping'] === 0 ? __('Free', 'latte-shop') : Money::format($summary['shipping']),
                'total_display' => Money::format($summary['total']),
            ];
        }

        if (is_page('checkout')) {
            $orderId = isset($_GET['order']) ? absint(wp_unslash($_GET['order'])) : 0;
            $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
            $context['order'] = $orderId > 0 ? Orders::find($orderId, $key) : null;

            $token = isset($_GET['co']) ? sanitize_key(wp_unslash($_GET['co'])) : '';
            $old = $token !== '' ? get_transient('latte_shop_co_' . $token) : false;
            $context['checkout'] = [
                'errors' => isset($_GET['co_errors'])
                    ? array_values(array_intersect(
                        explode(',', sanitize_text_field(wp_unslash($_GET['co_errors']))),
                        ['name', 'email', 'address', 'city', 'postcode', 'country', 'payment', 'server']
                    ))
                    : [],
                'values' => is_array($old) ? $old : ['payment' => 'cod', 'country' => ''],
                'payments' => [
                    ['id' => 'cod', 'label' => __('Cash on delivery', 'latte-shop'), 'icon' => 'truck'],
                    ['id' => 'bank', 'label' => __('Bank transfer', 'latte-shop'), 'icon' => 'credit-card'],
                ],
            ];
        }

        if (is_front_page() && function_exists('wpfasty_posts')) {
            $context['products'] = wpfasty_posts(get_posts(['post_type' => 'product', 'numberposts' => 8, 'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC']]));
        }

        $context['labels'] += [
            'add_to_cart' => __('Add to cart', 'latte-shop'),
            'buy_now' => __('Buy now', 'latte-shop'),
            'cart' => __('Cart', 'latte-shop'),
            'checkout' => __('Checkout', 'latte-shop'),
            'subtotal' => __('Subtotal', 'latte-shop'),
            'shipping' => __('Shipping', 'latte-shop'),
            'total' => __('Total', 'latte-shop'),
            'qty' => __('Quantity', 'latte-shop'),
            'remove' => __('Remove', 'latte-shop'),
            'update_cart' => __('Update cart', 'latte-shop'),
            'empty_cart' => __('Your cart is empty.', 'latte-shop'),
            'continue_shopping' => __('Continue shopping', 'latte-shop'),
            'place_order' => __('Place order', 'latte-shop'),
            'out_of_stock' => __('Out of stock', 'latte-shop'),
            'sale' => __('Sale', 'latte-shop'),
            'all_products' => __('All', 'latte-shop'),
            'shop_now' => __('Shop now', 'latte-shop'),
            'featured' => __('Featured products', 'latte-shop'),
            'order_received' => __('Thank you — your order has been received.', 'latte-shop'),
            'order_number' => __('Order', 'latte-shop'),
        ];

        return $context;
    }

    private static function notice(): ?array
    {
        $code = isset($_GET['cart']) ? sanitize_key(wp_unslash($_GET['cart'])) : '';
        return match ($code) {
            'added' => ['variant' => 'success', 'icon' => 'circle-check', 'text' => __('Added to your cart.', 'latte-shop')],
            'removed' => ['variant' => 'default', 'icon' => 'trash-2', 'text' => __('Item removed.', 'latte-shop')],
            'updated' => ['variant' => 'default', 'icon' => 'check', 'text' => __('Cart updated.', 'latte-shop')],
            'unavailable' => ['variant' => 'destructive', 'icon' => 'x', 'text' => __('Sorry, this product is unavailable.', 'latte-shop')],
            'empty' => ['variant' => 'default', 'icon' => 'shopping-cart', 'text' => __('Your cart is empty.', 'latte-shop')],
            default => null,
        };
    }
}
