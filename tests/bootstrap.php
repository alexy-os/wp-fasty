<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/');
}

$themeVendor = dirname(__DIR__) . '/themes/latte/vendor/autoload.php';
if (!is_file($themeVendor)) {
    fwrite(STDERR, "Missing themes/latte/vendor — run: bash tools/composer.sh install --working-dir=themes/latte\n");
    exit(1);
}
require $themeVendor;

// Root dev deps (twig/twig for TwigRenderer tests).
if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
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
if (!function_exists('esc_attr__')) {
    function esc_attr__(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}
if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('wp_salt')) {
    function wp_salt(string $scheme = 'auth'): string
    {
        return 'test-salt-' . $scheme;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode(mixed $data): string|false
    {
        return json_encode($data);
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $html): string
    {
        return $html;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        $filters = $GLOBALS['wp_test_filters'][$hook] ?? [];
        foreach ($filters as $callback) {
            $value = $callback($value, ...$args);
        }
        return $value;
    }
}
if (!function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        $GLOBALS['wp_test_filters'][$hook][] = $callback;
        return true;
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
        return $GLOBALS['wp_test_menus']['locations'] ?? [];
    }
}
if (!function_exists('wp_get_nav_menu_items')) {
    function wp_get_nav_menu_items(int|string $menu): array|false
    {
        return $GLOBALS['wp_test_menus']['items'][$menu] ?? false;
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
if (!function_exists('is_category')) {
    function is_category(): bool
    {
        return $GLOBALS['wp_test_flags']['is_category'] ?? false;
    }
}
if (!function_exists('is_tag')) {
    function is_tag(): bool
    {
        return $GLOBALS['wp_test_flags']['is_tag'] ?? false;
    }
}
if (!function_exists('is_author')) {
    function is_author(): bool
    {
        return $GLOBALS['wp_test_flags']['is_author'] ?? false;
    }
}
if (!function_exists('is_date')) {
    function is_date(): bool
    {
        return $GLOBALS['wp_test_flags']['is_date'] ?? false;
    }
}
if (!function_exists('is_tax')) {
    function is_tax(): bool
    {
        return $GLOBALS['wp_test_flags']['is_tax'] ?? false;
    }
}
if (!function_exists('is_post_type_archive')) {
    function is_post_type_archive(): bool
    {
        return $GLOBALS['wp_test_flags']['is_post_type_archive'] ?? false;
    }
}
if (!function_exists('is_page')) {
    function is_page(): bool
    {
        return $GLOBALS['wp_test_flags']['is_page'] ?? false;
    }
}
if (!function_exists('is_attachment')) {
    function is_attachment(): bool
    {
        return $GLOBALS['wp_test_flags']['is_attachment'] ?? false;
    }
}
if (!function_exists('is_singular')) {
    function is_singular(string|array $postTypes = ''): bool
    {
        if (!($GLOBALS['wp_test_flags']['is_singular'] ?? false)) {
            return false;
        }
        if ($postTypes === '' || $postTypes === []) {
            return true;
        }
        $type = $GLOBALS['wp_test_flags']['singular_type'] ?? 'post';
        $types = (array) $postTypes;
        return in_array($type, $types, true);
    }
}
if (!function_exists('get_the_title')) {
    function get_the_title(int $post = 0): string
    {
        return (string) ($GLOBALS['wp_test_titles'][$post] ?? '');
    }
}
if (!function_exists('wp_head')) {
    function wp_head(): void
    {
        // Mirrors core `_wp_render_title_tag()` (theme declares title-tag support) so renders are full documents.
        $title = (string) ($GLOBALS['wp_test_flags']['document_title'] ?? get_bloginfo('name'));
        echo '<title>' . htmlspecialchars($title !== '' ? $title : 'WordPress', ENT_QUOTES) . '</title>' . "\n";
    }
}
if (!function_exists('wp_footer')) {
    function wp_footer(): void
    {
    }
}
if (!function_exists('wp_body_open')) {
    function wp_body_open(): void
    {
        echo '<!--wp_body_open-->';
    }
}
if (!function_exists('language_attributes')) {
    function language_attributes(): void
    {
        echo 'lang="en-US"';
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
    function get_permalink(int|WP_Post|null $post = 0): string|false
    {
        $id = $post instanceof WP_Post ? $post->ID : (int) $post;
        return $GLOBALS['wp_test_permalinks'][$id] ?? 'http://example.test/blog';
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
if (!function_exists('is_active_sidebar')) {
    function is_active_sidebar(string $id): bool
    {
        return !empty($GLOBALS['wp_test_sidebars'][$id]);
    }
}
if (!function_exists('dynamic_sidebar')) {
    function dynamic_sidebar(string $id): bool
    {
        $html = $GLOBALS['wp_test_sidebars'][$id] ?? '';
        if ($html === '') {
            return false;
        }
        echo $html;
        return true;
    }
}
if (!function_exists('get_post')) {
    function get_post(int|WP_Post|null $post = null): ?WP_Post
    {
        return null;
    }
}
if (!function_exists('wp_reset_postdata')) {
    function wp_reset_postdata(): void
    {
    }
}
if (!function_exists('get_query_var')) {
    function get_query_var(string $var, mixed $default = ''): mixed
    {
        return $default;
    }
}
if (!function_exists('get_pagenum_link')) {
    function get_pagenum_link(int $page = 1): string
    {
        return 'http://example.test/page/' . $page;
    }
}

$GLOBALS['wp_test_flags'] = [];
$GLOBALS['wp_test_menus'] = ['locations' => [], 'items' => []];
$GLOBALS['wp_test_filters'] = [];
$GLOBALS['wp_test_sidebars'] = [];
