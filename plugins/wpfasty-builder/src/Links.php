<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Link tokens in props, resolved when a block renders so a layout survives a domain change or a Playground
 * import: {home}, {posts} (the posts page, or home), {page:<slug>}, {category:<slug>}, {archive:<post type>}.
 * A token may be followed by a path or a query: "{page:about}#team". index() lists the targets for the editor's link
 * picker (GET wpfasty-builder/v1/links): the editor resolves tokens client-side from that one response.
 */
final class Links
{
    public const TOKEN = '/\{(home|posts|page|category|archive)(?::([a-z0-9_-]+))?\}/';
    /** a slug a token can name (TOKEN's argument) */
    public const SLUG = '/^[a-z0-9_-]+$/';
    /** most pages / categories listed (the picker searches the list; a token still works for any slug) */
    public const MAX = 500;

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

    /**
     * The link targets of the site in one response, with the URL each token resolves to: site ({home}, {posts}),
     * published top-level pages (what {page:<slug>} finds), categories and the public post type archives. Four
     * queries whatever the number of items (pages, categories; no meta or term caches primed).
     *
     * @return array{
     *   home: string,
     *   site: list<array{token: string, label: string, url: string}>,
     *   pages: list<array{token: string, label: string, slug: string, url: string}>,
     *   sections: list<array{token: string, label: string, slug: string, kind: string, url: string}>
     * }
     */
    public static function index(): array
    {
        $home = home_url('/');
        $postsPage = (int) get_option('page_for_posts');
        $site = [
            ['token' => '{home}', 'label' => __('Home', 'wpfasty-builder'), 'url' => $home],
            [
                'token' => '{posts}',
                'label' => __('Posts', 'wpfasty-builder'),
                'url' => $postsPage > 0 ? self::str(get_page_link($postsPage), $home) : $home,
            ],
        ];
        $pages = [];
        $found = get_posts([
            'post_type' => 'page', 'post_status' => 'publish', 'post_parent' => 0, 'numberposts' => self::MAX,
            'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true,
            'update_post_meta_cache' => false, 'update_post_term_cache' => false,
        ]);
        foreach (is_array($found) ? $found : [] as $p) {
            if (!$p instanceof \WP_Post || preg_match(self::SLUG, (string) $p->post_name) !== 1) {
                continue;
            }
            $slug = (string) $p->post_name;
            $title = trim(wp_strip_all_tags((string) $p->post_title));
            $pages[] = [
                'token' => '{page:' . $slug . '}',
                'label' => $title !== '' ? $title : $slug,
                'slug' => $slug,
                'url' => self::str(get_page_link($p), $home),
            ];
        }
        $sections = [];
        $terms = get_terms([
            'taxonomy' => 'category', 'hide_empty' => false, 'number' => self::MAX, 'orderby' => 'name',
            'update_term_meta_cache' => false,
        ]);
        foreach (is_array($terms) ? $terms : [] as $t) {
            if (!$t instanceof \WP_Term || preg_match(self::SLUG, (string) $t->slug) !== 1) {
                continue;
            }
            $sections[] = [
                'token' => '{category:' . $t->slug . '}',
                'label' => (string) $t->name,
                'slug' => (string) $t->slug,
                'kind' => 'category',
                'url' => self::str(get_category_link($t), $home),
            ];
        }
        foreach (get_post_types(['public' => true, 'has_archive' => true], 'objects') as $name => $type) {
            $name = (string) $name;
            if (preg_match(self::SLUG, $name) !== 1) {
                continue;
            }
            $label = is_object($type) && isset($type->labels->name) ? (string) $type->labels->name : $name;
            $sections[] = [
                'token' => '{archive:' . $name . '}',
                'label' => $label,
                'slug' => $name,
                'kind' => 'archive',
                'url' => self::str(get_post_type_archive_link($name), $home),
            ];
        }
        return ['home' => $home, 'site' => $site, 'pages' => $pages, 'sections' => $sections];
    }

    private static function str(mixed $url, string $fallback): string
    {
        return is_string($url) && $url !== '' ? $url : $fallback;
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
