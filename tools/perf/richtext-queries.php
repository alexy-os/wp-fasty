<?php
/**
 * N+1 guard of the builder richtext block (the record of the current route, DataSources::current): a page with
 * [small] then [large] richtext blocks (all six variants in turn) gets its bound data as Front does it
 * (Front::bound: the queried page as a Codex entry, the_content through its filters, cover, author, terms); the query
 * count must not grow with the number of blocks. Two temporary pages (one per run, so no memo carries over) are
 * added and removed.
 *   wp eval-file tools/perf/queries.php richtext [small] [large]
 * Exit 1 when the larger page needs more queries, or when a block misses the body.
 *
 * @package WPFasty
 */

if (!defined('ABSPATH')) {
    exit;
}

$wpfasty_rq_small = max(1, (int) ($args[1] ?? 2));
$wpfasty_rq_large = max($wpfasty_rq_small + 1, (int) ($args[2] ?? 6));
$wpfasty_rq_catalog = WPFastyBuilder\Sources::catalog('ui8kit');
if ($wpfasty_rq_catalog === null || $wpfasty_rq_catalog->block('richtext') === null) {
    WP_CLI::error('the ui8kit source has no richtext block');
}
$wpfasty_rq_thumb = (int) (get_posts([
    'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids',
])[0] ?? 0);
$wpfasty_rq_body = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Perf</h2>\n<!-- /wp:heading -->\n"
    . "<!-- wp:paragraph -->\n<p>Body with a [caption width=\"10\"]caption[/caption].</p>\n<!-- /wp:paragraph -->";
$wpfasty_rq_pages = [];
foreach ([1, 2, 3] as $i) {
    $wpfasty_rq_pages[] = wp_insert_post([
        'post_type' => 'page', 'post_status' => 'publish', 'post_title' => "Perf richtext $i",
        'post_name' => "perf-richtext-$i", 'post_content' => $wpfasty_rq_body, 'post_author' => 1,
    ]);
    if ($wpfasty_rq_thumb > 0) {
        set_post_thumbnail((int) end($wpfasty_rq_pages), $wpfasty_rq_thumb);
    }
}

/** @return array{0: int, 1: int} queries, blocks without the body */
$wpfasty_rq_render = static function (int $pageId, int $n) use ($wpfasty_rq_catalog): array {
    global $wpdb, $wp_query, $wp_the_query, $post;
    $variants = array_column((array) $wpfasty_rq_catalog->block('richtext')['variants'], 'id');
    $blocks = [];
    for ($i = 0; $i < $n; $i++) {
        $blocks[] = ['id' => "rt$i", 'block' => 'richtext', 'variant' => $variants[$i % count($variants)]];
    }
    [$entry] = WPFastyBuilder\Layout::normalize(['blocks' => $blocks], 'ui8kit', $wpfasty_rq_catalog, [], [
        'id' => 'perf', 'slug' => 'perf', 'title' => 'Perf',
    ]);
    wp_cache_flush();
    $wp_query = new WP_Query(['page_id' => $pageId]); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    $wp_the_query = $wp_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    $post = $wp_query->post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    $before = $wpdb->num_queries;
    $bound = WPFastyBuilder\Front::bound($entry);
    $q = $wpdb->num_queries - $before;
    $missing = 0;
    foreach ($blocks as $b) {
        $missing += str_contains((string) ($bound[$b['id']]['content']['__html'] ?? ''), 'wp-block-heading') ? 0 : 1;
    }
    return [$q, $missing];
};

$wpfasty_rq_render($wpfasty_rq_pages[0], 1); // warm-up: options, the Codex manifest, the catalog
[$wpfasty_rq_q1, $wpfasty_rq_m1] = $wpfasty_rq_render($wpfasty_rq_pages[1], $wpfasty_rq_small);
[$wpfasty_rq_q2, $wpfasty_rq_m2] = $wpfasty_rq_render($wpfasty_rq_pages[2], $wpfasty_rq_large);
foreach ($wpfasty_rq_pages as $id) {
    wp_delete_post((int) $id, true);
}

WP_CLI::log(sprintf(
    'richtext: %d blocks → %d queries; %d blocks → %d queries',
    $wpfasty_rq_small,
    $wpfasty_rq_q1,
    $wpfasty_rq_large,
    $wpfasty_rq_q2
));
if ($wpfasty_rq_m1 + $wpfasty_rq_m2 > 0) {
    WP_CLI::error('richtext blocks without the_content of the queried page');
}
if ($wpfasty_rq_q2 > $wpfasty_rq_q1) {
    WP_CLI::error('N+1: the query count grows with the number of richtext blocks');
}
WP_CLI::success('No N+1 detected');
