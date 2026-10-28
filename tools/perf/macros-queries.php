<?php
/**
 * N+1 guard of the builder record tokens on a page: {post:<slug>}, {product:<slug>}, {term:<taxonomy>:<slug>},
 * {price:<product>}, {stock:<product>}. A page of blocks naming [small] then [large] records of each type is rendered
 * as Front does it (Links::prime over every block's props, then Links::walk per block); the query count must not grow.
 * Missing posts / terms are added for the run and removed after it (products: the site's own, seed them first).
 *   wp eval-file tools/perf/queries.php macros [small] [large]
 * Exit 1 when the larger page needs more queries.
 *
 * @package WPFasty
 */

if (!defined('ABSPATH')) {
    exit;
}

$wpfasty_mq_small = max(1, (int) ($args[1] ?? 2));
$wpfasty_mq_large = max($wpfasty_mq_small + 1, (int) ($args[2] ?? 6));
$wpfasty_mq_site = WPFastyBuilder\Records::site();
$wpfasty_mq_product = $wpfasty_mq_site['product'];
if ($wpfasty_mq_product === null) {
    WP_CLI::error('the active site has no purchasable post type (activate latte-shop with wpfasty-types)');
}
$wpfasty_mq_tax = in_array('product_cat', $wpfasty_mq_site['taxonomies'], true)
    ? 'product_cat'
    : (string) ($wpfasty_mq_site['taxonomies'][0] ?? '');
if ($wpfasty_mq_tax === '') {
    WP_CLI::error('the active site has no taxonomy in its Codex manifest');
}

// records to name: the site's own, topped up with temporary posts and terms
$wpfasty_mq_added = ['posts' => [], 'terms' => []];
$wpfasty_mq_slugs = static fn (string $type): array => get_posts([
    'post_type' => $type, 'post_status' => 'publish', 'numberposts' => $wpfasty_mq_large, 'fields' => 'ids',
    'no_found_rows' => true,
]);
for ($i = count($wpfasty_mq_slugs('post')); $i < $wpfasty_mq_large; $i++) {
    $wpfasty_mq_added['posts'][] = wp_insert_post([
        'post_type' => 'post', 'post_status' => 'publish', 'post_title' => "Perf macro post $i",
        'post_name' => "perf-macro-post-$i",
    ]);
}
$wpfasty_mq_terms = get_terms(['taxonomy' => $wpfasty_mq_tax, 'hide_empty' => false, 'fields' => 'slugs']);
for ($i = count(is_array($wpfasty_mq_terms) ? $wpfasty_mq_terms : []); $i < $wpfasty_mq_large; $i++) {
    $wpfasty_mq_t = wp_insert_term("Perf macro term $i", $wpfasty_mq_tax, ['slug' => "perf-macro-term-$i"]);
    if (is_array($wpfasty_mq_t)) {
        $wpfasty_mq_added['terms'][] = (int) $wpfasty_mq_t['term_id'];
    }
}
$wpfasty_mq_names = static function (string $type, int $n): array {
    $out = [];
    foreach (get_posts(['post_type' => $type, 'post_status' => 'publish', 'numberposts' => $n, 'no_found_rows' => true]) as $p) {
        $out[] = $p->post_name;
    }
    return $out;
};
$wpfasty_mq_products = $wpfasty_mq_names($wpfasty_mq_product['post_type'], $wpfasty_mq_large);
if (count($wpfasty_mq_products) < $wpfasty_mq_large) {
    WP_CLI::error(sprintf('need %d products, the site has %d: seed demo content first', $wpfasty_mq_large, count($wpfasty_mq_products)));
}
$wpfasty_mq_posts = $wpfasty_mq_names('post', $wpfasty_mq_large);
$wpfasty_mq_termslugs = array_slice((array) get_terms(['taxonomy' => $wpfasty_mq_tax, 'hide_empty' => false, 'fields' => 'slugs']), 0, $wpfasty_mq_large);

/** blocks of a page naming $n records of each type (one block per record, like a product grid built by hand) */
$wpfasty_mq_page = static function (int $n) use ($wpfasty_mq_posts, $wpfasty_mq_products, $wpfasty_mq_termslugs, $wpfasty_mq_tax): array {
    $blocks = [];
    for ($i = 0; $i < $n; $i++) {
        $p = $wpfasty_mq_products[$i];
        $blocks[] = [
            'title' => "{post:{$wpfasty_mq_posts[$i]}}",
            'items' => [['href' => "{product:$p}", 'price' => "{price:$p}", 'stock' => "Now: {stock:$p}"]],
            'href' => "{term:$wpfasty_mq_tax:{$wpfasty_mq_termslugs[$i]}}#top",
        ];
    }
    return $blocks;
};

/** @return array{0: int, 1: int, 2: string} queries, unresolved tokens, a sample */
$wpfasty_mq_render = static function (array $blocks): array {
    global $wpdb;
    wp_cache_flush();
    WPFastyBuilder\Records::reset();
    WPFastyBuilder\Links::setBlock(null);
    $before = $wpdb->num_queries;
    WPFastyBuilder\Links::prime($blocks);
    $out = [];
    foreach ($blocks as $props) {
        $out[] = WPFastyBuilder\Links::walk($props);
    }
    $n = $wpdb->num_queries - $before;
    $flat = (string) wp_json_encode($out);
    return [$n, substr_count($flat, '"#') + preg_match_all('/\{(post|product|term|price|stock):/', $flat), $flat];
};

$wpfasty_mq_render($wpfasty_mq_page(1)); // warm-up: options, rewrite rules, the Codex manifest
[$wpfasty_mq_q1, $wpfasty_mq_bad1] = $wpfasty_mq_render($wpfasty_mq_page($wpfasty_mq_small));
[$wpfasty_mq_q2, $wpfasty_mq_bad2, $wpfasty_mq_sample] = $wpfasty_mq_render($wpfasty_mq_page($wpfasty_mq_large));

foreach ($wpfasty_mq_added['posts'] as $id) {
    wp_delete_post((int) $id, true);
}
foreach ($wpfasty_mq_added['terms'] as $id) {
    wp_delete_term($id, $wpfasty_mq_tax);
}

WP_CLI::log(sprintf(
    'macros: %d blocks × 5 record tokens → %d queries; %d blocks × 5 → %d queries',
    $wpfasty_mq_small,
    $wpfasty_mq_q1,
    $wpfasty_mq_large,
    $wpfasty_mq_q2
));
if ($wpfasty_mq_bad1 + $wpfasty_mq_bad2 > 0) {
    WP_CLI::error('unresolved record tokens: ' . substr($wpfasty_mq_sample, 0, 400));
}
if ($wpfasty_mq_q2 > $wpfasty_mq_q1) {
    WP_CLI::error('N+1: the query count grows with the number of record tokens');
}
WP_CLI::success('No N+1 detected');
