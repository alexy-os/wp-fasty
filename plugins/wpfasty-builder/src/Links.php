<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Link tokens in props, resolved when a block renders so a layout survives a domain change or a Playground
 * import: {home}, {posts} (the posts page, or home), {page:<slug>}, {category:<slug>}, {archive:<post type>}.
 * A token may be followed by a path or a query: "{page:about}#team".
 */
final class Links
{
    public const TOKEN = '/\{(home|posts|page|category|archive)(?::([a-z0-9_-]+))?\}/';

    /** @var array<string, string> */
    private static array $cache = [];

    public static function resolve(string $value): string
    {
        if (!str_contains($value, '{')) {
            return $value;
        }
        return (string) preg_replace_callback(self::TOKEN, static function (array $m): string {
            $key = $m[0];
            return self::$cache[$key] ??= self::url($m[1], $m[2] ?? '');
        }, $value);
    }

    /**
     * @param array<mixed> $props
     * @return array<mixed>
     */
    public static function walk(array $props): array
    {
        foreach ($props as $k => $v) {
            if (is_array($v)) {
                $props[$k] = self::walk($v);
            } elseif (is_string($v)) {
                $props[$k] = self::resolve($v);
            }
        }
        return $props;
    }

    private static function url(string $kind, string $arg): string
    {
        $home = home_url('/');
        $url = match ($kind) {
            'home' => $home,
            'posts' => (int) get_option('page_for_posts') > 0
                ? get_permalink((int) get_option('page_for_posts'))
                : $home,
            'page' => ($p = get_page_by_path($arg)) instanceof \WP_Post ? get_permalink($p) : $home,
            'category' => ($t = get_category_by_slug($arg)) instanceof \WP_Term ? get_category_link($t) : $home,
            'archive' => post_type_exists($arg) ? get_post_type_archive_link($arg) : $home,
            default => $home,
        };
        return is_string($url) && $url !== '' ? $url : $home;
    }
}
