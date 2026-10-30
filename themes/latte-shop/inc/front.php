<?php

/**
 * Front-end (presentation only): view routing, shop pages, labels and view-models. Cart, checkout, orders and
 * prices come from the ModulY modules `cart` and `orders` through filters; without them the shop renders as a
 * catalog with nothing to buy:
 *   wpfasty_shop_state    (array $shop)                  URLs, nonces, cart count, free-shipping amount
 *   wpfasty_shop_cart     (array $cart)                  priced cart with display strings
 *   wpfasty_shop_product  (array $product, int $postId)  price / sale / stock / purchasable
 *   wpfasty_shop_checkout (array $checkout)              errors + kept values of the last attempt
 *   wpfasty_shop_order    (?array $order)                order confirmation (?order=&key=)
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
                wp_insert_post([
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'post_name' => $slug,
                    'post_title' => $title,
                ]);
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
        $filter = wpfasty_term_filter('product_cat');

        $shop = (array) apply_filters('wpfasty_shop_state', [
            'enabled' => false,
            'url' => self::shopUrl(),
            'cart_url' => self::pageUrl('cart'),
            'checkout_url' => self::pageUrl('checkout'),
            'action' => admin_url('admin-post.php'),
            'cart_nonce' => '',
            'checkout_nonce' => '',
            'count' => 0,
            'free_shipping' => '',
        ]);
        // Availability flags for the views (disable scenario): the cart module sets `enabled`, the orders module the
        // checkout nonce. Without them the views show "temporarily unavailable" instead of a form that cannot work.
        $shop['cart_available'] = !empty($shop['enabled']);
        $shop['checkout_available'] = $shop['cart_available'] && (string) ($shop['checkout_nonce'] ?? '') !== '';
        $context['shop'] = $shop + [
            'notice' => self::notice(),
            'categories' => $filter['items'],
            'current_cat' => $filter['current'],
        ];
        if (is_page(['cart', 'checkout'])) {
            $context['cart'] = (array) apply_filters('wpfasty_shop_cart', [
                'items' => [],
                'count' => 0,
                'subtotal' => 0,
                'shipping' => 0,
                'total' => 0,
                'subtotal_display' => '',
                'shipping_display' => '',
                'total_display' => '',
            ]);
        }

        if (is_page('checkout')) {
            $order = apply_filters('wpfasty_shop_order', null);
            $context['order'] = is_array($order) ? $order : null;
            $context['checkout'] = (array) apply_filters('wpfasty_shop_checkout', [
                'errors' => [],
                'values' => ['payment' => 'cod', 'country' => ''],
            ]) + [
                'fields' => [
                    ['name', __('Full name', 'latte-shop'), 'text', 'name', true],
                    ['email', __('Email', 'latte-shop'), 'email', 'email', true],
                    ['phone', __('Phone', 'latte-shop'), 'tel', 'tel', false],
                    ['country', __('Country', 'latte-shop'), 'text', 'country-name', true],
                    ['city', __('City', 'latte-shop'), 'text', 'address-level2', true],
                    ['postcode', __('Postcode', 'latte-shop'), 'text', 'postal-code', true],
                ],
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
            'address' => __('Address', 'latte-shop'),
            'payment' => __('Payment', 'latte-shop'),
            'demo_notice' => __('Demo store — no real payment is taken.', 'latte-shop'),
            'unavailable_title' => __('Temporarily unavailable', 'latte-shop'),
            'unavailable_text' => __('This feature is temporarily unavailable. Please try again later.', 'latte-shop'),
            'back_to_site' => __('Back to site', 'latte-shop'),
            'no_products' => __('No products found.', 'latte-shop'),
            'breadcrumb' => __('Breadcrumb', 'latte-shop'),
            'returns' => __('30-day easy returns', 'latte-shop'),
            'pay_options' => __('Pay on delivery or by bank transfer', 'latte-shop'),
            'free_shipping_over' => $context['shop']['free_shipping'] !== ''
                ? sprintf(
                    /* translators: %s: order amount for free shipping */
                    __('Free shipping over %s', 'latte-shop'),
                    $context['shop']['free_shipping']
                )
                : __('Fast delivery', 'latte-shop'),
        ];

        return $context;
    }

    /**
     * Product view-model (module `context` of types/product.php): prices and stock from the cart module.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function productViewModel(array $data, \WP_Post $post): array
    {
        $data['product'] = self::product($post->ID, (string) ($data['fields']['sku'] ?? ''));
        $data['product_cats'] = $data['terms']['product_cat'] ?? [];
        return $data;
    }

    /** @return array<string, mixed> */
    public static function product(int $postId, string $sku = ''): array
    {
        return (array) apply_filters('wpfasty_shop_product', [
            'price' => '',
            'regular' => '',
            'on_sale' => false,
            'sku' => $sku,
            'stock' => null,
            'in_stock' => true,
            'purchasable' => false,
        ], $postId);
    }

    /** Admin list column value (types/product.php `columns`). */
    public static function column(int $postId, string $column): string
    {
        $product = self::product($postId);
        if ($column === 'stock') {
            return $product['stock'] === null ? '∞' : (string) $product['stock'];
        }
        return (string) ($product[$column] ?? '');
    }

    private static function notice(): ?array
    {
        $code = isset($_GET['cart']) ? sanitize_key(wp_unslash($_GET['cart'])) : '';
        return match ($code) {
            'added' => [
                'variant' => 'success',
                'icon' => 'circle-check',
                'text' => __('Added to your cart.', 'latte-shop'),
            ],
            'removed' => ['variant' => 'default', 'icon' => 'trash-2', 'text' => __('Item removed.', 'latte-shop')],
            'updated' => ['variant' => 'default', 'icon' => 'check', 'text' => __('Cart updated.', 'latte-shop')],
            'unavailable' => [
                'variant' => 'destructive',
                'icon' => 'x',
                'text' => __('Sorry, this product is unavailable.', 'latte-shop'),
            ],
            'empty' => [
                'variant' => 'default',
                'icon' => 'shopping-cart',
                'text' => __('Your cart is empty.', 'latte-shop'),
            ],
            default => null,
        };
    }
}
