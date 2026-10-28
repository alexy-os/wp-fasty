<?php

declare(strict_types=1);

// WordPress stubs for tests/PagesTest.php (Pages::create, Admin row action / buttons): page inserts and deletes are
// recorded in $GLOBALS['wpfb_pages'] ('inserted', 'deleted'; 'taken' = slugs already used).

require_once __DIR__ . '/wp-objects-stubs.php';

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $s): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags($s)));
    }
}
if (!function_exists('sanitize_title')) {
    function sanitize_title(string $s): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(strip_tags($s))), '-');
    }
}
if (!function_exists('wp_unique_post_slug')) {
    function wp_unique_post_slug(string $slug, int $id, string $status, string $type, int $parent): string
    {
        $n = 2;
        $out = $slug;
        while (in_array($out, $GLOBALS['wpfb_pages']['taken'] ?? [], true)) {
            $out = $slug . '-' . $n++;
        }
        return $out;
    }
}
if (!function_exists('wp_insert_post')) {
    /** @param array<string, mixed> $args */
    function wp_insert_post(array $args, bool $wpError = false): int
    {
        $GLOBALS['wpfb_pages']['inserted'][] = $args;
        return 100 + count($GLOBALS['wpfb_pages']['inserted']);
    }
}
if (!function_exists('wp_delete_post')) {
    function wp_delete_post(int $id, bool $force = false): bool
    {
        $GLOBALS['wpfb_pages']['deleted'][] = $id;
        return true;
    }
}
if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return 'http://example.test/wp-admin/' . ltrim($path, '/');
    }
}
if (!function_exists('add_query_arg')) {
    /** @param array<string, mixed> $args */
    function add_query_arg(array $args, string $url): string
    {
        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($args);
    }
}
if (!function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return str_replace('&', '&#038;', $url);
    }
}
if (!function_exists('get_option')) {
    function get_option(string $name, mixed $default = false): mixed
    {
        return $default;
    }
}
