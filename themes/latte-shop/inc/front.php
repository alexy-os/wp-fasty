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
        add_filter('wpfasty_context', [self::class, 'context']);
        add_action('after_switch_theme', [self::class, 'ensurePages']);
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
            is_page('cart') => 'pages/cart',
            is_page('checkout') => 'pages/checkout',
            default => $view,
        };
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public static function context(array $context): array
    {
        $summary = Cart::summary();
        $filter = wpfasty_term_filter('product_cat');

        $context['shop'] = [
            'url' => self::shopUrl(),
            'cart_url' => self::pageUrl('cart'),
            'checkout_url' => self::pageUrl('checkout'),
            'action' => admin_url('admin-post.php'),
            'cart_nonce' => wp_create_nonce('latte_shop_cart'),
            'checkout_nonce' => wp_create_nonce('latte_shop_checkout'),
            'count' => $summary['count'],
            'notice' => self::notice(),
            'categories' => $filter['items'],
            'current_cat' => $filter['current'],
            'free_shipping' => Money::format((int) apply_filters('latte_shop_free_shipping_cents', 10000)),
        ];
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

        if (is_front_page()) {
            $context['products'] = wpfasty_type_posts('product', 8);
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
