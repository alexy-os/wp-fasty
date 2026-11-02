<?php

/**
 * N+1 guard of the builder record tokens on a page: {post:<slug>}, {product:<slug>}, {term:<taxonomy>:<slug>},
 * {price:<product>}, {stock:<product>}. A page of blocks naming [small] then [large] records of each type is rendered
 * as Front does it (Links::prime over every block's props, then Links::walk per block); the query count must not grow.
 * Missing posts / terms are added for the run and removed after it (products: the site's own, seed them first).
 *   wp eval-file tools/perf/queries.php macros [small] [large]
 * Exit 1 when the larger page needs more queries.
 */
if (!defined('ABSPATH')) {
    exit;
}

$wpfastyMqSmall = max(1, (int) ($args[1] ?? 2));
$wpfastyMqLarge = max($wpfastyMqSmall + 1, (int) ($args[2] ?? 6));
$wpfastyMqSite = WPFastyBuilder\Records::site();
$wpfastyMqProduct = $wpfastyMqSite['product'];
if (null === $wpfastyMqProduct) {
    WP_CLI::error('the active site has no purchasable post type (activate latte-shop with wpfasty-types)');
}
$wpfastyMqTax = in_array('product_cat', $wpfastyMqSite['taxonomies'], true)
    ? 'product_cat'
    : (string) ($wpfastyMqSite['taxonomies'][0] ?? '');
if ('' === $wpfastyMqTax) {
    WP_CLI::error('the active site has no taxonomy in its Codex manifest');
}

// records to name: the site's own, topped up with temporary posts and terms
$wpfastyMqAdded = ['posts' => [], 'terms' => []];
$wpfastyMqSlugs = static fn (string $type): array => get_posts([
    'post_type' => $type, 'post_status' => 'publish', 'numberposts' => $wpfastyMqLarge, 'fields' => 'ids',
    'no_found_rows' => true,
]);
for ($i = count($wpfastyMqSlugs('post')); $i < $wpfastyMqLarge; ++$i) {
    $wpfastyMqAdded['posts'][] = wp_insert_post([
        'post_type' => 'post', 'post_status' => 'publish', 'post_title' => "Perf macro post $i",
        'post_name' => "perf-macro-post-$i",
    ]);
}
$wpfastyMqTerms = get_terms(['taxonomy' => $wpfastyMqTax, 'hide_empty' => false, 'fields' => 'slugs']);
for ($i = count(is_array($wpfastyMqTerms) ? $wpfastyMqTerms : []); $i < $wpfastyMqLarge; ++$i) {
    $wpfastyMqT = wp_insert_term("Perf macro term $i", $wpfastyMqTax, ['slug' => "perf-macro-term-$i"]);
    if (is_array($wpfastyMqT)) {
        $wpfastyMqAdded['terms'][] = (int) $wpfastyMqT['term_id'];
    }
}
$wpfastyMqNames = static function (string $type, int $n): array {
    $out = [];
    foreach (get_posts(['post_type' => $type, 'post_status' => 'publish', 'numberposts' => $n, 'no_found_rows' => true]) as $p) {
        $out[] = $p->post_name;
    }

    return $out;
};
$wpfastyMqProducts = $wpfastyMqNames($wpfastyMqProduct['post_type'], $wpfastyMqLarge);
if (count($wpfastyMqProducts) < $wpfastyMqLarge) {
    WP_CLI::error(sprintf('need %d products, the site has %d: seed demo content first', $wpfastyMqLarge, count($wpfastyMqProducts)));
}
$wpfastyMqPosts = $wpfastyMqNames('post', $wpfastyMqLarge);
$wpfastyMqTermslugs = array_slice((array) get_terms(['taxonomy' => $wpfastyMqTax, 'hide_empty' => false, 'fields' => 'slugs']), 0, $wpfastyMqLarge);

/** blocks of a page naming $n records of each type (one block per record, like a product grid built by hand) */
$wpfastyMqPage = static function (int $n) use ($wpfastyMqPosts, $wpfastyMqProducts, $wpfastyMqTermslugs, $wpfastyMqTax): array {
    $blocks = [];
    for ($i = 0; $i < $n; ++$i) {
        $p = $wpfastyMqProducts[$i];
        $blocks[] = [
            'title' => "{post:{$wpfastyMqPosts[$i]}}",
            'items' => [['href' => "{product:$p}", 'price' => "{price:$p}", 'stock' => "Now: {stock:$p}"]],
            'href' => "{term:$wpfastyMqTax:{$wpfastyMqTermslugs[$i]}}#top",
        ];
    }

    return $blocks;
};

/** @return array{0: int, 1: int, 2: string} queries, unresolved tokens, a sample */
$wpfastyMqRender = static function (array $blocks): array {
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

$wpfastyMqRender($wpfastyMqPage(1)); // warm-up: options, rewrite rules, the Codex manifest
[$wpfastyMqQ1, $wpfastyMqBad1] = $wpfastyMqRender($wpfastyMqPage($wpfastyMqSmall));
[$wpfastyMqQ2, $wpfastyMqBad2, $wpfastyMqSample] = $wpfastyMqRender($wpfastyMqPage($wpfastyMqLarge));

foreach ($wpfastyMqAdded['posts'] as $id) {
    wp_delete_post((int) $id, true);
}
foreach ($wpfastyMqAdded['terms'] as $id) {
    wp_delete_term($id, $wpfastyMqTax);
}

WP_CLI::log(sprintf(
    'macros: %d blocks × 5 record tokens → %d queries; %d blocks × 5 → %d queries',
    $wpfastyMqSmall,
    $wpfastyMqQ1,
    $wpfastyMqLarge,
    $wpfastyMqQ2
));
if ($wpfastyMqBad1 + $wpfastyMqBad2 > 0) {
    WP_CLI::error('unresolved record tokens: '.substr($wpfastyMqSample, 0, 400));
}
if ($wpfastyMqQ2 > $wpfastyMqQ1) {
    WP_CLI::error('N+1: the query count grows with the number of record tokens');
}
WP_CLI::success('No N+1 detected');
