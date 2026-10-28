<?php

declare(strict_types=1);

// Minimal WordPress stubs for WPFastyBuilder\Links (only functions no other test defines).

if (!function_exists('get_page_by_path')) {
    function get_page_by_path(string $path): ?object
    {
        return $GLOBALS['wp_test_pages'][$path] ?? null; // WP_Post by path (tests/SecurityXssTest.php)
    }
}
if (!function_exists('get_category_by_slug')) {
    function get_category_by_slug(string $slug): ?object
    {
        return null;
    }
}
if (!function_exists('post_type_exists')) {
    function post_type_exists(string $type): bool
    {
        return $type === 'product';
    }
}
if (!function_exists('get_post_type_archive_link')) {
    function get_post_type_archive_link(string $type): string|false
    {
        return 'http://example.test/' . $type . '/';
    }
}
