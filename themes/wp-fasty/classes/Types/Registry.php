<?php

/**
 * Declarative post-type modules (ADR-001 §3).
 *
 * Child themes drop `types/<name>.php` returning an array. The theme side (this class)
 * only does presentation: view routing, main-query tuning, archive titles and post
 * view-models. Registration of post types/taxonomies/meta and the admin metabox lives in
 * the companion plugin `plugins/wpfasty-types` (wordpress.org: plugin territory, ADR-001 §3a),
 * which receives this registry via the `wpfasty_types_loaded` action.
 */

declare(strict_types=1);

namespace WPFasty\Types;

use WPFasty\Core\BootableServiceInterface;
use WPFasty\Core\Cast;
use WPFasty\Core\Model\Post;

final class Registry implements BootableServiceInterface
{
    /** @var array<string, PostType> */
    private array $types = [];

    private bool $loaded = false;

    /**
     * @param list<string> $dirs Directories scanned for `*.php` modules.
     */
    public function __construct(private readonly array $dirs = [])
    {
    }

    public function boot(): void
    {
        // Load at init:0 so module labels can use __() without early textdomain notices.
        add_action('init', [$this, 'load'], 0);
        add_filter('wpfasty_post_data', [$this, 'postData'], 5, 2);
        add_filter('wpfasty_resolve_view', [$this, 'resolveView'], 5);
        add_action('pre_get_posts', [$this, 'mainQuery']);
        add_filter('post_type_archive_title', [$this, 'archiveTitle'], 10, 2);
        add_filter('get_the_archive_title_prefix', [$this, 'archiveTitlePrefix']);
    }

    /** Loads module files once (dirs filter: `wpfasty_type_dirs`, arrays: `wpfasty_types`). */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;
        $dirs = function_exists('apply_filters')
            ? (array) apply_filters('wpfasty_type_dirs', $this->dirs)
            : $this->dirs;
        $defs = [];
        foreach (array_unique(Cast::strings($dirs)) as $dir) {
            foreach (glob(rtrim($dir, '/\\').'/*.php') ?: [] as $file) {
                $def = include $file;
                if (is_array($def)) {
                    $defs[] = $def;
                }
            }
        }
        if (function_exists('apply_filters')) {
            $defs = (array) apply_filters('wpfasty_types', $defs);
        }
        foreach ($defs as $def) {
            $this->add(PostType::fromArray(Cast::map($def)));
        }
        /*
         * Data registration (post types, taxonomies, meta, metaboxes) is plugin territory
         * (wordpress.org rules): the companion plugin `wpfasty-types` listens here.
         */
        if (function_exists('do_action')) {
            do_action('wpfasty_types_loaded', $this);
        }
    }

    public function add(PostType $type): void
    {
        $this->types[$type->name] = $type;
    }

    public function get(string $name): ?PostType
    {
        $this->load();

        return $this->types[$name] ?? null;
    }

    /** @return array<string, PostType> */
    public function all(): array
    {
        $this->load();

        return $this->types;
    }

    public function supports(string $postType, string $capability): bool
    {
        return $this->get($postType)?->supports($capability) ?? false;
    }

    public function value(int $postId, string $field): string
    {
        $type = $this->get((string) get_post_type($postId));
        $def = $type?->fields[$field] ?? null;

        return $def ? Cast::string(get_post_meta($postId, $def->metaKey, true)) : '';
    }

    /**
     * `wpfasty_post_data` (receives the Core Post DTO): adds `fields` (name → raw string) and `terms`
     * (taxonomy → list) to the post view-model, then lets the module shape it via its `context` callable
     * (`fn (array $data, Post $post, PostType $type): array`; breaking since 2026-11: was WP_Post).
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function postData(array $data, Post $post): array
    {
        $type = $this->get($post->kind);
        if (null === $type) {
            return $data;
        }
        $data['fields'] = [];
        foreach ($type->fields as $name => $field) {
            $data['fields'][$name] = Cast::string(get_post_meta($post->id, $field->metaKey, true));
        }
        $data['terms'] = [];
        foreach (array_keys($type->taxonomies()) as $taxonomy) {
            $data['terms'][$taxonomy] = [];
            $terms = get_the_terms($post->id, (string) $taxonomy);
            foreach (is_array($terms) ? $terms : [] as $term) {
                $data['terms'][$taxonomy][] = [
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'url' => self::termUrl($term),
                ];
            }
        }
        $context = $type->def['context'] ?? null;
        if (!is_callable($context)) {
            return $data;
        }

        // module `context` callables receive the Core Post DTO (no WP_Post): ($data, Post $post, PostType $type)
        return Cast::map($context($data, $post, $type));
    }

    public function resolveView(string $view): string
    {
        foreach ($this->all() as $type) {
            $archive = $type->view('archive');
            if ($archive) {
                $taxonomies = array_keys($type->taxonomies());
                if (is_post_type_archive($type->name) || ([] !== $taxonomies && is_tax($taxonomies))) {
                    return $archive;
                }
            }
            $single = $type->view('single');
            if ($single && is_singular($type->name)) {
                return $single;
            }
        }

        return $view;
    }

    public function mainQuery(\WP_Query $query): void
    {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        foreach ($this->all() as $type) {
            $taxonomies = array_keys($type->taxonomies());
            if (!$query->is_post_type_archive($type->name) && ([] === $taxonomies || !$query->is_tax($taxonomies))) {
                continue;
            }
            $archive = $type->archive();
            if (isset($archive['per_page'])) {
                $query->set('posts_per_page', Cast::int($archive['per_page']));
            }
            if (isset($archive['orderby'])) {
                $query->set('orderby', $archive['orderby']);
            }
        }
    }

    /**
     * Module `archive.title` overrides the post type archive title.
     *
     * WordPress passes a null title (and may pass a non-string post type) through `post_type_archive_title`
     * when the post type object has no labels->name, so the filter accepts both and keeps WP's value as is.
     */
    public function archiveTitle(?string $title, mixed $postType = ''): ?string
    {
        $custom = is_string($postType) ? ($this->get($postType)?->archive()['title'] ?? null) : null;

        return is_string($custom) && '' !== $custom ? $custom : $title;
    }

    /** Module `archive.prefix` => false drops "Archives:"/"Category:" prefixes on its archives. */
    public function archiveTitlePrefix(string $prefix): string
    {
        foreach ($this->all() as $type) {
            if (($type->archive()['prefix'] ?? true) !== false) {
                continue;
            }
            $taxonomies = array_keys($type->taxonomies());
            if (is_post_type_archive($type->name) || ([] !== $taxonomies && is_tax($taxonomies))) {
                return '';
            }
        }

        return $prefix;
    }

    /**
     * Latest posts of a module type using its archive ordering (for front pages / related lists).
     *
     * @return list<\WP_Post>
     */
    public function latest(string $postType, int $count): array
    {
        $type = $this->get($postType);
        if (null === $type) {
            return [];
        }
        $orderby = $type->archive()['orderby'] ?? null;
        $posts = get_posts([
            'post_type' => $postType,
            'numberposts' => $count,
            'orderby' => is_array($orderby) ? array_map(Cast::string(...), Cast::map($orderby))
                : (is_string($orderby) && '' !== $orderby ? $orderby : ['date' => 'DESC']),
        ]);

        return array_values(array_filter($posts, static fn ($p): bool => $p instanceof \WP_Post));
    }

    /**
     * Non-empty terms of a taxonomy plus the currently queried slug (archive filter bars).
     *
     * @return array{items: list<array{name: string, slug: string, url: string}>, current: string}
     */
    public static function termFilter(string $taxonomy): array
    {
        $items = [];
        $terms = taxonomy_exists($taxonomy) ? get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]) : [];
        foreach (is_array($terms) ? $terms : [] as $term) {
            if ($term instanceof \WP_Term) {
                $items[] = ['name' => $term->name, 'slug' => $term->slug, 'url' => self::termUrl($term)];
            }
        }
        $current = is_tax($taxonomy) ? get_queried_object() : null;

        return ['items' => $items, 'current' => $current instanceof \WP_Term ? $current->slug : ''];
    }

    private static function termUrl(\WP_Term $term): string
    {
        $url = get_term_link($term);

        return is_string($url) ? $url : '';
    }
}
