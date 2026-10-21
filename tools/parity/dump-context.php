<?php
/**
 * Snapshot one request's view + ContextFactory context as a parity fixture (dev tool, needs a WP site).
 *   wp eval-file tools/parity/dump-context.php <uri> <out.json>
 *   [posts-front] renders the theme front page even when a static page is set.
 * The fixture is frozen data: parity renders it with both engines, no WordPress needed.
 *
 * @package Latte
 */

use WPFasty\Core\Application;
use WPFasty\Theme\ThemeService;

$wpfastyArgs = $args ?? [];
if (count($wpfastyArgs) < 2) {
    WP_CLI::error('usage: wp eval-file tools/parity/dump-context.php <uri> <out.json>');
}
[$wpfastyUri, $wpfastyOut] = $wpfastyArgs;

$_SERVER['REQUEST_URI'] = $wpfastyUri;
$wpfastyQuery = (string) wp_parse_url($wpfastyUri, PHP_URL_QUERY);
if ($wpfastyQuery !== '') {
    parse_str($wpfastyQuery, $_GET); // phpcs:ignore WordPress.Security.NonceVerification
}
if (in_array('posts-front', $wpfastyArgs, true)) {
    // Theme front page (hero + featured grid) instead of a static/builder page.
    add_filter('pre_option_show_on_front', static fn (): string => 'posts');
}
$GLOBALS['wp']->main();

$wpfastyTheme = Application::getInstance()->get(ThemeService::class);
$wpfastyView = $wpfastyTheme->resolveView();
$wpfastyJson = wp_json_encode(
    ['uri' => $wpfastyUri, 'view' => $wpfastyView, 'page' => true, 'context' => $wpfastyTheme->context($wpfastyView)],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
file_put_contents($wpfastyOut, $wpfastyJson . "\n"); // phpcs:ignore WordPress.WP.AlternativeFunctions
WP_CLI::success("{$wpfastyUri} → {$wpfastyView} ({$wpfastyOut})");
