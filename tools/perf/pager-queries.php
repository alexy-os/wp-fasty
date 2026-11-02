<?php

/**
 * N+1 guard of the builder posts pager (posts/listPaged, Pager): a page with one paged posts block bound to the posts
 * source, [small] then [large] posts per page, on page 1 and page 2 of the request (`paged` of the main query), gets
 * its bound data as Front does it (Front::bound: one list query with its found rows, the Codex entries, media,
 * authors, terms in one query each). No query shape may run twice in one render (guard.php), and the query count
 * must not grow above the [small] page 1 count. Fewer queries are fine: a page whose posts have no author or media
 * skips that batched query. Temporary posts top the site up to 2 × [large] + 1 and are removed after the run.
 *   wp eval-file tools/perf/queries.php pager [small] [large]
 * Exit 1 on a repeated query shape or a growing count, or when the pager or the page of posts is wrong.
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__.'/guard.php';

$wpfastyPqSmall = max(1, (int) ($args[1] ?? 2));
$wpfastyPqLarge = max($wpfastyPqSmall + 1, (int) ($args[2] ?? 6));
$wpfastyPqCatalog = WPFastyBuilder\Sources::catalog('ui8kit');
if (null === $wpfastyPqCatalog || null === $wpfastyPqCatalog->variant('posts', 'listPaged')) {
    WP_CLI::error('the ui8kit source has no posts/listPaged variant');
}
if (!isset(WPFastyBuilder\DataSources::all()['posts'])) {
    WP_CLI::error('the site has no posts data source');
}
$wpfastyPqAdded = [];
$wpfastyPqHave = count(get_posts([
    'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'no_found_rows' => true,
]));
for ($i = $wpfastyPqHave; $i < 2 * $wpfastyPqLarge + 1; ++$i) {
    $wpfastyPqAdded[] = wp_insert_post([
        'post_type' => 'post', 'post_status' => 'publish', 'post_title' => "Perf pager post $i",
        'post_name' => "perf-pager-post-$i", 'post_content' => 'Perf pager body', 'post_author' => 1,
    ]);
}
$wpfastyPqPage = wp_insert_post([
    'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Perf pager', 'post_name' => 'perf-pager',
]);
$wpfastyPqTotal = (int) wp_count_posts('post')->publish;

/** @return array{0: int, 1: string} queries, problem ('' = none) */
$wpfastyPqRender = static function (int $n, int $paged) use ($wpfastyPqCatalog, $wpfastyPqPage, $wpfastyPqTotal): array {
    global $wpdb, $wp_query, $wp_the_query, $post;
    [$entry] = WPFastyBuilder\Layout::normalize(['blocks' => [[
        'id' => 'pg', 'block' => 'posts', 'variant' => 'listPaged',
        'bindings' => ['posts' => ['source' => 'posts', 'limit' => $n]],
    ], ], ], 'ui8kit', $wpfastyPqCatalog, array_keys(WPFastyBuilder\DataSources::all()), [
        'id' => 'perf', 'slug' => 'perf', 'title' => 'Perf',
    ]);
    wp_cache_flush();
    // phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
    $wp_query = new WP_Query(['page_id' => $wpfastyPqPage, 'paged' => $paged]);
    $wp_the_query = $wp_query;
    $post = $wp_query->post;
    // phpcs:enable
    $sqls = [];
    $collect = static function (string $sql) use (&$sqls): string {
        $sqls[] = $sql;

        return $sql;
    };
    add_filter('query', $collect);
    $before = $wpdb->num_queries;
    $bound = WPFastyBuilder\Front::bound($entry);
    $q = $wpdb->num_queries - $before;
    remove_filter('query', $collect);
    $repeated = wpfasty_perf_repeated_shapes($sqls);
    $pager = $bound['pg']['pager'] ?? null;
    $pages = (int) ceil($wpfastyPqTotal / $n);
    $want = min($n, max(0, $wpfastyPqTotal - ($paged - 1) * $n));
    $problem = match (true) {
        [] !== $repeated => sprintf('N+1: %s', implode('; ', array_map(
            static fn (string $shape, int $n): string => sprintf('%d× %s', $n, substr($shape, 0, 120)),
            array_keys($repeated),
            $repeated
        ))),
        count($bound['pg']['posts'] ?? []) !== $want => sprintf('%d posts on page %d, want %d', count($bound['pg']['posts'] ?? []), $paged, $want),
        !is_array($pager) => 'no pager',
        $pager['page'] !== $paged || $pager['pages'] !== $pages => sprintf('pager %d/%d, want %d/%d', $pager['page'], $pager['pages'], $paged, $pages),
        '' === $pager['previous'] || '' === $pager['next'] => 'a pager button without a name',
        default => '',
    };

    return [$q, $problem];
};

$wpfastyPqRender(1, 1); // warm-up: options, the Codex manifest, the catalog
$wpfastyPqRuns = [];
foreach ([$wpfastyPqSmall, $wpfastyPqLarge] as $n) {
    foreach ([1, 2] as $paged) {
        $wpfastyPqRuns["$n/$paged"] = $wpfastyPqRender($n, $paged);
    }
}
foreach ($wpfastyPqAdded as $id) {
    wp_delete_post((int) $id, true);
}
wp_delete_post((int) $wpfastyPqPage, true);

WP_CLI::log(sprintf(
    'pager: %d posts/page → %d queries (page 1), %d (page 2); %d posts/page → %d (page 1), %d (page 2)',
    $wpfastyPqSmall,
    $wpfastyPqRuns["$wpfastyPqSmall/1"][0],
    $wpfastyPqRuns["$wpfastyPqSmall/2"][0],
    $wpfastyPqLarge,
    $wpfastyPqRuns["$wpfastyPqLarge/1"][0],
    $wpfastyPqRuns["$wpfastyPqLarge/2"][0]
));
foreach ($wpfastyPqRuns as $k => [, $problem]) {
    if ('' !== $problem) {
        WP_CLI::error("pager $k: $problem");
    }
}
$wpfastyPqBase = $wpfastyPqRuns["$wpfastyPqSmall/1"][0];
if (max(array_column($wpfastyPqRuns, 0)) > $wpfastyPqBase) {
    WP_CLI::error('N+1: the query count of the paged posts block grows with the posts per page or the page');
}
WP_CLI::success('No N+1 detected');
