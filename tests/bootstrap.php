<?php

declare(strict_types=1);

$themeVendor = dirname(__DIR__) . '/themes/latte/vendor/autoload.php';
if (!is_file($themeVendor)) {
    fwrite(STDERR, "Missing themes/latte/vendor — run: bash tools/composer.sh install --working-dir=themes/latte\n");
    exit(1);
}
require $themeVendor;

// Windows path-repo junctions sometimes break PHP includes; load UI8Kit from workspace if needed.
if (!class_exists(\UI8Kit\Rt::class)) {
    $rt = dirname(__DIR__) . '/.workspaces/ui8kit-latte/php/UI8Kit/Rt.php';
    $classes = dirname(__DIR__) . '/.workspaces/ui8kit-latte/php/UI8Kit/Classes.php';
    if (is_file($rt) && is_file($classes)) {
        require_once $rt;
        require_once $classes;
    }
}

// Minimal WordPress stubs for unit tests (no WP bootstrap).
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}
if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return $value;
    }
}
if (!function_exists('home_url')) {
    function home_url(string $path = ''): string
    {
        return 'http://example.test' . $path;
    }
}
if (!function_exists('get_bloginfo')) {
    function get_bloginfo(string $show = ''): string
    {
        return match ($show) {
            'name' => 'Latte Test',
            'description' => 'Test site',
            'language' => 'en-US',
            'charset' => 'UTF-8',
            default => '',
        };
    }
}
if (!function_exists('get_template_directory')) {
    function get_template_directory(): string
    {
        return dirname(__DIR__) . '/themes/latte';
    }
}
if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory(): string
    {
        return dirname(__DIR__) . '/themes/latte';
    }
}
if (!function_exists('get_template_directory_uri')) {
    function get_template_directory_uri(): string
    {
        return 'http://example.test/wp-content/themes/latte';
    }
}
if (!function_exists('get_stylesheet_directory_uri')) {
    function get_stylesheet_directory_uri(): string
    {
        return 'http://example.test/wp-content/themes/latte';
    }
}
if (!function_exists('get_nav_menu_locations')) {
    function get_nav_menu_locations(): array
    {
        return [];
    }
}
if (!function_exists('is_front_page')) {
    function is_front_page(): bool
    {
        return $GLOBALS['wp_test_flags']['is_front_page'] ?? false;
    }
}
if (!function_exists('is_home')) {
    function is_home(): bool
    {
        return $GLOBALS['wp_test_flags']['is_home'] ?? false;
    }
}
if (!function_exists('is_404')) {
    function is_404(): bool
    {
        return $GLOBALS['wp_test_flags']['is_404'] ?? false;
    }
}
if (!function_exists('is_search')) {
    function is_search(): bool
    {
        return $GLOBALS['wp_test_flags']['is_search'] ?? false;
    }
}
if (!function_exists('is_archive')) {
    function is_archive(): bool
    {
        return $GLOBALS['wp_test_flags']['is_archive'] ?? false;
    }
}
if (!function_exists('is_page')) {
    function is_page(): bool
    {
        return $GLOBALS['wp_test_flags']['is_page'] ?? false;
    }
}
if (!function_exists('is_singular')) {
    function is_singular(string $postType = ''): bool
    {
        return $GLOBALS['wp_test_flags']['is_singular'] ?? false;
    }
}
if (!function_exists('wp_head')) {
    function wp_head(): void
    {
    }
}
if (!function_exists('wp_footer')) {
    function wp_footer(): void
    {
    }
}
if (!function_exists('body_class')) {
    function body_class(string ...$classes): void
    {
        echo 'class="body ' . implode(' ', $classes) . '"';
    }
}
if (!function_exists('do_action')) {
    function do_action(string $hook, mixed ...$args): void
    {
    }
}
if (!function_exists('has_custom_logo')) {
    function has_custom_logo(): bool
    {
        return false;
    }
}
if (!function_exists('get_option')) {
    function get_option(string $key, mixed $default = false): mixed
    {
        return $default;
    }
}
if (!function_exists('get_permalink')) {
    function get_permalink(int|WP_Post $post = 0): string|false
    {
        return 'http://example.test/blog';
    }
}
if (!function_exists('get_search_query')) {
    function get_search_query(): string
    {
        return (string) ($GLOBALS['wp_test_flags']['search_query'] ?? '');
    }
}
if (!function_exists('have_posts')) {
    function have_posts(): bool
    {
        return false;
    }
}
if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text): string
    {
        return strip_tags($text);
    }
}
if (!function_exists('get_the_archive_title')) {
    function get_the_archive_title(): string
    {
        return 'Archive';
    }
}
if (!function_exists('get_the_archive_description')) {
    function get_the_archive_description(): string
    {
        return '';
    }
}

$GLOBALS['wp_test_flags'] = [];
