<?php

/**
 * N+1 guard of the builder richtext block (the record of the current route, DataSources::current): a page with
 * [small] then [large] richtext blocks (all six variants in turn) gets its bound data as Front does it
 * (Front::bound: the queried page as a Codex entry, the_content through its filters, cover, author, terms); the query
 * count must not grow with the number of blocks. Two temporary pages (one per run, so no memo carries over) are
 * added and removed.
 *   wp eval-file tools/perf/queries.php richtext [small] [large]
 * Exit 1 when the larger page needs more queries, or when a block misses the body.
 */
if (!defined('ABSPATH')) {
    exit;
}

$wpfastyRqSmall = max(1, (int) ($args[1] ?? 2));
$wpfastyRqLarge = max($wpfastyRqSmall + 1, (int) ($args[2] ?? 6));
$wpfastyRqCatalog = WPFastyBuilder\Sources::catalog('ui8kit');
if (null === $wpfastyRqCatalog || null === $wpfastyRqCatalog->block('richtext')) {
    WP_CLI::error('the ui8kit source has no richtext block');
}
$wpfastyRqThumb = (int) (get_posts([
    'post_type' => 'attachment', 'post_status' => 'inherit', 'numberposts' => 1, 'fields' => 'ids',
])[0] ?? 0);
$wpfastyRqBody = "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Perf</h2>\n<!-- /wp:heading -->\n"
    ."<!-- wp:paragraph -->\n<p>Body with a [caption width=\"10\"]caption[/caption].</p>\n<!-- /wp:paragraph -->";
$wpfastyRqPages = [];
foreach ([1, 2, 3] as $i) {
    $wpfastyRqPages[] = wp_insert_post([
        'post_type' => 'page', 'post_status' => 'publish', 'post_title' => "Perf richtext $i",
        'post_name' => "perf-richtext-$i", 'post_content' => $wpfastyRqBody, 'post_author' => 1,
    ]);
    if ($wpfastyRqThumb > 0) {
        set_post_thumbnail((int) end($wpfastyRqPages), $wpfastyRqThumb);
    }
}

/** @return array{0: int, 1: int} queries, blocks without the body */
$wpfastyRqRender = static function (int $pageId, int $n) use ($wpfastyRqCatalog): array {
    global $wpdb, $wp_query, $wp_the_query, $post;
    $variants = array_column((array) $wpfastyRqCatalog->block('richtext')['variants'], 'id');
    $blocks = [];
    for ($i = 0; $i < $n; ++$i) {
        $blocks[] = ['id' => "rt$i", 'block' => 'richtext', 'variant' => $variants[$i % count($variants)]];
    }
    [$entry] = WPFastyBuilder\Layout::normalize(['blocks' => $blocks], 'ui8kit', $wpfastyRqCatalog, [], [
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

$wpfastyRqRender($wpfastyRqPages[0], 1); // warm-up: options, the Codex manifest, the catalog
[$wpfastyRqQ1, $wpfastyRqM1] = $wpfastyRqRender($wpfastyRqPages[1], $wpfastyRqSmall);
[$wpfastyRqQ2, $wpfastyRqM2] = $wpfastyRqRender($wpfastyRqPages[2], $wpfastyRqLarge);
foreach ($wpfastyRqPages as $id) {
    wp_delete_post((int) $id, true);
}

WP_CLI::log(sprintf(
    'richtext: %d blocks → %d queries; %d blocks → %d queries',
    $wpfastyRqSmall,
    $wpfastyRqQ1,
    $wpfastyRqLarge,
    $wpfastyRqQ2
));
if ($wpfastyRqM1 + $wpfastyRqM2 > 0) {
    WP_CLI::error('richtext blocks without the_content of the queried page');
}
if ($wpfastyRqQ2 > $wpfastyRqQ1) {
    WP_CLI::error('N+1: the query count grows with the number of richtext blocks');
}
WP_CLI::success('No N+1 detected');
