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
 *   wpfasty_cookie_consent (array $state)                cookie banner on/off                — cookie-consent.
 */

declare(strict_types=1);

namespace WpfastyMarket;

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

        return $page ? (string) get_permalink($page) : home_url('/'.$slug.'/');
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
        if (null !== $range) {
            $meta[] = ['key' => '_shop_price', 'value' => $range, 'type' => 'NUMERIC', 'compare' => 'BETWEEN'];
        }
        if (isset($_GET['rating']) && absint(wp_unslash($_GET['rating'])) > 0) {
            $min = absint(wp_unslash($_GET['rating']));
            $meta[] = ['key' => '_shop_rating', 'value' => $min, 'type' => 'DECIMAL', 'compare' => '>='];
        }
        if ([] !== $meta) {
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
     *
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
            // admin-post actions of ModulY cart >= 0.4 / orders >= 0.4 (the modules set the same keys)
            'cart_action' => 'moduly_cart',
            'checkout_action' => 'moduly_checkout',
            'count' => 0,
            'free_shipping' => '',
        ]);
        // Availability flags for the views (disable scenario): the cart module sets `enabled`, the orders module the
        // checkout nonce. Without them the views hide the buy buttons and show "temporarily unavailable".
        $shop['cart_available'] = !empty($shop['enabled']);
        $shop['checkout_available'] = $shop['cart_available'] && '' !== (string) ($shop['checkout_nonce'] ?? '');
        $context['shop'] = $shop + [
            'search_url' => home_url('/'),
            'notice' => self::notice(),
            'categories' => $filter['items'],
            'current_cat' => $filter['current'],
            'sort' => self::param('sort', self::SORTS),
            'price' => self::param('price', self::PRICES),
            'rating' => isset($_GET['rating']) ? absint(wp_unslash($_GET['rating'])) : 0,
            'sorts' => [
                ['id' => 'popular', 'label' => __('Best sellers', 'wpfasty-market')],
                ['id' => 'new', 'label' => __('Newest', 'wpfasty-market')],
                ['id' => 'rating', 'label' => __('Top rated', 'wpfasty-market')],
                ['id' => 'price-asc', 'label' => __('Price: low to high', 'wpfasty-market')],
                ['id' => 'price-desc', 'label' => __('Price: high to low', 'wpfasty-market')],
            ],
            'prices' => [
                ['id' => 'any', 'label' => __('Any price', 'wpfasty-market')],
                ['id' => 'free-50', 'label' => __('Up to 5,000', 'wpfasty-market')],
                ['id' => '50-100', 'label' => __('5,000 – 10,000', 'wpfasty-market')],
                ['id' => '100-plus', 'label' => __('10,000 and more', 'wpfasty-market')],
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
            'email' => 'support@'.wp_parse_url(home_url(), PHP_URL_HOST),
        ]);

        $context['cookieConsent'] = (array) apply_filters('wpfasty_cookie_consent', ['enabled' => false]);

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
                    ['name', __('Full name', 'wpfasty-market'), 'text', 'name', true],
                    ['email', __('Email for download links', 'wpfasty-market'), 'email', 'email', true],
                    ['phone', __('Phone', 'wpfasty-market'), 'tel', 'tel', false],
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
            'placeholder' => $context['labels']['searchPlaceholder'],
            'submit' => $context['labels']['search'],
        ]);
        $context['labels']['searchSite'] = $context['labels']['search'];

        return $context;
    }

    /** @return list<array{id: string, label: string, icon: string}> */
    public static function payments(): array
    {
        return [
            ['id' => 'card', 'label' => __('Bank card', 'wpfasty-market'), 'icon' => 'credit-card'],
            ['id' => 'sbp', 'label' => __('Faster Payments System (SBP)', 'wpfasty-market'), 'icon' => 'send'],
            ['id' => 'bank', 'label' => __('Invoice for companies', 'wpfasty-market'), 'icon' => 'briefcase'],
        ];
    }

    /**
     * Product view-model (module `context` of types/product.php, receives the Core Post DTO): prices, licenses and
     * reviews from the modules,
     * digital-goods details from the product fields.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public static function productViewModel(array $data, \WPFasty\Core\Model\Post $post): array
    {
        $fields = (array) ($data['fields'] ?? []);
        $data['product'] = self::product($post->id, (string) ($fields['sku'] ?? ''));
        $data['productCats'] = $data['terms']['product_cat'] ?? [];
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

    /** Admin list column value (types/product.php `columns`). */
    public static function column(int $postId, string $column): string
    {
        $product = self::product($postId);
        if ('stock' === $column) {
            return null === $product['stock'] ? '∞' : (string) $product['stock'];
        }

        return (string) ($product[$column] ?? '');
    }

    private static function param(string $key, array $allowed): string
    {
        $raw = isset($_GET[$key]) ? sanitize_key(wp_unslash($_GET[$key])) : '';

        return in_array($raw, $allowed, true) ? $raw : $allowed[0];
    }

    /**
     * Digital-goods details from the product fields (types/product.php); author profiles of the demo authors come
     * from Fixtures when the `author` field has no user.
     *
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $product
     *
     * @return array<string, mixed>
     */
    private static function digital(array $fields, \WPFasty\Core\Model\Post $post, array $product): array
    {
        $changelog = [];
        foreach (preg_split('/\R/', (string) ($fields['changelog'] ?? '')) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line, 3));
            if (3 === count($parts) && '' !== $parts[0]) {
                $changelog[] = ['version' => $parts[0], 'date' => $parts[1], 'notes' => $parts[2]];
            }
        }
        $userId = (int) ($fields['author'] ?? 0);
        $user = $userId > 0 ? get_userdata($userId) : false;
        $author = $user instanceof \WP_User
            ? ['name' => $user->display_name, 'slug' => $user->user_nicename, 'role' => '', 'items' => 0,
                'url' => (string) get_author_posts_url($user->ID), ]
            : Fixtures::productAuthor($post->slug);

        return [
            'version' => (string) ($fields['version'] ?? ''),
            'wp' => (string) ($fields['requires_wp'] ?? ''),
            'php' => (string) ($fields['requires_php'] ?? ''),
            'sales' => (int) ($fields['sales_count'] ?? 0),
            'support_months' => (int) ($fields['support_months'] ?? 0),
            'updated' => $changelog[0]['date'] ?? get_the_modified_date('Y-m-d', $post->id),
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

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, string>
     */
    private static function labels(array $context): array
    {
        return [
            'addToCart' => __('Add to cart', 'wpfasty-market'),
            'buyNow' => __('Buy now', 'wpfasty-market'),
            'cart' => __('Cart', 'wpfasty-market'),
            'checkout' => __('Checkout', 'wpfasty-market'),
            'subtotal' => __('Subtotal', 'wpfasty-market'),
            'total' => __('Total', 'wpfasty-market'),
            'qty' => __('Quantity', 'wpfasty-market'),
            'remove' => __('Remove', 'wpfasty-market'),
            'updateCart' => __('Update cart', 'wpfasty-market'),
            'emptyCart' => __('Your cart is empty.', 'wpfasty-market'),
            'continueShopping' => __('Continue shopping', 'wpfasty-market'),
            'placeOrder' => __('Pay and download', 'wpfasty-market'),
            'outOfStock' => __('Unavailable', 'wpfasty-market'),
            'sale' => __('Sale', 'wpfasty-market'),
            'allProducts' => __('All items', 'wpfasty-market'),
            'orderReceived' => __('Thank you! Download links have been sent to your email.', 'wpfasty-market'),
            'orderNumber' => __('Order', 'wpfasty-market'),
            'payment' => __('Payment method', 'wpfasty-market'),
            'demoNotice' => __('Demo store — no real payment is taken.', 'wpfasty-market'),
            'noProducts' => __('Nothing found. Try another filter.', 'wpfasty-market'),
            'breadcrumb' => __('Breadcrumb', 'wpfasty-market'),
            'heroKicker' => __('Marketplace of WordPress products', 'wpfasty-market'),
            'heroTitle' => __('Ready-made WordPress sites, themes and plugins that launch fast', 'wpfasty-market'),
            'heroText' => __(
                'Reviewed by humans, built on clean code, updated for the latest PHP and WordPress.',
                'wpfasty-market'
            ),
            'searchPlaceholder' => __('Search sites, themes and plugins', 'wpfasty-market'),
            'search' => __('Search', 'wpfasty-market'),
            'popularTags' => __('Popular:', 'wpfasty-market'),
            'cat_sites' => __('Ready-made sites', 'wpfasty-market'),
            'cat_sites_text' => __('Full sites with content, ready to launch in a day', 'wpfasty-market'),
            'cat_themes' => __('Themes', 'wpfasty-market'),
            'cat_themes_text' => __('Design systems for blogs, shops and portfolios', 'wpfasty-market'),
            'cat_plugins' => __('Plugins', 'wpfasty-market'),
            'cat_plugins_text' => __('SEO, forms, speed and payments without bloat', 'wpfasty-market'),
            'items' => __('items', 'wpfasty-market'),
            'popular' => __('Popular this week', 'wpfasty-market'),
            'newest' => __('New arrivals', 'wpfasty-market'),
            'viewAll' => __('View all', 'wpfasty-market'),
            'authors' => __('Top authors', 'wpfasty-market'),
            'authorsText' => __('Independent studios and developers who sell on WP FastY.', 'wpfasty-market'),
            'sales' => __('sales', 'wpfasty-market'),
            'since' => __('Author since', 'wpfasty-market'),
            'trustTitle' => __('Why buy on WP FastY', 'wpfasty-market'),
            'trust_review' => __('Every item is reviewed by hand', 'wpfasty-market'),
            'trust_review_text' => __('Code, security and performance are checked before release.', 'wpfasty-market'),
            'trust_updates' => __('Lifetime updates', 'wpfasty-market'),
            'trust_updates_text' => __('Download every new version from your account.', 'wpfasty-market'),
            'trust_support' => __('6 months of author support', 'wpfasty-market'),
            'trust_support_text' => __('Extend support at any time from the item page.', 'wpfasty-market'),
            'trust_pay' => __('Secure payment in rubles', 'wpfasty-market'),
            'trust_pay_text' => __('Cards, SBP and invoices for companies. Receipts under 54-FZ.', 'wpfasty-market'),
            'filters' => __('Filters', 'wpfasty-market'),
            'category' => __('Category', 'wpfasty-market'),
            'price' => __('Price', 'wpfasty-market'),
            'compatibility' => __('Compatibility', 'wpfasty-market'),
            'rating' => __('Rating', 'wpfasty-market'),
            'rating_4' => __('4 stars and up', 'wpfasty-market'),
            'rating_any' => __('Any rating', 'wpfasty-market'),
            'sortBy' => __('Sort by', 'wpfasty-market'),
            'apply' => __('Apply', 'wpfasty-market'),
            'reset' => __('Reset', 'wpfasty-market'),
            'results' => __('results', 'wpfasty-market'),
            'livePreview' => __('Live preview', 'wpfasty-market'),
            'license' => __('License', 'wpfasty-market'),
            'license_regular' => __('Regular license', 'wpfasty-market'),
            'license_regular_text' => __('One end product, end users are not charged.', 'wpfasty-market'),
            'license_extended' => __('Extended license', 'wpfasty-market'),
            'license_extended_text' => __('One end product that end users can be charged for.', 'wpfasty-market'),
            'included' => __('Included: future updates and 6 months of support.', 'wpfasty-market'),
            'version' => __('Version', 'wpfasty-market'),
            'updated' => __('Last update', 'wpfasty-market'),
            'wp_version' => __('WordPress', 'wpfasty-market'),
            'php_version' => __('PHP', 'wpfasty-market'),
            'changelog' => __('Changelog', 'wpfasty-market'),
            'description' => __('Description', 'wpfasty-market'),
            'support' => __('Support', 'wpfasty-market'),
            'supportText' => __(
                'The author replies within one business day. Bugs and feature questions are covered.',
                'wpfasty-market'
            ),
            'reviews' => __('Reviews', 'wpfasty-market'),
            'by' => __('by', 'wpfasty-market'),
            'authorItems' => __('Items by this author', 'wpfasty-market'),
            'gallery' => __('Screenshots', 'wpfasty-market'),
            'yourDetails' => __('Your details', 'wpfasty-market'),
            'digitalDelivery' => __(
                'Digital delivery: download links arrive by email right after payment.',
                'wpfasty-market'
            ),
            'consent_offer' => __('I accept the public offer and license agreement', 'wpfasty-market'),
            'consent_pd' => __('I consent to the processing of my personal data', 'wpfasty-market'),
            'required' => __('Required', 'wpfasty-market'),
            'secure' => __('Secure checkout', 'wpfasty-market'),
            'refundNote' => __('Refunds follow the refund policy for digital goods.', 'wpfasty-market'),
            'footerAbout' => __(
                'WP FastY is a marketplace of ready-made WordPress sites, themes and plugins.',
                'wpfasty-market'
            ),
            'footerMarket' => __('Marketplace', 'wpfasty-market'),
            'footerDocs' => __('Documents', 'wpfasty-market'),
            'footerCompany' => __('Seller', 'wpfasty-market'),
            'ogrn' => __('OGRN', 'wpfasty-market'),
            'inn' => __('INN', 'wpfasty-market'),
            'address' => __('Address', 'wpfasty-market'),
            'legal_oferta' => __('Public offer and license agreement', 'wpfasty-market'),
            'legal_privacy' => __('Privacy policy', 'wpfasty-market'),
            'legal_consent' => __('Consent to personal data processing', 'wpfasty-market'),
            'legal_newsletter-consent' => __('Consent to newsletters', 'wpfasty-market'),
            'legal_cookies' => __('Cookie policy', 'wpfasty-market'),
            'legal_refund' => __('Refund policy', 'wpfasty-market'),
            'legal_requisites' => __('Seller details', 'wpfasty-market'),
            'consent_news' => __('I want to receive news and offers by email (optional)', 'wpfasty-market'),
            'cookieText' => __(
                'We use necessary cookies to run the store and, with your consent, analytics cookies.',
                'wpfasty-market'
            ),
            'cookieAccept' => __('Accept all', 'wpfasty-market'),
            'cookieNecessary' => __('Necessary only', 'wpfasty-market'),
            'cookieSettings' => __('Cookie settings', 'wpfasty-market'),
            'rights' => __('All rights reserved.', 'wpfasty-market'),
            'previous' => __('Previous', 'wpfasty-market'),
            'next' => __('Next', 'wpfasty-market'),
            'pagination' => __('Pagination', 'wpfasty-market'),
            'unavailableTitle' => __('Temporarily unavailable', 'wpfasty-market'),
            'unavailableText' => __(
                'This feature is temporarily unavailable. Please try again later.',
                'wpfasty-market'
            ),
            'backToSite' => __('Back to site', 'wpfasty-market'),
            'downloads' => __('Downloads', 'wpfasty-market'),
            'download' => __('Download', 'wpfasty-market'),
            'downloadsPending' => __(
                'Download links appear here and in your email as soon as the payment is confirmed.',
                'wpfasty-market'
            ),
            'linkExpires' => __('Link valid until', 'wpfasty-market'),
            'downloadsLeft' => __('downloads left', 'wpfasty-market'),
            'verifiedBuyer' => __('Verified buyer', 'wpfasty-market'),
            'writeReview' => __('Write a review', 'wpfasty-market'),
            'reviewNote' => __('Only buyers can review: use the email of your paid order.', 'wpfasty-market'),
            'your_name' => __('Name', 'wpfasty-market'),
            'your_email' => __('Order email', 'wpfasty-market'),
            'yourRating' => __('Rating', 'wpfasty-market'),
            'reviewText' => __('Your review', 'wpfasty-market'),
            'submitReview' => __('Send review', 'wpfasty-market'),
            'noReviews' => __('No reviews yet.', 'wpfasty-market'),
            'licenseTier' => __('License', 'wpfasty-market'),
        ];
    }

    private static function notice(): ?array
    {
        $code = isset($_GET['cart']) ? sanitize_key(wp_unslash($_GET['cart'])) : '';

        return match ($code) {
            'added' => [
                'variant' => 'success',
                'icon' => 'circle-check',
                'text' => __('Added to your cart.', 'wpfasty-market'),
            ],
            'removed' => [
                'variant' => 'default',
                'icon' => 'trash-2',
                'text' => __('Item removed.', 'wpfasty-market'),
            ],
            'updated' => ['variant' => 'default', 'icon' => 'check', 'text' => __('Cart updated.', 'wpfasty-market')],
            'unavailable' => [
                'variant' => 'destructive',
                'icon' => 'x',
                'text' => __('Sorry, this item is unavailable.', 'wpfasty-market'),
            ],
            'empty' => [
                'variant' => 'default',
                'icon' => 'shopping-cart',
                'text' => __('Your cart is empty.', 'wpfasty-market'),
            ],
            default => null,
        };
    }
}
