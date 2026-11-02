<?php

/**
 * N+1 guard: DB query count of a rendered archive page must not grow with the number of posts.
 *
 * Usage: wp eval-file tools/perf/queries.php [post_type] [small] [large]
 *        wp eval-file tools/perf/queries.php macros [small] [large]   (builder record tokens, macros-queries.php)
 *        wp eval-file tools/perf/queries.php richtext [small] [large] (builder richtext, richtext-queries.php)
 *        wp eval-file tools/perf/queries.php pager [small] [large]    (builder posts pager, pager-queries.php)
 * Counts $wpdb->num_queries (SAVEQUERIES is not needed).
 * Exit code 1 when the large page needs more queries than the small one.
 */
if (!defined('ABSPATH')) {
    exit;
}

$_SERVER['SERVER_NAME'] ??= 'localhost';

$wpfastyPerfType = $args[0] ?? 'post';
if ('macros' === $wpfastyPerfType) {
    // builder record tokens on a page: wp eval-file tools/perf/queries.php macros [small] [large]
    require __DIR__.'/macros-queries.php';

    return;
}
if ('pager' === $wpfastyPerfType) {
    // builder posts pager (posts/listPaged, pages 1 and 2): wp eval-file tools/perf/queries.php pager [small] [large]
    require __DIR__.'/pager-queries.php';

    return;
}
if ('richtext' === $wpfastyPerfType) {
    // builder richtext (the queried page's the_content): wp eval-file tools/perf/queries.php richtext [small] [large]
    require __DIR__.'/richtext-queries.php';

    return;
}
$wpfastyPerfSmall = (int) ($args[1] ?? 2);
$wpfastyPerfLarge = (int) ($args[2] ?? 6);

/**
 * Renders the archive of $type with $n posts per page as the main query; returns [queries, posts].
 *
 * @return array{0: int, 1: int}
 */
function wpfasty_perf_render(string $type, int $n): array
{
    global $wpdb, $wp_query, $wp_the_query;
    wp_cache_flush();
    $before = $wpdb->num_queries;
    $wp_query = new WP_Query([
        'post_type' => $type,
        'posts_per_page' => $n,
        'ignore_sticky_posts' => true,
    ]);
    $wp_the_query = $wp_query;
    $wp_query->is_home = 'post' === $type;
    $wp_query->is_post_type_archive = 'post' !== $type;
    $wp_query->is_archive = 'post' !== $type;
    $theme = WPFasty\Core\Application::getInstance()->get('theme');
    $view = 'post' === $type ? 'pages/archive' : (WPFasty\Core\Application::getInstance()->get('types.registry')->get($type)?->view('archive') ?? 'pages/archive');
    ob_start();
    echo $theme->renderPage($view, $theme->context($view)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ob_end_clean();

    return [$wpdb->num_queries - $before, $wp_query->post_count];
}

wpfasty_perf_render($wpfastyPerfType, 1); // Warm-up: options, menus, compiled templates.
[$wpfastyPerfQ1, $wpfastyPerfP1] = wpfasty_perf_render($wpfastyPerfType, $wpfastyPerfSmall);
[$wpfastyPerfQ2, $wpfastyPerfP2] = wpfasty_perf_render($wpfastyPerfType, $wpfastyPerfLarge);

// Same comparison with widget areas emptied: sidebar widgets (recent posts, comments, archives) have a constant size,
// but they reuse posts the list already loaded, so with them a larger page can need *fewer* queries and hide an N+1.
add_filter('sidebars_widgets', static fn (): array => []);
wpfasty_perf_render($wpfastyPerfType, 1);
[$wpfastyPerfL1] = wpfasty_perf_render($wpfastyPerfType, $wpfastyPerfSmall);
[$wpfastyPerfL2] = wpfasty_perf_render($wpfastyPerfType, $wpfastyPerfLarge);

WP_CLI::log(sprintf(
    '%s: %d posts → %d queries; %d posts → %d queries (list only, no widgets: %d → %d)',
    $wpfastyPerfType,
    $wpfastyPerfP1,
    $wpfastyPerfQ1,
    $wpfastyPerfP2,
    $wpfastyPerfQ2,
    $wpfastyPerfL1,
    $wpfastyPerfL2
));
if ($wpfastyPerfP2 <= $wpfastyPerfP1) {
    WP_CLI::warning('Not enough posts to compare; seed demo content first.');
} elseif ($wpfastyPerfQ2 > $wpfastyPerfQ1 || $wpfastyPerfL2 > $wpfastyPerfL1) {
    WP_CLI::error(sprintf(
        'N+1: %d extra queries (list only: %d) for %d extra posts',
        $wpfastyPerfQ2 - $wpfastyPerfQ1,
        $wpfastyPerfL2 - $wpfastyPerfL1,
        $wpfastyPerfP2 - $wpfastyPerfP1
    ));
}
WP_CLI::success('No N+1 detected');
