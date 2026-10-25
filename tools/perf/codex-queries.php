<?php
/**
 * N+1 guard of the Codex REST list: queries of GET /wpfasty/v1/codex/entries/<kind>?per_page=N must not grow with N.
 *   wp eval-file tools/perf/codex-queries.php <kind> [small] [large]
 * Exit 1 when the large page needs more queries than the small one (or a page could not be filled).
 *
 * @package WPFasty
 */

if (!defined('ABSPATH')) {
    exit;
}

$wpfasty_cq_kind = (string) ($args[0] ?? 'post');
$wpfasty_cq_sizes = [(int) ($args[1] ?? 2), (int) ($args[2] ?? 6)];
$wpfasty_cq = [];
foreach ($wpfasty_cq_sizes as $wpfasty_cq_n) {
    global $wpdb;
    wp_cache_flush();
    $wpfasty_cq_req = new WP_REST_Request('GET', '/wpfasty/v1/codex/entries/' . $wpfasty_cq_kind);
    $wpfasty_cq_req->set_query_params(['per_page' => (string) $wpfasty_cq_n]);
    $wpfasty_cq_before = $wpdb->num_queries;
    $wpfasty_cq_res = rest_do_request($wpfasty_cq_req);
    $wpfasty_cq[] = [$wpdb->num_queries - $wpfasty_cq_before, $wpfasty_cq_res->get_status(), $wpfasty_cq_res];
}
$wpfasty_cq_count = static function (WP_REST_Response $r): int {
    $data = $r->get_data();
    $raw = is_array($data) ? (string) reset($data) : '';
    $doc = json_decode($raw, true);
    return is_array($doc['data'] ?? null) ? count($doc['data']) : -1;
};
[$wpfasty_cq_small, $wpfasty_cq_large] = $wpfasty_cq;
$wpfasty_cq_items = [$wpfasty_cq_count($wpfasty_cq_small[2]), $wpfasty_cq_count($wpfasty_cq_large[2])];
WP_CLI::log(sprintf(
    'codex %s: %d entries → %d queries; %d entries → %d queries',
    $wpfasty_cq_kind,
    $wpfasty_cq_items[0],
    $wpfasty_cq_small[0],
    $wpfasty_cq_items[1],
    $wpfasty_cq_large[0],
));
if ($wpfasty_cq_small[1] !== 200 || $wpfasty_cq_large[1] !== 200 || $wpfasty_cq_items[1] <= $wpfasty_cq_items[0]) {
    WP_CLI::error('could not compare (status or too few entries)');
}
if ($wpfasty_cq_large[0] > $wpfasty_cq_small[0]) {
    WP_CLI::error('N+1: the larger page needs more queries');
}
WP_CLI::success('No N+1 detected');
