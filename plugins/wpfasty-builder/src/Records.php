<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WPFastyBuilder\Codex\Format;
use WPFastyBuilder\Codex\ReadPath;

/**
 * Record tokens of Links: {post:<slug>}, {product:<slug>}, {term:<taxonomy>:<slug>} (links) and {price:<product>},
 * {stock:<product>} (text). Products and terms are the purchasable post kind and the taxonomies of the site's Codex
 * manifest (wpfasty-types: types/codex.manifest.json + delivery); posts are the core post type.
 *
 * No N+1: prime() collects every record token of a page (all block props) and loads them in one query per type —
 * posts, products (+ their meta in one query), terms of all taxonomies — before the first block renders. A token not
 * primed (render-block of one block, a sample prop) is loaded on first use, in a batch of one.
 *
 * Data contract (platform-neutral, no WordPress field names): a product is read as a Codex entry
 * (`metadata.<field>.value`, money in integer minor units) and formatted with the readPath pipes: amount = the
 * effective price (sale price when set and lower, as DataSources), currency =
 * `metadata.currency.value|settings:currency`, in stock = `metadata.<stock>.value|in-stock` (no stock value =
 * unlimited = in stock).
 * Degrades safely: no wpfasty-types, no purchasable kind, unknown taxonomy or slug → "#" for links, "" for text.
 */
final class Records
{
    /** record token kinds (Links::TOKEN) */
    public const KINDS = ['post', 'product', 'term', 'price', 'stock'];
    /** a record token with its argument(s): {post:slug}, {term:tax:slug} */
    private const SCAN = '/\{(post|product|term|price|stock):([a-z0-9_-]+)(?::([a-z0-9_-]+))?\}/';

    /**
     * @var array{product: array{kind: string, post_type: string, meta: array<string, string>,
     *   fields: array<string, array<string, mixed>>, price: string, sale: string, stock: string}|null,
     *   taxonomies: list<string>, currency: string, locale: string}|null
     */
    private static ?array $site = null;
    /** @var array<string, string|null> post slug → URL (null = not found) */
    private static array $posts = [];
    /** @var array<string, array<string, mixed>|null> product slug → product() (null = not found) */
    private static array $products = [];
    /** @var array<string, string|null> "<taxonomy>:<slug>" → URL (null = not found) */
    private static array $terms = [];

    /**
     * What the tokens read: the purchasable kind (post type, field → meta key, price / sale / stock fields), the
     * taxonomies, the settings currency and the locale. Tests and previews may set one; null = the site's.
     *
     * @param array<string, mixed>|null $site
     */
    public static function setSite(?array $site): void
    {
        self::$site = $site === null
            ? null
            : $site + ['product' => null, 'taxonomies' => [], 'currency' => '', 'locale' => 'en'];
        self::reset();
    }

    /** forgets loaded records (a new request, tests) */
    public static function reset(): void
    {
        self::$posts = [];
        self::$products = [];
        self::$terms = [];
    }

    /** @return array<string, mixed> */
    public static function site(): array
    {
        if (self::$site !== null) {
            return self::$site;
        }
        $out = ['product' => null, 'taxonomies' => [], 'currency' => '', 'locale' => 'en'];
        $site = class_exists(\WPFastyTypes\Codex\Site::class) ? DataSources::site() : null;
        if ($site === null) {
            return self::$site = $out;
        }
        foreach (DataSources::models() as $m) {
            if ($m['term'] || !is_array($m['purchasable']) || !post_type_exists((string) $m['post_type'])) {
                continue;
            }
            if ($out['product'] !== null && $m['kind'] !== 'product') {
                continue;
            }
            $kind = (string) $m['kind'];
            $p = $m['purchasable'];
            $out['product'] = [
                'kind' => $kind, 'post_type' => (string) $m['post_type'],
                'meta' => array_map('strval', (array) ($site->wp($kind)['meta'] ?? [])),
                'fields' => $site->fields($kind),
                'price' => (string) ($p['price'] ?? ''), 'sale' => (string) ($p['sale'] ?? ''),
                'stock' => (string) ($p['stock'] ?? ''),
            ];
        }
        $out['taxonomies'] = array_values(array_filter($site->allTaxonomies(), 'taxonomy_exists'));
        $cur = strtoupper((string) get_option($site->currencyOption()));
        $out['currency'] = preg_match('/^[A-Z]{3}$/', $cur) === 1 ? $cur : '';
        $out['locale'] = \WPFastyTypes\Codex\Mapper::locale();
        return self::$site = $out;
    }

    /**
     * Loads every record token found in the strings of $props (nested), one query per type.
     *
     * @param array<mixed> $props
     */
    public static function prime(array $props): void
    {
        $want = ['post' => [], 'product' => [], 'term' => []];
        self::scan($props, $want);
        $want['post'] = array_diff(array_keys($want['post']), array_keys(self::$posts));
        $want['product'] = array_diff(array_keys($want['product']), array_keys(self::$products));
        $want['term'] = array_diff(array_keys($want['term']), array_keys(self::$terms));
        if ($want['post'] !== []) {
            self::loadPosts(array_values($want['post']));
        }
        if ($want['product'] !== []) {
            self::loadProducts(array_values($want['product']));
        }
        if ($want['term'] !== []) {
            self::loadTerms(array_values($want['term']));
        }
    }

    /**
     * @param array<mixed> $props
     * @param array<string, array<string, true>> $want
     */
    private static function scan(array $props, array &$want): void
    {
        foreach ($props as $v) {
            if (is_array($v)) {
                self::scan($v, $want);
                continue;
            }
            if (!is_string($v) || !str_contains($v, '{') || preg_match_all(self::SCAN, $v, $all, PREG_SET_ORDER) < 1) {
                continue;
            }
            foreach ($all as $m) {
                if ($m[1] === 'term') {
                    if (($m[3] ?? '') !== '') {
                        $want['term'][$m[2] . ':' . $m[3]] = true;
                    }
                } elseif (($m[3] ?? '') === '') {
                    $want[$m[1] === 'post' ? 'post' : 'product'][$m[2]] = true;
                }
            }
        }
    }

    /** What a record token gives: a URL ("#" when the record is not there) or text ("" when not there). */
    public static function value(string $kind, string $arg, string $arg2 = ''): string
    {
        if ($kind === 'term') {
            if ($arg2 === '') {
                return '#';
            }
            $key = $arg . ':' . $arg2;
            if (!array_key_exists($key, self::$terms)) {
                self::loadTerms([$key]);
            }
            return self::$terms[$key] ?? '#';
        }
        if ($arg2 !== '') {
            return in_array($kind, ['post', 'product'], true) ? '#' : '';
        }
        if ($kind === 'post') {
            if (!array_key_exists($arg, self::$posts)) {
                self::loadPosts([$arg]);
            }
            return self::$posts[$arg] ?? '#';
        }
        if (!array_key_exists($arg, self::$products)) {
            self::loadProducts([$arg]);
        }
        $p = self::$products[$arg] ?? null;
        return match ($kind) {
            'product' => $p !== null ? (string) $p['url'] : '#',
            'price' => $p !== null ? (string) $p['price'] : '',
            'stock' => $p !== null ? (string) $p['stock'] : '',
            default => '',
        };
    }

    /** @param list<string> $slugs */
    private static function loadPosts(array $slugs): void
    {
        foreach ($slugs as $s) {
            self::$posts[$s] = null;
        }
        $found = get_posts([
            'post_type' => 'post', 'post_status' => 'publish', 'post_name__in' => $slugs,
            'numberposts' => count($slugs), 'no_found_rows' => true, 'ignore_sticky_posts' => true,
            'update_post_meta_cache' => false, 'update_post_term_cache' => false,
        ]);
        foreach (is_array($found) ? $found : [] as $p) {
            if ($p instanceof \WP_Post && array_key_exists((string) $p->post_name, self::$posts)) {
                $url = get_permalink($p);
                self::$posts[(string) $p->post_name] = is_string($url) && $url !== '' ? $url : null;
            }
        }
    }

    /** @param list<string> $slugs */
    private static function loadProducts(array $slugs): void
    {
        foreach ($slugs as $s) {
            self::$products[$s] = null;
        }
        $kind = self::site()['product'];
        if ($kind === null) {
            return;
        }
        // one query for the posts, one for their meta (price, stock…); no term cache
        $found = get_posts([
            'post_type' => $kind['post_type'], 'post_status' => 'publish', 'post_name__in' => $slugs,
            'numberposts' => count($slugs), 'no_found_rows' => true,
            'update_post_meta_cache' => true, 'update_post_term_cache' => false,
        ]);
        foreach (is_array($found) ? $found : [] as $p) {
            if ($p instanceof \WP_Post && array_key_exists((string) $p->post_name, self::$products)) {
                $product = self::product($p);
                if ((string) $p->post_password !== '') {
                    // a password-protected product: its link stays, its fields are behind the password
                    $product = ['amount' => null, 'price' => '', 'in_stock' => false, 'stock' => ''] + $product;
                }
                self::$products[(string) $p->post_name] = $product;
            }
        }
    }

    /**
     * The product as the tokens and the picker read it: url, label, amount (minor units, null = no price), currency,
     * price (formatted, "" without a price), in_stock, stock (label).
     *
     * @return array{url: string, label: string, amount: int|null, currency: string, price: string, in_stock: bool,
     *   stock: string}
     */
    public static function product(\WP_Post $p): array
    {
        $site = self::site();
        $kind = $site['product'];
        $e = self::entry($p, $kind ?? ['meta' => [], 'fields' => []]);
        $md = $e['metadata'];
        $price = $kind !== null ? ($md[$kind['price']]['value'] ?? null) : null;
        $sale = $kind !== null && $kind['sale'] !== '' ? ($md[$kind['sale']]['value'] ?? null) : null;
        $amount = is_int($price) ? $price : null;
        if ($amount !== null && is_int($sale) && $sale > 0 && $sale < $amount) {
            $amount = $sale;
        }
        $opts = [
            'settings' => $site['currency'] !== '' ? ['currency' => $site['currency']] : [],
            'locale' => $site['locale'],
        ];
        $currency = ReadPath::read($e, 'metadata.currency.value|settings:currency', $opts);
        $currency = is_string($currency) ? $currency : '';
        $inStock = (bool) ReadPath::read($e, 'metadata.' . ($kind['stock'] ?? '') . '.value|in-stock', $opts);
        $url = get_permalink($p);
        $title = trim(wp_strip_all_tags((string) $p->post_title));
        return [
            'url' => is_string($url) && $url !== '' ? $url : '#',
            'label' => $title !== '' ? $title : (string) $p->post_name,
            'amount' => $amount,
            'currency' => $currency,
            'price' => $amount !== null && $currency !== ''
                ? (string) Format::currency($amount, $currency, $site['locale'])
                : '',
            'in_stock' => $inStock,
            'stock' => $inStock ? __('In stock', 'wpfasty-builder') : __('Out of stock', 'wpfasty-builder'),
        ];
    }

    /**
     * The Codex entry part the tokens read: id, slug and typed metadata (field → {value}) from the post meta of the
     * delivery mapping (meta cache primed by the batch query).
     *
     * @param array<string, mixed> $kind
     * @return array{id: string, slug: string, metadata: array<string, array{value: mixed}>}
     */
    private static function entry(\WP_Post $p, array $kind): array
    {
        $md = [];
        foreach ((array) $kind['meta'] as $field => $key) {
            $def = (array) ($kind['fields'][$field] ?? ['type' => 'string']);
            $raw = get_post_meta($p->ID, (string) $key, true);
            $value = class_exists(\WPFastyTypes\Codex\Mapper::class)
                ? \WPFastyTypes\Codex\Mapper::typed($def, $raw)
                : (is_numeric($raw) ? (int) round((float) $raw) : ($raw === '' || $raw === null ? null : $raw));
            if ($value !== null) {
                $md[(string) $field] = ['value' => $value];
            }
        }
        return [
            'id' => ($kind['kind'] ?? 'product') . '-' . $p->ID,
            'slug' => (string) $p->post_name,
            'metadata' => $md,
        ];
    }

    /** @param list<string> $keys "<taxonomy>:<slug>" */
    private static function loadTerms(array $keys): void
    {
        $taxes = [];
        $slugs = [];
        foreach ($keys as $k) {
            self::$terms[$k] = null;
            [$tax, $slug] = explode(':', $k, 2);
            if (in_array($tax, self::site()['taxonomies'], true)) {
                $taxes[$tax] = true;
                $slugs[$slug] = true;
            }
        }
        if ($taxes === []) {
            return;
        }
        // one query for every taxonomy and slug of the page
        $found = get_terms([
            'taxonomy' => array_keys($taxes), 'slug' => array_keys($slugs), 'hide_empty' => false,
            'update_term_meta_cache' => false,
        ]);
        foreach (is_array($found) ? $found : [] as $t) {
            $key = $t instanceof \WP_Term ? $t->taxonomy . ':' . $t->slug : '';
            if ($key !== '' && array_key_exists($key, self::$terms)) {
                $url = get_term_link($t);
                self::$terms[$key] = is_string($url) && $url !== '' ? $url : null;
            }
        }
    }

    /**
     * The picker lists (GET /links): published posts, products with price and stock (null when the site has no
     * purchasable kind) and the terms of the manifest taxonomies except `category` ({category:<slug>} lists those).
     * One query for posts, two for products (posts + meta), one for terms, whatever the number of items.
     *
     * @return array{
     *   posts: list<array{token: string, label: string, slug: string, url: string}>,
     *   products: list<array{token: string, label: string, slug: string, url: string, amount: int|null,
     *     currency: string, price: string, in_stock: bool, stock: string}>|null,
     *   terms: list<array{token: string, label: string, slug: string, taxonomy: string, kind: string, url: string}>
     * }
     */
    public static function index(): array
    {
        $posts = [];
        $found = get_posts([
            'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => Links::MAX, 'orderby' => 'date',
            'order' => 'DESC', 'no_found_rows' => true, 'ignore_sticky_posts' => true,
            'update_post_meta_cache' => false, 'update_post_term_cache' => false,
        ]);
        foreach (is_array($found) ? $found : [] as $p) {
            if (!$p instanceof \WP_Post || preg_match(Links::SLUG, (string) $p->post_name) !== 1) {
                continue;
            }
            $url = get_permalink($p);
            $title = trim(wp_strip_all_tags((string) $p->post_title));
            self::$posts[(string) $p->post_name] = is_string($url) && $url !== '' ? $url : null;
            $posts[] = [
                'token' => '{post:' . $p->post_name . '}', 'label' => $title !== '' ? $title : (string) $p->post_name,
                'slug' => (string) $p->post_name, 'url' => is_string($url) && $url !== '' ? $url : '#',
            ];
        }
        $site = self::site();
        $products = null;
        if ($site['product'] !== null) {
            $products = [];
            $found = get_posts([
                'post_type' => $site['product']['post_type'], 'post_status' => 'publish', 'numberposts' => Links::MAX,
                'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true,
                'update_post_meta_cache' => true, 'update_post_term_cache' => false,
            ]);
            foreach (is_array($found) ? $found : [] as $p) {
                if (!$p instanceof \WP_Post || preg_match(Links::SLUG, (string) $p->post_name) !== 1) {
                    continue;
                }
                $slug = (string) $p->post_name;
                $r = self::product($p);
                self::$products[$slug] = $r;
                $products[] = ['token' => '{product:' . $slug . '}', 'label' => $r['label'], 'slug' => $slug] + $r;
            }
        }
        $terms = [];
        $taxes = array_values(array_diff($site['taxonomies'], ['category']));
        $found = $taxes !== [] ? get_terms([
            'taxonomy' => $taxes, 'hide_empty' => false, 'number' => Links::MAX, 'orderby' => 'name',
            'update_term_meta_cache' => false,
        ]) : [];
        foreach (is_array($found) ? $found : [] as $t) {
            if (!$t instanceof \WP_Term || preg_match(Links::SLUG, (string) $t->slug) !== 1) {
                continue;
            }
            $url = get_term_link($t);
            $tax = get_taxonomy($t->taxonomy);
            self::$terms[$t->taxonomy . ':' . $t->slug] = is_string($url) && $url !== '' ? $url : null;
            $terms[] = [
                'token' => '{term:' . $t->taxonomy . ':' . $t->slug . '}', 'label' => (string) $t->name,
                'slug' => (string) $t->slug, 'taxonomy' => (string) $t->taxonomy,
                'kind' => is_object($tax) && isset($tax->labels->singular_name)
                    ? html_entity_decode((string) $tax->labels->singular_name, ENT_QUOTES, 'UTF-8')
                    : (string) $t->taxonomy,
                'url' => is_string($url) && $url !== '' ? $url : '#',
            ];
        }
        return ['posts' => $posts, 'products' => $products, 'terms' => $terms];
    }
}
