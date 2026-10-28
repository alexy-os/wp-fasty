<?php

declare(strict_types=1);

/**
 * WordPress stubs for WPFastyBuilder\Links::index (tests/LinksRestTest.php, separate processes), backed by
 * $GLOBALS['wpfb_links']: pages (WP_Post list), terms (WP_Term list), types [name => labels name], posts_page id,
 * calls [function => count] and args [function => last args].
 */
$GLOBALS['wpfb_links'] ??= ['pages' => [], 'terms' => [], 'types' => [], 'posts_page' => 0, 'calls' => [], 'args' => []];

if (!class_exists('WP_Term')) {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Generic.Files.OneObjectStructurePerFile
    final class WP_Term
    {
        public int $term_id = 0;
        public string $name = '';
        public string $slug = '';
        public string $taxonomy = 'category';

        /** @param array<string, mixed> $props */
        public function __construct(array $props = [])
        {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        }
    }
}

/** @param array<mixed> $args */
function wpfb_links_call(string $fn, array $args): void
{
    $GLOBALS['wpfb_links']['calls'][$fn] = ($GLOBALS['wpfb_links']['calls'][$fn] ?? 0) + 1;
    $GLOBALS['wpfb_links']['args'][$fn] = $args;
}

if (!function_exists('get_posts')) {
    /**
     * @param array<string, mixed> $args
     * @return list<WP_Post>
     */
    function get_posts(array $args = []): array
    {
        wpfb_links_call('get_posts', $args);
        return $GLOBALS['wpfb_links']['pages'];
    }
}
if (!function_exists('get_terms')) {
    /**
     * @param array<string, mixed> $args
     * @return list<WP_Term>
     */
    function get_terms(array $args = []): array
    {
        wpfb_links_call('get_terms', $args);
        return $GLOBALS['wpfb_links']['terms'];
    }
}
if (!function_exists('get_post_types')) {
    /**
     * @param array<string, mixed> $args
     * @return array<string, object>
     */
    function get_post_types(array $args = [], string $output = 'names'): array
    {
        wpfb_links_call('get_post_types', [$args, $output]);
        $out = [];
        foreach ($GLOBALS['wpfb_links']['types'] as $name => $label) {
            $out[$name] = (object) ['name' => $name, 'labels' => (object) ['name' => $label]];
        }
        return $out;
    }
}
if (!function_exists('get_page_link')) {
    function get_page_link(int|WP_Post $post): string
    {
        $id = $post instanceof WP_Post ? $post->ID : $post;
        foreach ($GLOBALS['wpfb_links']['pages'] as $p) {
            if ($p->ID === $id) {
                return 'http://example.test/' . $p->post_name . '/';
            }
        }
        return $id === $GLOBALS['wpfb_links']['posts_page'] ? 'http://example.test/blog/' : '';
    }
}
if (!function_exists('get_category_link')) {
    function get_category_link(int|WP_Term $term): string
    {
        return 'http://example.test/category/' . ($term instanceof WP_Term ? $term->slug : (string) $term) . '/';
    }
}

if (!function_exists('is_email')) {
    function is_email(string $email): string|false
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : false;
    }
}
if (!function_exists('wp_date')) {
    /** fixed clock: 2026-10-28 12:00 UTC */
    function wp_date(string $format, ?int $timestamp = null): string|false
    {
        return gmdate($format, $timestamp ?? 1793188800);
    }
}
