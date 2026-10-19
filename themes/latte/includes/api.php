<?php

declare(strict_types=1);

/**
 * Child-theme API (global functions). Declarations only; loaded from functions.php.
 *
 * @package Latte
 */

/**
 * Child-theme API: view-model arrays for posts from secondary queries.
 *
 * @param list<\WP_Post|int> $posts
 * @return list<array<string, mixed>>
 */
function wpfasty_posts(array $posts): array
{
    $factory = WPFasty\Core\Application::getInstance()->get('data.context_factory');
    $resolved = array_values(array_filter(
        array_map('get_post', $posts),
        static fn ($p): bool => $p instanceof WP_Post
    ));
    $factory->prime($resolved);
    $out = [];
    foreach ($resolved as $p) {
        $out[] = $factory->postData($p);
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

/**
 * View-models of the latest posts of a declared post type (module archive ordering).
 *
 * @return list<array<string, mixed>>
 */
function wpfasty_type_posts(string $postType, int $count): array
{
    return wpfasty_posts(WPFasty\Core\Application::getInstance()->get('types.registry')->latest($postType, $count));
}

/**
 * Archive filter bar data: ['items' => [...], 'current' => slug].
 *
 * @return array{items: list<array{name: string, slug: string, url: string}>, current: string}
 */
function wpfasty_term_filter(string $taxonomy): array
{
    return WPFasty\Types\Registry::termFilter($taxonomy);
}
