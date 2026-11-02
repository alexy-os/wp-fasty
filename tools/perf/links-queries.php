<?php

/**
 * N+1 guard of the builder's link picker: queries of GET /wpfasty-builder/v1/links must not grow with the number of
 * pages and categories. Measures the site as is, adds [extra] published pages and categories, measures again and
 * removes them.
 *   wp eval-file tools/perf/links-queries.php [extra]
 * Exit 1 when the larger site needs more queries.
 */
if (!defined('ABSPATH')) {
    exit;
}

$wpfastyLqExtra = max(1, (int) ($args[0] ?? 6));
$wpfastyLqAdmin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
wp_set_current_user((int) ($wpfastyLqAdmin[0] ?? 1));

$wpfastyLqMeasure = static function (): array {
    global $wpdb;
    wp_cache_flush();
    $before = $wpdb->num_queries;
    $res = rest_do_request(new WP_REST_Request('GET', '/wpfasty-builder/v1/links'));
    $n = $wpdb->num_queries - $before;
    $data = $res->get_data();
    if (200 !== $res->get_status() || !is_array($data)) {
        WP_CLI::error('GET /wpfasty-builder/v1/links: HTTP '.$res->get_status());
    }

    return [$n, count($data['pages']), count($data['sections'])];
};

$wpfastyLqMeasure(); // warm-up: REST server, rewrite rules and other once-per-request loads
$wpfastyLqSmall = $wpfastyLqMeasure();
$wpfastyLqPosts = [];
$wpfastyLqTerms = [];
for ($wpfastyLqI = 1; $wpfastyLqI <= $wpfastyLqExtra; ++$wpfastyLqI) {
    $wpfastyLqPosts[] = wp_insert_post([
        'post_type' => 'page', 'post_status' => 'publish', 'post_title' => "Perf page $wpfastyLqI",
        'post_name' => "perf-page-$wpfastyLqI",
    ]);
    $wpfastyLqT = wp_insert_term("Perf category $wpfastyLqI", 'category', ['slug' => "perf-cat-$wpfastyLqI"]);
    if (is_array($wpfastyLqT)) {
        $wpfastyLqTerms[] = (int) $wpfastyLqT['term_id'];
    }
}
$wpfastyLqLarge = $wpfastyLqMeasure();
foreach ($wpfastyLqPosts as $wpfastyLqId) {
    wp_delete_post((int) $wpfastyLqId, true);
}
foreach ($wpfastyLqTerms as $wpfastyLqId) {
    wp_delete_term($wpfastyLqId, 'category');
}

WP_CLI::log(sprintf(
    'links: %d pages, %d sections → %d queries; %d pages, %d sections → %d queries',
    $wpfastyLqSmall[1],
    $wpfastyLqSmall[2],
    $wpfastyLqSmall[0],
    $wpfastyLqLarge[1],
    $wpfastyLqLarge[2],
    $wpfastyLqLarge[0],
));
if ($wpfastyLqLarge[1] <= $wpfastyLqSmall[1] || $wpfastyLqLarge[2] <= $wpfastyLqSmall[2]) {
    WP_CLI::error('the added pages / categories are not in the response');
}
if ($wpfastyLqLarge[0] > $wpfastyLqSmall[0]) {
    WP_CLI::error('N+1: the query count grows with the number of pages / categories');
}
WP_CLI::success('No N+1 detected');
