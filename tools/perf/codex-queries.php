<?php

/**
 * N+1 guard of the Codex REST list: queries of GET /wpfasty/v1/codex/entries/<kind>?per_page=N must not grow with N.
 *   wp eval-file tools/perf/codex-queries.php <kind> [small] [large]
 * Exit 1 when the large page needs more queries than the small one (or a page could not be filled).
 */
if (!defined('ABSPATH')) {
    exit;
}

$wpfastyCqKind = (string) ($args[0] ?? 'post');
$wpfastyCqSizes = [(int) ($args[1] ?? 2), (int) ($args[2] ?? 6)];
$wpfastyCq = [];
foreach ($wpfastyCqSizes as $wpfastyCqN) {
    global $wpdb;
    wp_cache_flush();
    $wpfastyCqReq = new WP_REST_Request('GET', '/wpfasty/v1/codex/entries/'.$wpfastyCqKind);
    $wpfastyCqReq->set_query_params(['per_page' => (string) $wpfastyCqN]);
    $wpfastyCqBefore = $wpdb->num_queries;
    $wpfastyCqRes = rest_do_request($wpfastyCqReq);
    $wpfastyCq[] = [$wpdb->num_queries - $wpfastyCqBefore, $wpfastyCqRes->get_status(), $wpfastyCqRes];
}
$wpfastyCqCount = static function (WP_REST_Response $r): int {
    $data = $r->get_data();
    $raw = is_array($data) ? (string) reset($data) : '';
    $doc = json_decode($raw, true);

    return is_array($doc['data'] ?? null) ? count($doc['data']) : -1;
};
[$wpfastyCqSmall, $wpfastyCqLarge] = $wpfastyCq;
$wpfastyCqItems = [$wpfastyCqCount($wpfastyCqSmall[2]), $wpfastyCqCount($wpfastyCqLarge[2])];
WP_CLI::log(sprintf(
    'codex %s: %d entries → %d queries; %d entries → %d queries',
    $wpfastyCqKind,
    $wpfastyCqItems[0],
    $wpfastyCqSmall[0],
    $wpfastyCqItems[1],
    $wpfastyCqLarge[0],
));
if (200 !== $wpfastyCqSmall[1] || 200 !== $wpfastyCqLarge[1] || $wpfastyCqItems[1] <= $wpfastyCqItems[0]) {
    WP_CLI::error('could not compare (status or too few entries)');
}
if ($wpfastyCqLarge[0] > $wpfastyCqSmall[0]) {
    WP_CLI::error('N+1: the larger page needs more queries');
}
WP_CLI::success('No N+1 detected');
