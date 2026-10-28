<?php
/**
 * N+1 guard of the builder's link picker: queries of GET /wpfasty-builder/v1/links must not grow with the number of
 * pages and categories. Measures the site as is, adds [extra] published pages and categories, measures again and
 * removes them.
 *   wp eval-file tools/perf/links-queries.php [extra]
 * Exit 1 when the larger site needs more queries.
 *
 * @package WPFasty
 */

if (!defined('ABSPATH')) {
    exit;
}

$wpfasty_lq_extra = max(1, (int) ($args[0] ?? 6));
$wpfasty_lq_admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
wp_set_current_user((int) ($wpfasty_lq_admin[0] ?? 1));

$wpfasty_lq_measure = static function (): array {
    global $wpdb;
    wp_cache_flush();
    $before = $wpdb->num_queries;
    $res = rest_do_request(new WP_REST_Request('GET', '/wpfasty-builder/v1/links'));
    $n = $wpdb->num_queries - $before;
    $data = $res->get_data();
    if ($res->get_status() !== 200 || !is_array($data)) {
        WP_CLI::error('GET /wpfasty-builder/v1/links: HTTP ' . $res->get_status());
    }
    return [$n, count($data['pages']), count($data['sections'])];
};

$wpfasty_lq_measure(); // warm-up: REST server, rewrite rules and other once-per-request loads
$wpfasty_lq_small = $wpfasty_lq_measure();
$wpfasty_lq_posts = [];
$wpfasty_lq_terms = [];
for ($wpfasty_lq_i = 1; $wpfasty_lq_i <= $wpfasty_lq_extra; $wpfasty_lq_i++) {
    $wpfasty_lq_posts[] = wp_insert_post([
        'post_type' => 'page', 'post_status' => 'publish', 'post_title' => "Perf page $wpfasty_lq_i",
        'post_name' => "perf-page-$wpfasty_lq_i",
    ]);
    $wpfasty_lq_t = wp_insert_term("Perf category $wpfasty_lq_i", 'category', ['slug' => "perf-cat-$wpfasty_lq_i"]);
    if (is_array($wpfasty_lq_t)) {
        $wpfasty_lq_terms[] = (int) $wpfasty_lq_t['term_id'];
    }
}
$wpfasty_lq_large = $wpfasty_lq_measure();
foreach ($wpfasty_lq_posts as $wpfasty_lq_id) {
    wp_delete_post((int) $wpfasty_lq_id, true);
}
foreach ($wpfasty_lq_terms as $wpfasty_lq_id) {
    wp_delete_term($wpfasty_lq_id, 'category');
}

WP_CLI::log(sprintf(
    'links: %d pages, %d sections → %d queries; %d pages, %d sections → %d queries',
    $wpfasty_lq_small[1],
    $wpfasty_lq_small[2],
    $wpfasty_lq_small[0],
    $wpfasty_lq_large[1],
    $wpfasty_lq_large[2],
    $wpfasty_lq_large[0],
));
if ($wpfasty_lq_large[1] <= $wpfasty_lq_small[1] || $wpfasty_lq_large[2] <= $wpfasty_lq_small[2]) {
    WP_CLI::error('the added pages / categories are not in the response');
}
if ($wpfasty_lq_large[0] > $wpfasty_lq_small[0]) {
    WP_CLI::error('N+1: the query count grows with the number of pages / categories');
}
WP_CLI::success('No N+1 detected');
