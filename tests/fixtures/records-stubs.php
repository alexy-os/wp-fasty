<?php

declare(strict_types=1);

/**
 * WordPress stubs for WPFastyBuilder\Records (tests/RecordsTest.php, separate processes), backed by
 * $GLOBALS['wpfb_records']: posts (WP_Post list), meta [post id][key], terms (WP_Term list), log (every query:
 * [function, args]). URLs: get_permalink via $GLOBALS['wp_test_permalinks'] (bootstrap), get_term_link here.
 */
$GLOBALS['wpfb_records'] ??= ['posts' => [], 'meta' => [], 'terms' => [], 'log' => []];

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

if (!function_exists('get_posts')) {
    /**
     * @param array<string, mixed> $args
     * @return list<WP_Post>
     */
    function get_posts(array $args = []): array
    {
        $GLOBALS['wpfb_records']['log'][] = ['get_posts', $args];
        return array_values(array_filter(
            $GLOBALS['wpfb_records']['posts'],
            static fn ($p): bool => $p->post_type === ($args['post_type'] ?? 'post')
                && (!isset($args['post_name__in']) || in_array($p->post_name, $args['post_name__in'], true)),
        ));
    }
}
if (!function_exists('get_terms')) {
    /**
     * @param array<string, mixed> $args
     * @return list<WP_Term>
     */
    function get_terms(array $args = []): array
    {
        $GLOBALS['wpfb_records']['log'][] = ['get_terms', $args];
        return array_values(array_filter(
            $GLOBALS['wpfb_records']['terms'],
            static fn ($t): bool => in_array($t->taxonomy, (array) $args['taxonomy'], true)
                && (!isset($args['slug']) || in_array($t->slug, (array) $args['slug'], true)),
        ));
    }
}
if (!function_exists('get_term_link')) {
    function get_term_link(WP_Term $t): string
    {
        return 'http://example.test/' . $t->taxonomy . '/' . $t->slug . '/';
    }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta(int $id, string $key = '', bool $single = false): mixed
    {
        return $GLOBALS['wpfb_records']['meta'][$id][$key] ?? '';
    }
}
if (!function_exists('get_taxonomy')) {
    function get_taxonomy(string $tax): object|false
    {
        return (object) ['labels' => (object) ['singular_name' => ucfirst(str_replace('_', ' ', $tax))]];
    }
}
