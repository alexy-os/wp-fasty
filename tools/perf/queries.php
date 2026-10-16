<?php
/**
 * N+1 guard: DB query count of a rendered archive page must not grow with the number of posts.
 *
 * Usage: wp eval-file tools/perf/queries.php [post_type] [small] [large]
 * Exit code 1 when the large page needs more queries than the small one.
 *
 * @package Latte
 */

if (!defined('ABSPATH')) {
    exit;
}

$_SERVER['SERVER_NAME'] ??= 'localhost';
if (!defined('SAVEQUERIES')) {
    define('SAVEQUERIES', true);
}

$wpfasty_perf_type = $args[0] ?? 'post';
$wpfasty_perf_small = (int) ($args[1] ?? 2);
$wpfasty_perf_large = (int) ($args[2] ?? 6);

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
    $wp_query->is_home = $type === 'post';
    $wp_query->is_post_type_archive = $type !== 'post';
    $wp_query->is_archive = $type !== 'post';
    $theme = WPFasty\Core\Application::getInstance()->get('theme');
    $view = $type === 'post' ? 'pages/archive' : (WPFasty\Core\Application::getInstance()->get('types.registry')->get($type)?->view('archive') ?? 'pages/archive');
    ob_start();
    echo $theme->renderPage($view, $theme->context($view)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ob_end_clean();
    return [$wpdb->num_queries - $before, $wp_query->post_count];
}

wpfasty_perf_render($wpfasty_perf_type, 1); // Warm-up: options, menus, compiled templates.
[$wpfasty_perf_q1, $wpfasty_perf_p1] = wpfasty_perf_render($wpfasty_perf_type, $wpfasty_perf_small);
[$wpfasty_perf_q2, $wpfasty_perf_p2] = wpfasty_perf_render($wpfasty_perf_type, $wpfasty_perf_large);

WP_CLI::log(sprintf('%s: %d posts → %d queries; %d posts → %d queries', $wpfasty_perf_type, $wpfasty_perf_p1, $wpfasty_perf_q1, $wpfasty_perf_p2, $wpfasty_perf_q2));
if ($wpfasty_perf_p2 <= $wpfasty_perf_p1) {
    WP_CLI::warning('Not enough posts to compare; seed demo content first.');
} elseif ($wpfasty_perf_q2 > $wpfasty_perf_q1) {
    WP_CLI::error(sprintf('N+1: %d extra queries for %d extra posts', $wpfasty_perf_q2 - $wpfasty_perf_q1, $wpfasty_perf_p2 - $wpfasty_perf_p1));
}
WP_CLI::success('No N+1 detected');
