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
 * Extension points for modules and brand children (no template overrides):
 *   wpfasty_slots                  (array $slots, array $context)  views in product.purchase, product.after,
 *                                  checkout.consents, checkout.order, author.after, footer (see Latte base-theme.php)
 *   wpfasty_shop_product_fields    (array $fields)                 extra product fields (types/product.php)
 *   wpfasty_shop_checkout_fields   (array $fields)                 checkout form fields
 *   wpfasty_shop_checkout_payments (array $payments)               payment methods shown at checkout
 *   wpfasty_shop_digital_only      (bool)                          downloads-only store: no address, no shipping
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
        add_filter('wpfasty_types', [self::class, 'types']);
        add_action('after_switch_theme', [self::class, 'ensurePages']);
        // Payment ids the orders module accepts follow the methods shown at checkout.
        add_filter('wpfasty_shop_payment_methods', static fn (array $ids): array => self::digitalOnly()
            ? array_column(self::payments(), 'id')
            : $ids);
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
            is_author() && self::authorProducts() !== [] => 'pages/author',
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
        $shop['account_url'] = is_user_logged_in() ? admin_url('profile.php') : wp_login_url(self::pageUrl('cart'));
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
                'values' => ['payment' => (string) (self::payments()[0]['id'] ?? ''), 'country' => ''],
            ]) + [
                'fields' => self::checkoutFields(),
                'payments' => self::payments(),
                'digital' => self::digitalOnly(),
            ];
            $context['checkout']['error_labels'] = self::errorLabels((array) ($context['checkout']['errors'] ?? []));
        }

        if (is_author() && self::authorProducts() !== []) {
            $user = get_queried_object();
            $products = self::authorProducts();
            $name = $user instanceof \WP_User ? (string) $user->display_name : '';
            $context['author'] = [
                'name' => $name,
                'initial' => mb_substr($name, 0, 1),
                'bio' => $user instanceof \WP_User ? (string) get_user_meta($user->ID, 'description', true) : '',
                'since' => $user instanceof \WP_User ? mysql2date('Y', $user->user_registered) : '',
                'items' => count($products),
            ];
            $context['products'] = $products;
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
            'check_fields' => __('Please check', 'latte-shop'),
            'payment' => __('Payment', 'latte-shop'),
            'demo_notice' => __('Demo store — no real payment is taken.', 'latte-shop'),
            'unavailable_title' => __('Temporarily unavailable', 'latte-shop'),
            'unavailable_text' => __('This feature is temporarily unavailable. Please try again later.', 'latte-shop'),
            'back_to_site' => __('Back to site', 'latte-shop'),
            'no_products' => __('No products found.', 'latte-shop'),
            'breadcrumb' => __('Breadcrumb', 'latte-shop'),
            'author_items' => __('items', 'latte-shop'),
            'author_since' => __('author since', 'latte-shop'),
            'by_author' => __('by', 'latte-shop'),
            'account' => __('Account', 'latte-shop'),
            'account_search' => __('Search', 'latte-shop'),
            'returns' => __('30-day easy returns', 'latte-shop'),
            'instant_download' => __('Instant download after payment', 'latte-shop'),
            'updates_included' => __('Updates included', 'latte-shop'),
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
     * Product type extension: modules add fields (licenses, downloads, …) through `wpfasty_shop_product_fields`;
     * the cart reads license tiers from `licenses` and treats products as downloads in a digital store.
     *
     * @param array<mixed> $defs
     * @return array<mixed>
     */
    public static function types(array $defs): array
    {
        foreach ($defs as $i => $def) {
            if (!is_array($def) || ($def['name'] ?? '') !== 'product') {
                continue;
            }
            $def['fields'] = (array) apply_filters('wpfasty_shop_product_fields', (array) ($def['fields'] ?? []));
            $buy = (array) ($def['supports']['purchasable'] ?? []);
            if ($buy !== []) {
                $buy += ['license_field' => 'licenses', 'digital' => self::digitalOnly()];
                $def['supports']['purchasable'] = $buy;
            }
            $defs[$i] = $def;
        }
        return $defs;
    }

    /**
     * Downloads-only checkout: the cart module's `digital_only` setting or a cart holding only digital items
     * (no address, online payments). Filter `wpfasty_shop_digital_only` has the final word.
     */
    public static function digitalOnly(): bool
    {
        $cart = (array) apply_filters('wpfasty_shop_cart', ['items' => []]);
        return (bool) apply_filters('wpfasty_shop_digital_only', !empty($cart['digital']));
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: string, 4: bool}> name, label, type, autocomplete, required */
    public static function checkoutFields(): array
    {
        $fields = self::digitalOnly()
            ? [
                ['name', __('Full name', 'latte-shop'), 'text', 'name', true],
                ['email', __('Email for download links', 'latte-shop'), 'email', 'email', true],
                ['phone', __('Phone', 'latte-shop'), 'tel', 'tel', false],
            ]
            : [
                ['name', __('Full name', 'latte-shop'), 'text', 'name', true],
                ['email', __('Email', 'latte-shop'), 'email', 'email', true],
                ['phone', __('Phone', 'latte-shop'), 'tel', 'tel', false],
                ['country', __('Country', 'latte-shop'), 'text', 'country-name', true],
                ['city', __('City', 'latte-shop'), 'text', 'address-level2', true],
                ['postcode', __('Postcode', 'latte-shop'), 'text', 'postal-code', true],
            ];
        return array_values((array) apply_filters('wpfasty_shop_checkout_fields', $fields));
    }

    /**
     * Human names of the invalid checkout fields (the orders module reports field ids).
     *
     * @param array<mixed> $errors
     * @return list<string>
     */
    public static function errorLabels(array $errors): array
    {
        $names = [
            'address' => __('Address', 'latte-shop'),
            'payment' => __('Payment', 'latte-shop'),
            'consent_offer' => __('Acceptance of the offer', 'latte-shop'),
            'consent_pd' => __('Consent to personal data processing', 'latte-shop'),
        ];
        foreach (self::checkoutFields() as $f) {
            $names[$f[0]] = $f[1];
        }
        $out = [];
        foreach ($errors as $e) {
            $e = (string) $e;
            $out[] = $names[$e] ?? $e;
        }
        return $out;
    }

    /** @return list<array{id: string, label: string, icon: string}> */
    public static function payments(): array
    {
        $payments = self::digitalOnly()
            ? [
                ['id' => 'card', 'label' => __('Bank card', 'latte-shop'), 'icon' => 'credit-card'],
                ['id' => 'sbp', 'label' => __('Faster Payments System (SBP)', 'latte-shop'), 'icon' => 'send'],
                ['id' => 'bank', 'label' => __('Invoice for companies', 'latte-shop'), 'icon' => 'briefcase'],
            ]
            : [
                ['id' => 'cod', 'label' => __('Cash on delivery', 'latte-shop'), 'icon' => 'truck'],
                ['id' => 'bank', 'label' => __('Bank transfer', 'latte-shop'), 'icon' => 'credit-card'],
            ];
        return array_values((array) apply_filters('wpfasty_shop_checkout_payments', $payments));
    }

    /**
     * Products of the queried author (author archive), as product cards.
     *
     * @return list<array<string, mixed>>
     */
    public static function authorProducts(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }
        $user = get_queried_object();
        if (!$user instanceof \WP_User) {
            return $cache = [];
        }
        $ids = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'author' => $user->ID,
            'numberposts' => 48,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        $all = $ids === [] ? [] : wpfasty_type_posts('product', 48);
        $cache = array_values(array_filter(
            $all,
            static fn (array $p): bool => in_array((int) ($p['id'] ?? 0), $ids, true)
        ));
        return $cache;
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
        $user = get_userdata((int) $post->post_author);
        $isSeller = $user instanceof \WP_User && user_can($user, 'edit_posts') && !user_can($user, 'manage_options');
        $data['seller'] = $isSeller && $user instanceof \WP_User
            ? ['name' => (string) $user->display_name, 'url' => (string) get_author_posts_url($user->ID)]
            : null;
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
