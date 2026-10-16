<?php

declare(strict_types=1);

/**
 * Latte — clean WP FastY theme (UI8Kit primitives).
 *
 * @package Latte
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Latte theme: run composer install in themes/latte (and bun run ui:primitives from the monorepo root).',
            'latte'
        );
        echo '</p></div>';
    });
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

WPFasty\Core\Application::getInstance();

add_action('init', static function (): void {
    load_theme_textdomain('latte', get_template_directory() . '/languages');

    if (is_child_theme()) {
        $domain = wp_get_theme()->get('TextDomain');
        if (is_string($domain) && $domain !== '' && $domain !== 'latte') {
            load_child_theme_textdomain($domain, get_stylesheet_directory() . '/languages');
        }
    }
});

/**
 * Child-theme API: view-model arrays for posts from secondary queries.
 *
 * @param list<\WP_Post|int> $posts
 * @return list<array<string, mixed>>
 */
function wpfasty_posts(array $posts): array
{
    $factory = WPFasty\Core\Application::getInstance()->get('data.context_factory');
    $out = [];
    foreach ($posts as $p) {
        $p = get_post($p);
        if ($p instanceof WP_Post) {
            $out[] = $factory->postData($p);
        }
    }
    return $out;
}

/**
 * Post-type module capability lookup, e.g. wpfasty_type_supports('product', 'purchasable').
 */
function wpfasty_type_supports(string $postType, string $capability): bool
{
    return WPFasty\Core\Application::getInstance()->get('types.registry')->supports($postType, $capability);
}

/**
 * Declared field value of a post-type module (raw, sanitized on save).
 */
function wpfasty_field(int $postId, string $field): string
{
    return WPFasty\Core\Application::getInstance()->get('types.registry')->value($postId, $field);
}
