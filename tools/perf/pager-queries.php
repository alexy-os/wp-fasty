<?php
/**
 * N+1 guard of the builder posts pager (posts/listPaged, Pager): a page with one paged posts block bound to the posts
 * source, [small] then [large] posts per page, on page 1 and page 2 of the request (`paged` of the main query), gets
 * its bound data as Front does it (Front::bound: one list query with its found rows, the Codex entries, media,
 * authors, terms in one query each). The query count must not grow with the posts per page and must be the same on
 * page 2 as on page 1. Temporary posts top the site up to 2 × [large] + 1 and are removed after the run.
 *   wp eval-file tools/perf/queries.php pager [small] [large]
 * Exit 1 when a count grows, or when the pager or the page of posts is wrong.
 *
 * @package WPFasty
 */

if (!defined('ABSPATH')) {
    exit;
}

$wpfasty_pq_small = max(1, (int) ($args[1] ?? 2));
$wpfasty_pq_large = max($wpfasty_pq_small + 1, (int) ($args[2] ?? 6));
$wpfasty_pq_catalog = WPFastyBuilder\Sources::catalog('ui8kit');
if ($wpfasty_pq_catalog === null || $wpfasty_pq_catalog->variant('posts', 'listPaged') === null) {
    WP_CLI::error('the ui8kit source has no posts/listPaged variant');
}
if (!isset(WPFastyBuilder\DataSources::all()['posts'])) {
    WP_CLI::error('the site has no posts data source');
}
$wpfasty_pq_added = [];
$wpfasty_pq_have = count(get_posts([
    'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'no_found_rows' => true,
]));
for ($i = $wpfasty_pq_have; $i < 2 * $wpfasty_pq_large + 1; $i++) {
    $wpfasty_pq_added[] = wp_insert_post([
        'post_type' => 'post', 'post_status' => 'publish', 'post_title' => "Perf pager post $i",
        'post_name' => "perf-pager-post-$i", 'post_content' => 'Perf pager body', 'post_author' => 1,
    ]);
}
$wpfasty_pq_page = wp_insert_post([
    'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Perf pager', 'post_name' => 'perf-pager',
]);
$wpfasty_pq_total = (int) wp_count_posts('post')->publish;

/** @return array{0: int, 1: string} queries, problem ('' = none) */
$wpfasty_pq_render = static function (int $n, int $paged) use ($wpfasty_pq_catalog, $wpfasty_pq_page, $wpfasty_pq_total): array {
    global $wpdb, $wp_query, $wp_the_query, $post;
    [$entry] = WPFastyBuilder\Layout::normalize(['blocks' => [[
        'id' => 'pg', 'block' => 'posts', 'variant' => 'listPaged',
        'bindings' => ['posts' => ['source' => 'posts', 'limit' => $n]],
    ]]], 'ui8kit', $wpfasty_pq_catalog, array_keys(WPFastyBuilder\DataSources::all()), [
        'id' => 'perf', 'slug' => 'perf', 'title' => 'Perf',
    ]);
    wp_cache_flush();
    // phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
    $wp_query = new WP_Query(['page_id' => $wpfasty_pq_page, 'paged' => $paged]);
    $wp_the_query = $wp_query;
    $post = $wp_query->post;
    // phpcs:enable
    $before = $wpdb->num_queries;
    $bound = WPFastyBuilder\Front::bound($entry);
    $q = $wpdb->num_queries - $before;
    $pager = $bound['pg']['pager'] ?? null;
    $pages = (int) ceil($wpfasty_pq_total / $n);
    $want = min($n, max(0, $wpfasty_pq_total - ($paged - 1) * $n));
    $problem = match (true) {
        count($bound['pg']['posts'] ?? []) !== $want => sprintf('%d posts on page %d, want %d', count($bound['pg']['posts'] ?? []), $paged, $want),
        !is_array($pager) => 'no pager',
        $pager['page'] !== $paged || $pager['pages'] !== $pages => sprintf('pager %d/%d, want %d/%d', $pager['page'], $pager['pages'], $paged, $pages),
        $pager['previous'] === '' || $pager['next'] === '' => 'a pager button without a name',
        default => '',
    };
    return [$q, $problem];
};

$wpfasty_pq_render(1, 1); // warm-up: options, the Codex manifest, the catalog
$wpfasty_pq_runs = [];
foreach ([$wpfasty_pq_small, $wpfasty_pq_large] as $n) {
    foreach ([1, 2] as $paged) {
        $wpfasty_pq_runs["$n/$paged"] = $wpfasty_pq_render($n, $paged);
    }
}
foreach ($wpfasty_pq_added as $id) {
    wp_delete_post((int) $id, true);
}
wp_delete_post((int) $wpfasty_pq_page, true);

WP_CLI::log(sprintf(
    'pager: %d posts/page → %d queries (page 1), %d (page 2); %d posts/page → %d (page 1), %d (page 2)',
    $wpfasty_pq_small,
    $wpfasty_pq_runs["$wpfasty_pq_small/1"][0],
    $wpfasty_pq_runs["$wpfasty_pq_small/2"][0],
    $wpfasty_pq_large,
    $wpfasty_pq_runs["$wpfasty_pq_large/1"][0],
    $wpfasty_pq_runs["$wpfasty_pq_large/2"][0]
));
foreach ($wpfasty_pq_runs as $k => [, $problem]) {
    if ($problem !== '') {
        WP_CLI::error("pager $k: $problem");
    }
}
$wpfasty_pq_counts = array_unique(array_column($wpfasty_pq_runs, 0));
if (count($wpfasty_pq_counts) > 1) {
    WP_CLI::error('N+1: the query count of the paged posts block changes with the posts per page or the page');
}
WP_CLI::success('No N+1 detected');
