<?php

declare(strict_types=1);

/**
 * Minimal WP_Post / WP_User and the meta lookups the Codex Mapper calls, backed by $GLOBALS['wpfasty_wp'] (tests only):
 *   posts[id] = post_type, att[id] = {url, meta, alt}, user_meta[id][key], registered[type] = [key => args],
 *   meta[type][id][key].
 */
$GLOBALS['wpfasty_wp'] ??= ['posts' => [], 'att' => [], 'user_meta' => [], 'registered' => [], 'meta' => []];

if (!class_exists('WP_Post')) {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Generic.Files.OneObjectStructurePerFile
    final class WP_Post
    {
        public int $ID = 0;
        public string $post_title = '';
        public string $post_content = '';
        public string $post_excerpt = '';
        public string $post_name = '';
        public string $post_status = 'publish';
        public string $post_type = 'post';
        public string $post_mime_type = '';
        public int $post_parent = 0;
        public int|string $post_author = 0;
        public string $post_date_gmt = '0000-00-00 00:00:00';
        public string $post_modified_gmt = '0000-00-00 00:00:00';
        public string $comment_status = 'closed';
        public string $ping_status = 'closed';

        /** @param array<string, mixed> $props */
        public function __construct(array $props = [])
        {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        }
    }
}
if (!class_exists('WP_User')) {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Generic.Files.OneObjectStructurePerFile
    final class WP_User
    {
        public int $ID = 0;
        public string $user_login = '';
        public string $user_nicename = '';
        public string $user_url = '';
        public string $user_registered = '';
        public string $display_name = '';
        /** @var list<string> */
        public array $roles = [];

        /** @param array<string, mixed> $props */
        public function __construct(array $props = [])
        {
            foreach ($props as $k => $v) {
                $this->$k = $v;
            }
        }
    }
}
if (!function_exists('get_locale')) {
    function get_locale(): string
    {
        return 'en_US';
    }
}
if (!function_exists('get_post_type')) {
    function get_post_type(int|WP_Post|null $post = null): string|false
    {
        $id = $post instanceof WP_Post ? $post->ID : (int) $post;
        return $GLOBALS['wpfasty_wp']['posts'][$id] ?? false;
    }
}
if (!function_exists('wp_get_attachment_metadata')) {
    function wp_get_attachment_metadata(int $id): array|false
    {
        return $GLOBALS['wpfasty_wp']['att'][$id]['meta'] ?? false;
    }
}
if (!function_exists('wp_get_attachment_url')) {
    function wp_get_attachment_url(int $id): string|false
    {
        return $GLOBALS['wpfasty_wp']['att'][$id]['url'] ?? false;
    }
}
if (!function_exists('get_post_meta')) {
    function get_post_meta(int $id, string $key = '', bool $single = false): mixed
    {
        return $key === '_wp_attachment_image_alt' ? ($GLOBALS['wpfasty_wp']['att'][$id]['alt'] ?? '') : '';
    }
}
if (!function_exists('get_user_meta')) {
    function get_user_meta(int $id, string $key = '', bool $single = false): mixed
    {
        return $GLOBALS['wpfasty_wp']['user_meta'][$id][$key] ?? '';
    }
}
if (!function_exists('get_registered_meta_keys')) {
    /** @return array<string, array<string, mixed>> */
    function get_registered_meta_keys(string $type, string $sub = ''): array
    {
        return $GLOBALS['wpfasty_wp']['registered'][$type] ?? [];
    }
}
if (!function_exists('get_metadata')) {
    function get_metadata(string $type, int $id, string $key = '', bool $single = false): mixed
    {
        return $GLOBALS['wpfasty_wp']['meta'][$type][$id][$key] ?? ($single ? '' : []);
    }
}
