<?php

declare(strict_types=1);

/**
 * WP_Query + cache-priming stubs for ContextFactory::recentPosts() (tests/ContextFactoryRecentPostsTest.php,
 * separate process). $GLOBALS['wpfasty_recent'] = posts (WP_Post list), thumbs [post id => attachment id],
 * log (every priming / per-post lookup: [function, args]).
 */
$GLOBALS['wpfasty_recent'] ??= ['posts' => [], 'thumbs' => [], 'log' => []];

if (!class_exists('WP_Query')) {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Generic.Files.OneObjectStructurePerFile
    class WP_Query
    {
        /** @var list<WP_Post> */
        public array $posts = [];
        public int $max_num_pages = 1;

        public function __construct(public array $query_vars = [])
        {
            $GLOBALS['wpfasty_recent']['log'][] = ['WP_Query', $query_vars];
            $this->posts = array_slice($GLOBALS['wpfasty_recent']['posts'], 0, (int) ($query_vars['posts_per_page'] ?? 10));
        }
    }
}

function wpfasty_recent_log(string $fn, ...$args): void
{
    $GLOBALS['wpfasty_recent']['log'][] = [$fn, $args];
}

if (!function_exists('update_meta_cache')) {
    /** @param list<int> $ids */
    function update_meta_cache(string $type, array $ids): bool
    {
        wpfasty_recent_log('update_meta_cache', $type, $ids);

        return true;
    }
}
if (!function_exists('update_object_term_cache')) {
    /** @param list<int> $ids */
    function update_object_term_cache(array $ids, string $type): bool
    {
        wpfasty_recent_log('update_object_term_cache', $ids, $type);

        return true;
    }
}
if (!function_exists('_prime_post_caches')) {
    /** @param list<int> $ids */
    function _prime_post_caches(array $ids, bool $terms = true, bool $meta = true): void
    {
        wpfasty_recent_log('_prime_post_caches', $ids, $terms, $meta);
    }
}
if (!function_exists('cache_users')) {
    /** @param list<int> $ids */
    function cache_users(array $ids): void
    {
        wpfasty_recent_log('cache_users', $ids);
    }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta(int $id, string $key = '', bool $single = false): mixed
    {
        return '_thumbnail_id' === $key ? (string) ($GLOBALS['wpfasty_recent']['thumbs'][$id] ?? '') : '';
    }
}

require_once __DIR__.'/wp-objects-stubs.php';

// what post() reads per post (kept trivial: the test is about the batch priming above)
if (!function_exists('post_password_required')) {
    function post_password_required(mixed $post = null): bool
    {
        return false;
    }
}
if (!function_exists('get_the_password_form')) {
    function get_the_password_form(mixed $post = 0): string
    {
        return '';
    }
}
if (!function_exists('has_excerpt')) {
    function has_excerpt(mixed $post = 0): bool
    {
        return false;
    }
}
if (!function_exists('get_the_excerpt')) {
    function get_the_excerpt(mixed $post = null): string
    {
        return '';
    }
}
if (!function_exists('strip_shortcodes')) {
    function strip_shortcodes(string $content): string
    {
        return $content;
    }
}
if (!function_exists('wp_trim_words')) {
    function wp_trim_words(string $text, int $num = 55, ?string $more = null): string
    {
        return $text;
    }
}
if (!function_exists('get_post_class')) {
    /** @return list<string> */
    function get_post_class(string|array $class = '', mixed $post = null): array
    {
        return ['post'];
    }
}
if (!function_exists('get_the_date')) {
    function get_the_date(string $format = '', mixed $post = null): string
    {
        return '2026-11-02';
    }
}
if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail(mixed $post = null): bool
    {
        return false;
    }
}
if (!function_exists('is_object_in_taxonomy')) {
    function is_object_in_taxonomy(string $type, string $taxonomy): bool
    {
        return false;
    }
}
if (!function_exists('get_the_author_meta')) {
    function get_the_author_meta(string $field = '', int|false $userId = false): string
    {
        return 'Author '.(int) $userId;
    }
}
if (!function_exists('get_author_posts_url')) {
    function get_author_posts_url(int $authorId, string $nicename = ''): string
    {
        return 'http://example.test/author/'.$authorId.'/';
    }
}
