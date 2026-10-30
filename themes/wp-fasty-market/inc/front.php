<?php

/**
 * Front-end (presentation only): view routing, market pages, catalog sorting/filters, labels and view-models.
 * Prices, license tiers, cart, checkout, orders, downloads, consents and reviews come from ModulY modules through
 * filters with safe defaults (disable scenario: without a module its forms are hidden / "temporarily unavailable"):
 *   wpfasty_shop_state    (array $shop)                  URLs, nonces, cart count          — cart, orders
 *   wpfasty_shop_cart     (array $cart)                  priced cart, license tiers         — cart
 *   wpfasty_shop_product  (array $product, int $postId)  price / licenses / reviews         — cart, reviews
 *   wpfasty_shop_checkout (array $checkout)              errors, kept values, consents      — orders, consent-log
 *   wpfasty_shop_order    (?array $order)                confirmation + download links      — orders, downloads
 *   wpfasty_cookie_consent (array $state)                cookie banner on/off                — cookie-consent
 *
 * @package WP_FastY_Market
 */

declare(strict_types=1);

namespace WpFastyMarket;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display params.

final class Front
{
    public const PAGES = ['cart' => 'Cart', 'checkout' => 'Checkout', 'author' => 'Author'];

    /** Footer legal documents: page slug => label key. */
    public const LEGAL = ['oferta', 'privacy', 'consent', 'newsletter-consent', 'cookies', 'refund', 'requisites'];

    public const SORTS = ['popular', 'new', 'rating', 'price-asc', 'price-desc'];

    public const PRICES = ['any', 'free-50', '50-100', '100-plus'];

    public static function register(): void
    {
        add_filter('wpfasty_resolve_view', [self::class, 'view']);
        add_filter('wpfasty_context', [self::class, 'context']);
        add_action('after_switch_theme', [self::class, 'ensurePages']);
        add_action('pre_get_posts', [self::class, 'catalogQuery']);
        // Store configuration for the orders module: payment methods offered by this digital store.
        add_filter('wpfasty_shop_payment_methods', static fn (): array => array_column(self::payments(), 'id'));
        add_action('template_redirect', static function (): void {
            if (is_page(['cart', 'checkout'])) {
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
            is_page('author') => 'pages/author',
            default => $view,
        };
    }

    private static function param(string $key, array $allowed): string
    {
        $raw = isset($_GET[$key]) ? sanitize_key(wp_unslash($_GET[$key])) : '';
        return in_array($raw, $allowed, true) ? $raw : $allowed[0];
    }

    /** Catalog sort and price/rating filters on the product archive (main query only). */
    public static function catalogQuery(\WP_Query $q): void
    {
        if (is_admin() || !$q->is_main_query() || !($q->is_post_type_archive('product') || $q->is_tax('product_cat'))) {
            return;
        }
        $sort = self::param('sort', self::SORTS);
        $meta = [];
        $price = self::param('price', self::PRICES);
        $ranges = ['free-50' => [0, 500000], '50-100' => [500000, 1000000], '100-plus' => [1000000, PHP_INT_MAX]];
        $range = $ranges[$price] ?? null;
        if ($range !== null) {
            $meta[] = ['key' => '_shop_price', 'value' => $range, 'type' => 'NUMERIC', 'compare' => 'BETWEEN'];
        }
        if (isset($_GET['rating']) && absint(wp_unslash($_GET['rating'])) > 0) {
            $min = absint(wp_unslash($_GET['rating']));
            $meta[] = ['key' => '_shop_rating', 'value' => $min, 'type' => 'DECIMAL', 'compare' => '>='];
        }
        if ($meta !== []) {
            $q->set('meta_query', $meta); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        }
        $order = match ($sort) {
            'new' => ['orderby' => 'date', 'order' => 'DESC'],
            'rating' => ['meta_key' => '_shop_rating', 'orderby' => 'meta_value_num', 'order' => 'DESC'],
            'price-asc' => ['meta_key' => '_shop_price', 'orderby' => 'meta_value_num', 'order' => 'ASC'],
            'price-desc' => ['meta_key' => '_shop_price', 'orderby' => 'meta_value_num', 'order' => 'DESC'],
            default => ['meta_key' => '_shop_reviews', 'orderby' => 'meta_value_num', 'order' => 'DESC'],
        };
        foreach ($order as $k => $v) {
            $q->set($k, $v);
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public static function context(array $context): array
    {
        $filter = wpfasty_term_filter('product_cat');
        $rank = ['sites' => 0, 'themes' => 1, 'plugins' => 2];
        usort(
            $filter['items'],
            static fn (array $a, array $b): int => ($rank[$a['slug']] ?? 9) <=> ($rank[$b['slug']] ?? 9)
        );

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
        // checkout nonce. Without them the views hide the buy buttons and show "temporarily unavailable".
        $shop['cart_available'] = !empty($shop['enabled']);
        $shop['checkout_available'] = $shop['cart_available'] && (string) ($shop['checkout_nonce'] ?? '') !== '';
        $context['shop'] = $shop + [
            'search_url' => home_url('/'),
            'notice' => self::notice(),
            'categories' => $filter['items'],
            'current_cat' => $filter['current'],
            'sort' => self::param('sort', self::SORTS),
            'price' => self::param('price', self::PRICES),
            'rating' => isset($_GET['rating']) ? absint(wp_unslash($_GET['rating'])) : 0,
            'sorts' => [
                ['id' => 'popular', 'label' => __('Best sellers', 'wp-fasty-market')],
                ['id' => 'new', 'label' => __('Newest', 'wp-fasty-market')],
                ['id' => 'rating', 'label' => __('Top rated', 'wp-fasty-market')],
                ['id' => 'price-asc', 'label' => __('Price: low to high', 'wp-fasty-market')],
                ['id' => 'price-desc', 'label' => __('Price: high to low', 'wp-fasty-market')],
            ],
            'prices' => [
                ['id' => 'any', 'label' => __('Any price', 'wp-fasty-market')],
                ['id' => 'free-50', 'label' => __('Up to 5,000', 'wp-fasty-market')],
                ['id' => '50-100', 'label' => __('5,000 – 10,000', 'wp-fasty-market')],
                ['id' => '100-plus', 'label' => __('10,000 and more', 'wp-fasty-market')],
            ],
            'compat' => [
                ['id' => 'wp-6.5', 'label' => 'WordPress 6.5+'],
                ['id' => 'wp-6.4', 'label' => 'WordPress 6.4+'],
                ['id' => 'php-8.2', 'label' => 'PHP 8.2+'],
                ['id' => 'php-8.1', 'label' => 'PHP 8.1+'],
            ],
            'legal' => array_map(
                static fn (string $slug): array => ['slug' => $slug, 'url' => self::pageUrl($slug)],
                self::LEGAL
            ),
            'author_url' => self::pageUrl('author'),
        ];
        $context['company'] = (array) apply_filters('wpfasty_market_company', [
            'name' => 'ООО «ИТ Гараж»',
            'name_en' => 'IT Garage Ltd',
            'ogrn' => '0000',
            'inn' => '[ИНН]',
            'address' => '[АДРЕС]',
            'email' => 'support@' . wp_parse_url(home_url(), PHP_URL_HOST),
        ]);

        $context['cookie_consent'] = (array) apply_filters('wpfasty_cookie_consent', ['enabled' => false]);

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

        if (is_page('author')) {
            $slug = isset($_GET['a']) ? sanitize_key(wp_unslash($_GET['a'])) : 'fasty-studio';
            $author = Fixtures::author($slug) ?? Fixtures::author('fasty-studio');
            $context['author'] = $author;
            $context['products'] = array_values(array_filter(
                wpfasty_type_posts('product', 24),
                static fn (array $p): bool => ($p['digital']['author']['slug'] ?? '') === ($author['slug'] ?? '')
            ));
        }

        if (is_page('checkout')) {
            $order = apply_filters('wpfasty_shop_order', null);
            $context['order'] = is_array($order) ? $order : null;
            $context['checkout'] = (array) apply_filters('wpfasty_shop_checkout', [
                'errors' => [],
                'values' => ['payment' => 'card'],
                'consents' => [],
            ]) + [
                'fields' => [
                    ['name', __('Full name', 'wp-fasty-market'), 'text', 'name', true],
                    ['email', __('Email for download links', 'wp-fasty-market'), 'email', 'email', true],
                    ['phone', __('Phone', 'wp-fasty-market'), 'tel', 'tel', false],
                ],
                'payments' => self::payments(),
            ];
        }

        if (is_front_page()) {
            $all = wpfasty_type_posts('product', 24);
            $popular = $all;
            usort(
                $popular,
                static fn (array $a, array $b): int => ($b['digital']['sales'] ?? 0) <=> ($a['digital']['sales'] ?? 0)
            );
            $context['popular'] = array_slice($popular, 0, 4);
            $context['newest'] = array_slice($all, 0, 4);
            $context['authors'] = Fixtures::authors();
        }

        $context['labels'] = array_merge($context['labels'], self::labels($context));
        $context['search'] = array_merge((array) ($context['search'] ?? []), [
            'label' => $context['labels']['search'],
            'placeholder' => $context['labels']['search_placeholder'],
            'submit' => $context['labels']['search'],
        ]);
        $context['labels']['search_site'] = $context['labels']['search'];

        return $context;
    }

    /** @return list<array{id: string, label: string, icon: string}> */
    public static function payments(): array
    {
        return [
            ['id' => 'card', 'label' => __('Bank card', 'wp-fasty-market'), 'icon' => 'credit-card'],
            ['id' => 'sbp', 'label' => __('Faster Payments System (SBP)', 'wp-fasty-market'), 'icon' => 'send'],
            ['id' => 'bank', 'label' => __('Invoice for companies', 'wp-fasty-market'), 'icon' => 'briefcase'],
        ];
    }

    /**
     * Product view-model (module `context` of types/product.php): prices, licenses and reviews from the modules,
     * digital-goods details from the product fields.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function productViewModel(array $data, \WP_Post $post): array
    {
        $fields = (array) ($data['fields'] ?? []);
        $data['product'] = self::product($post->ID, (string) ($fields['sku'] ?? ''));
        $data['product_cats'] = $data['terms']['product_cat'] ?? [];
        $data['rating'] = (float) ($fields['rating'] ?? 0);
        $data['reviews'] = (int) ($fields['reviews'] ?? 0);
        $data['badge'] = (string) ($fields['badge'] ?? '');
        $data['digital'] = self::digital($fields, $post, $data['product']);
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
            'licenses' => [],
            'reviews_available' => false,
            'reviews_list' => [],
        ], $postId);
    }

    /**
     * Digital-goods details from the product fields (types/product.php); author profiles of the demo authors come
     * from Fixtures when the `author` field has no user.
     *
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private static function digital(array $fields, \WP_Post $post, array $product): array
    {
        $changelog = [];
        foreach (preg_split('/\R/', (string) ($fields['changelog'] ?? '')) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line, 3));
            if (count($parts) === 3 && $parts[0] !== '') {
                $changelog[] = ['version' => $parts[0], 'date' => $parts[1], 'notes' => $parts[2]];
            }
        }
        $userId = (int) ($fields['author'] ?? 0);
        $user = $userId > 0 ? get_userdata($userId) : false;
        $author = $user instanceof \WP_User
            ? ['name' => $user->display_name, 'slug' => $user->user_nicename, 'role' => '', 'items' => 0,
                'url' => (string) get_author_posts_url($user->ID)]
            : Fixtures::productAuthor($post->post_name);
        return [
            'version' => (string) ($fields['version'] ?? ''),
            'wp' => (string) ($fields['requires_wp'] ?? ''),
            'php' => (string) ($fields['requires_php'] ?? ''),
            'sales' => (int) ($fields['sales_count'] ?? 0),
            'support_months' => (int) ($fields['support_months'] ?? 0),
            'updated' => $changelog[0]['date'] ?? get_the_modified_date('Y-m-d', $post),
            'author' => $author,
            'preview_url' => (string) ($fields['preview_url'] ?? ''),
            'licenses' => array_map(static fn (array $l): array => [
                'id' => (string) ($l['tier'] ?? ''),
                'label' => (string) ($l['label'] ?? ''),
                'price' => (string) ($l['price'] ?? ''),
            ], (array) ($product['licenses'] ?? [])),
            'changelog' => $changelog,
            'reviews' => array_map(static fn (array $r): array => [
                'name' => (string) ($r['author'] ?? ''),
                'rating' => max(0, min(5, (int) ($r['rating'] ?? 0))),
                'text' => (string) ($r['text'] ?? ''),
                'verified' => !empty($r['verified']),
            ], (array) ($product['reviews_list'] ?? [])),
            'reviews_available' => !empty($product['reviews_available']),
            'review_action' => (string) ($product['review_action'] ?? ''),
        ];
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

    /**
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    private static function labels(array $context): array
    {
        return [
            'add_to_cart' => __('Add to cart', 'wp-fasty-market'),
            'buy_now' => __('Buy now', 'wp-fasty-market'),
            'cart' => __('Cart', 'wp-fasty-market'),
            'checkout' => __('Checkout', 'wp-fasty-market'),
            'subtotal' => __('Subtotal', 'wp-fasty-market'),
            'total' => __('Total', 'wp-fasty-market'),
            'qty' => __('Quantity', 'wp-fasty-market'),
            'remove' => __('Remove', 'wp-fasty-market'),
            'update_cart' => __('Update cart', 'wp-fasty-market'),
            'empty_cart' => __('Your cart is empty.', 'wp-fasty-market'),
            'continue_shopping' => __('Continue shopping', 'wp-fasty-market'),
            'place_order' => __('Pay and download', 'wp-fasty-market'),
            'out_of_stock' => __('Unavailable', 'wp-fasty-market'),
            'sale' => __('Sale', 'wp-fasty-market'),
            'all_products' => __('All items', 'wp-fasty-market'),
            'order_received' => __('Thank you! Download links have been sent to your email.', 'wp-fasty-market'),
            'order_number' => __('Order', 'wp-fasty-market'),
            'payment' => __('Payment method', 'wp-fasty-market'),
            'demo_notice' => __('Demo store — no real payment is taken.', 'wp-fasty-market'),
            'no_products' => __('Nothing found. Try another filter.', 'wp-fasty-market'),
            'breadcrumb' => __('Breadcrumb', 'wp-fasty-market'),
            'hero_kicker' => __('Marketplace of WordPress products', 'wp-fasty-market'),
            'hero_title' => __('Ready-made WordPress sites, themes and plugins that launch fast', 'wp-fasty-market'),
            'hero_text' => __(
                'Reviewed by humans, built on clean code, updated for the latest PHP and WordPress.',
                'wp-fasty-market'
            ),
            'search_placeholder' => __('Search sites, themes and plugins', 'wp-fasty-market'),
            'search' => __('Search', 'wp-fasty-market'),
            'popular_tags' => __('Popular:', 'wp-fasty-market'),
            'cat_sites' => __('Ready-made sites', 'wp-fasty-market'),
            'cat_sites_text' => __('Full sites with content, ready to launch in a day', 'wp-fasty-market'),
            'cat_themes' => __('Themes', 'wp-fasty-market'),
            'cat_themes_text' => __('Design systems for blogs, shops and portfolios', 'wp-fasty-market'),
            'cat_plugins' => __('Plugins', 'wp-fasty-market'),
            'cat_plugins_text' => __('SEO, forms, speed and payments without bloat', 'wp-fasty-market'),
            'items' => __('items', 'wp-fasty-market'),
            'popular' => __('Popular this week', 'wp-fasty-market'),
            'newest' => __('New arrivals', 'wp-fasty-market'),
            'view_all' => __('View all', 'wp-fasty-market'),
            'authors' => __('Top authors', 'wp-fasty-market'),
            'authors_text' => __('Independent studios and developers who sell on WP FastY.', 'wp-fasty-market'),
            'sales' => __('sales', 'wp-fasty-market'),
            'since' => __('Author since', 'wp-fasty-market'),
            'trust_title' => __('Why buy on WP FastY', 'wp-fasty-market'),
            'trust_review' => __('Every item is reviewed by hand', 'wp-fasty-market'),
            'trust_review_text' => __('Code, security and performance are checked before release.', 'wp-fasty-market'),
            'trust_updates' => __('Lifetime updates', 'wp-fasty-market'),
            'trust_updates_text' => __('Download every new version from your account.', 'wp-fasty-market'),
            'trust_support' => __('6 months of author support', 'wp-fasty-market'),
            'trust_support_text' => __('Extend support at any time from the item page.', 'wp-fasty-market'),
            'trust_pay' => __('Secure payment in rubles', 'wp-fasty-market'),
            'trust_pay_text' => __('Cards, SBP and invoices for companies. Receipts under 54-FZ.', 'wp-fasty-market'),
            'filters' => __('Filters', 'wp-fasty-market'),
            'category' => __('Category', 'wp-fasty-market'),
            'price' => __('Price', 'wp-fasty-market'),
            'compatibility' => __('Compatibility', 'wp-fasty-market'),
            'rating' => __('Rating', 'wp-fasty-market'),
            'rating_4' => __('4 stars and up', 'wp-fasty-market'),
            'rating_any' => __('Any rating', 'wp-fasty-market'),
            'sort_by' => __('Sort by', 'wp-fasty-market'),
            'apply' => __('Apply', 'wp-fasty-market'),
            'reset' => __('Reset', 'wp-fasty-market'),
            'results' => __('results', 'wp-fasty-market'),
            'live_preview' => __('Live preview', 'wp-fasty-market'),
            'license' => __('License', 'wp-fasty-market'),
            'license_regular' => __('Regular license', 'wp-fasty-market'),
            'license_regular_text' => __('One end product, end users are not charged.', 'wp-fasty-market'),
            'license_extended' => __('Extended license', 'wp-fasty-market'),
            'license_extended_text' => __('One end product that end users can be charged for.', 'wp-fasty-market'),
            'included' => __('Included: future updates and 6 months of support.', 'wp-fasty-market'),
            'version' => __('Version', 'wp-fasty-market'),
            'updated' => __('Last update', 'wp-fasty-market'),
            'wp_version' => __('WordPress', 'wp-fasty-market'),
            'php_version' => __('PHP', 'wp-fasty-market'),
            'changelog' => __('Changelog', 'wp-fasty-market'),
            'description' => __('Description', 'wp-fasty-market'),
            'support' => __('Support', 'wp-fasty-market'),
            'support_text' => __(
                'The author replies within one business day. Bugs and feature questions are covered.',
                'wp-fasty-market'
            ),
            'reviews' => __('Reviews', 'wp-fasty-market'),
            'by' => __('by', 'wp-fasty-market'),
            'author_items' => __('Items by this author', 'wp-fasty-market'),
            'gallery' => __('Screenshots', 'wp-fasty-market'),
            'your_details' => __('Your details', 'wp-fasty-market'),
            'digital_delivery' => __(
                'Digital delivery: download links arrive by email right after payment.',
                'wp-fasty-market'
            ),
            'consent_offer' => __('I accept the public offer and license agreement', 'wp-fasty-market'),
            'consent_pd' => __('I consent to the processing of my personal data', 'wp-fasty-market'),
            'required' => __('Required', 'wp-fasty-market'),
            'secure' => __('Secure checkout', 'wp-fasty-market'),
            'refund_note' => __('Refunds follow the refund policy for digital goods.', 'wp-fasty-market'),
            'footer_about' => __(
                'WP FastY is a marketplace of ready-made WordPress sites, themes and plugins.',
                'wp-fasty-market'
            ),
            'footer_market' => __('Marketplace', 'wp-fasty-market'),
            'footer_docs' => __('Documents', 'wp-fasty-market'),
            'footer_company' => __('Seller', 'wp-fasty-market'),
            'ogrn' => __('OGRN', 'wp-fasty-market'),
            'inn' => __('INN', 'wp-fasty-market'),
            'address' => __('Address', 'wp-fasty-market'),
            'legal_oferta' => __('Public offer and license agreement', 'wp-fasty-market'),
            'legal_privacy' => __('Privacy policy', 'wp-fasty-market'),
            'legal_consent' => __('Consent to personal data processing', 'wp-fasty-market'),
            'legal_newsletter-consent' => __('Consent to newsletters', 'wp-fasty-market'),
            'legal_cookies' => __('Cookie policy', 'wp-fasty-market'),
            'legal_refund' => __('Refund policy', 'wp-fasty-market'),
            'legal_requisites' => __('Seller details', 'wp-fasty-market'),
            'consent_news' => __('I want to receive news and offers by email (optional)', 'wp-fasty-market'),
            'cookie_text' => __(
                'We use necessary cookies to run the store and, with your consent, analytics cookies.',
                'wp-fasty-market'
            ),
            'cookie_accept' => __('Accept all', 'wp-fasty-market'),
            'cookie_necessary' => __('Necessary only', 'wp-fasty-market'),
            'cookie_settings' => __('Cookie settings', 'wp-fasty-market'),
            'rights' => __('All rights reserved.', 'wp-fasty-market'),
            'previous' => __('Previous', 'wp-fasty-market'),
            'next' => __('Next', 'wp-fasty-market'),
            'pagination' => __('Pagination', 'wp-fasty-market'),
            'unavailable_title' => __('Temporarily unavailable', 'wp-fasty-market'),
            'unavailable_text' => __(
                'This feature is temporarily unavailable. Please try again later.',
                'wp-fasty-market'
            ),
            'back_to_site' => __('Back to site', 'wp-fasty-market'),
            'downloads' => __('Downloads', 'wp-fasty-market'),
            'download' => __('Download', 'wp-fasty-market'),
            'downloads_pending' => __(
                'Download links appear here and in your email as soon as the payment is confirmed.',
                'wp-fasty-market'
            ),
            'link_expires' => __('Link valid until', 'wp-fasty-market'),
            'downloads_left' => __('downloads left', 'wp-fasty-market'),
            'verified_buyer' => __('Verified buyer', 'wp-fasty-market'),
            'write_review' => __('Write a review', 'wp-fasty-market'),
            'review_note' => __('Only buyers can review: use the email of your paid order.', 'wp-fasty-market'),
            'your_name' => __('Name', 'wp-fasty-market'),
            'your_email' => __('Order email', 'wp-fasty-market'),
            'your_rating' => __('Rating', 'wp-fasty-market'),
            'review_text' => __('Your review', 'wp-fasty-market'),
            'submit_review' => __('Send review', 'wp-fasty-market'),
            'no_reviews' => __('No reviews yet.', 'wp-fasty-market'),
            'license_tier' => __('License', 'wp-fasty-market'),
        ];
    }

    private static function notice(): ?array
    {
        $code = isset($_GET['cart']) ? sanitize_key(wp_unslash($_GET['cart'])) : '';
        return match ($code) {
            'added' => [
                'variant' => 'success',
                'icon' => 'circle-check',
                'text' => __('Added to your cart.', 'wp-fasty-market'),
            ],
            'removed' => [
                'variant' => 'default',
                'icon' => 'trash-2',
                'text' => __('Item removed.', 'wp-fasty-market'),
            ],
            'updated' => ['variant' => 'default', 'icon' => 'check', 'text' => __('Cart updated.', 'wp-fasty-market')],
            'unavailable' => [
                'variant' => 'destructive',
                'icon' => 'x',
                'text' => __('Sorry, this item is unavailable.', 'wp-fasty-market'),
            ],
            'empty' => [
                'variant' => 'default',
                'icon' => 'shopping-cart',
                'text' => __('Your cart is empty.', 'wp-fasty-market'),
            ],
            default => null,
        };
    }
}
