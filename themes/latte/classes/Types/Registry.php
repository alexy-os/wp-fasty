<?php
/**
 * Declarative post-type modules (ADR-001 §3).
 *
 * Child themes drop `types/<name>.php` returning an array. The theme side (this class)
 * only does presentation: view routing, main-query tuning, archive titles and post
 * view-models. Registration of post types/taxonomies/meta and the admin metabox lives in
 * the companion plugin `plugins/wpfasty-types` (wordpress.org: plugin territory, ADR-001 §3a),
 * which receives this registry via the `wpfasty_types_loaded` action.
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Types;

use WPFasty\Core\BootableServiceInterface;

final class Registry implements BootableServiceInterface
{
    /** @var array<string, PostType> */
    private array $types = [];

    private bool $loaded = false;

    /**
     * @param list<string> $dirs Directories scanned for `*.php` modules.
     */
    public function __construct(private array $dirs = [])
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
        foreach (array_unique($dirs) as $dir) {
            foreach (glob(rtrim((string) $dir, '/\\') . '/*.php') ?: [] as $file) {
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
            $this->add(PostType::fromArray((array) $def));
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
        return $def ? (string) get_post_meta($postId, $def->metaKey, true) : '';
    }

    /**
     * Adds `fields` (name → raw string) and `terms` (taxonomy → list) to the post view-model,
     * then lets the module shape it via its `context` callable.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function postData(array $data, \WP_Post $post): array
    {
        $type = $this->get($post->post_type);
        if ($type === null) {
            return $data;
        }
        $data['fields'] = [];
        foreach ($type->fields as $name => $field) {
            $data['fields'][$name] = (string) get_post_meta($post->ID, $field->metaKey, true);
        }
        $data['terms'] = [];
        foreach (array_keys($type->taxonomies()) as $taxonomy) {
            $data['terms'][$taxonomy] = [];
            foreach (get_the_terms($post, (string) $taxonomy) ?: [] as $term) {
                $data['terms'][$taxonomy][] = [
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'url' => (string) get_term_link($term),
                ];
            }
        }
        $context = $type->def['context'] ?? null;
        return is_callable($context) ? (array) $context($data, $post, $type) : $data;
    }

    public function resolveView(string $view): string
    {
        foreach ($this->all() as $type) {
            $archive = $type->view('archive');
            if ($archive) {
                $taxonomies = array_keys($type->taxonomies());
                if (is_post_type_archive($type->name) || ($taxonomies !== [] && is_tax($taxonomies))) {
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
            if (!$query->is_post_type_archive($type->name) && ($taxonomies === [] || !$query->is_tax($taxonomies))) {
                continue;
            }
            $archive = $type->archive();
            if (isset($archive['per_page'])) {
                $query->set('posts_per_page', (int) $archive['per_page']);
            }
            if (isset($archive['orderby'])) {
                $query->set('orderby', $archive['orderby']);
            }
        }
    }

    /** Module `archive.title` overrides the post type archive title. */
    public function archiveTitle(string $title, string $postType): string
    {
        $custom = $this->get($postType)?->archive()['title'] ?? null;
        return is_string($custom) && $custom !== '' ? $custom : $title;
    }

    /** Module `archive.prefix` => false drops "Archives:"/"Category:" prefixes on its archives. */
    public function archiveTitlePrefix(string $prefix): string
    {
        foreach ($this->all() as $type) {
            if (($type->archive()['prefix'] ?? true) !== false) {
                continue;
            }
            $taxonomies = array_keys($type->taxonomies());
            if (is_post_type_archive($type->name) || ($taxonomies !== [] && is_tax($taxonomies))) {
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
        if ($type === null) {
            return [];
        }
        $posts = get_posts([
            'post_type' => $postType,
            'numberposts' => $count,
            'orderby' => $type->archive()['orderby'] ?? ['date' => 'DESC'],
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
        foreach (get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]) ?: [] as $term) {
            if ($term instanceof \WP_Term) {
                $items[] = ['name' => $term->name, 'slug' => $term->slug, 'url' => (string) get_term_link($term)];
            }
        }
        $current = is_tax($taxonomy) ? get_queried_object() : null;
        return ['items' => $items, 'current' => $current instanceof \WP_Term ? $current->slug : ''];
    }
}
