<?php
/**
 * Declarative post-type modules (ADR-001 §3).
 *
 * Child themes (or a companion plugin) drop `types/<name>.php` returning an array;
 * this registry registers the CPT, taxonomies and typed/sanitized meta, generates the
 * admin metabox (nonce + capability), routes views, tunes the main query and enriches
 * post view-models. Kept free of renderer dependencies so it can be shipped as a plugin
 * for wordpress.org builds (ADR-001 §3a).
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
        add_action('init', [$this, 'register']);
        add_filter('wpfasty_post_data', [$this, 'postData'], 5, 2);
        add_filter('wpfasty_resolve_view', [$this, 'resolveView'], 5);
        add_action('pre_get_posts', [$this, 'mainQuery']);
        if (is_admin()) {
            add_action('add_meta_boxes', [$this, 'metaBoxes']);
            add_action('save_post', [$this, 'save'], 10, 2);
            add_action('admin_init', [$this, 'columns']);
        }
    }

    /** Loads module files once (dirs filter: `wpfasty_type_dirs`, arrays: `wpfasty_types`). */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }
        $this->loaded = true;
        $dirs = function_exists('apply_filters') ? (array) apply_filters('wpfasty_type_dirs', $this->dirs) : $this->dirs;
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

    public function register(): void
    {
        foreach ($this->all() as $type) {
            register_post_type($type->name, $type->args());
            foreach ($type->taxonomies() as $taxonomy => $args) {
                register_taxonomy((string) $taxonomy, $type->name, (array) $args);
            }
            foreach ($type->fields as $field) {
                register_post_meta($type->name, $field->metaKey, [
                    'type' => 'string',
                    'single' => true,
                    'show_in_rest' => (bool) ($field->def['show_in_rest'] ?? false),
                    'sanitize_callback' => [$field, 'sanitize'],
                    'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
                ]);
            }
        }
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
                $data['terms'][$taxonomy][] = ['name' => $term->name, 'slug' => $term->slug, 'url' => (string) get_term_link($term)];
            }
        }
        $context = $type->def['context'] ?? null;
        return is_callable($context) ? (array) $context($data, $post, $type) : $data;
    }

    public function resolveView(string $view): string
    {
        foreach ($this->all() as $type) {
            $archive = $type->view('archive');
            if ($archive && (is_post_type_archive($type->name) || ($type->taxonomies() !== [] && is_tax(array_keys($type->taxonomies()))))) {
                return $archive;
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

    public function metaBoxes(string $postType): void
    {
        $type = $this->get($postType);
        $box = $type?->metabox();
        if ($type === null || $box === false || $type->fields === []) {
            return;
        }
        add_meta_box(
            'wpfasty-' . $type->name . '-fields',
            (string) ($box['title'] ?? __('Details', 'latte')),
            fn (\WP_Post $post) => $this->renderMetaBox($type, $post),
            $type->name,
            (string) ($box['context'] ?? 'side'),
            (string) ($box['priority'] ?? 'default')
        );
    }

    private function renderMetaBox(PostType $type, \WP_Post $post): void
    {
        wp_nonce_field('wpfasty_save_' . $type->name, 'wpfasty_' . $type->name . '_nonce');
        foreach ($type->fields as $name => $field) {
            $value = (string) get_post_meta($post->ID, $field->metaKey, true);
            $id = 'wpfasty-' . $type->name . '-' . $name;
            $input = $field->inputType();
            if ($input === 'checkbox') {
                printf(
                    '<p><label for="%1$s"><input type="checkbox" id="%1$s" name="wpfasty_fields[%2$s]" value="1"%3$s> %4$s</label></p>',
                    esc_attr($id),
                    esc_attr($name),
                    checked($value, '1', false),
                    esc_html($field->label)
                );
                continue;
            }
            $extra = $input === 'number' ? ' min="0" step="' . esc_attr($field->type === 'integer' ? '1' : '0.01') . '"' : '';
            printf(
                '<p><label for="%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="%3$s"%4$s id="%1$s" name="wpfasty_fields[%5$s]" value="%6$s"></p>',
                esc_attr($id),
                esc_html($field->label),
                esc_attr($input),
                $extra, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr above.
                esc_attr($name),
                esc_attr($value)
            );
        }
    }

    public function save(int $postId, \WP_Post $post): void
    {
        $type = $this->get($post->post_type);
        if ($type === null || $type->fields === [] || $type->metabox() === false) {
            return;
        }
        $key = 'wpfasty_' . $type->name . '_nonce';
        $nonce = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
        if (!wp_verify_nonce($nonce, 'wpfasty_save_' . $type->name)
            || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || !current_user_can('edit_post', $postId)
        ) {
            return;
        }
        // Each value is sanitized per field type below.
        $input = isset($_POST['wpfasty_fields']) && is_array($_POST['wpfasty_fields'])
            ? wp_unslash($_POST['wpfasty_fields']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            : [];
        foreach ($type->fields as $name => $field) {
            update_post_meta($postId, $field->metaKey, $field->sanitize($input[$name] ?? ''));
        }
    }

    /** Admin list columns: module `columns` => [key => ['label' => string, 'value' => callable(int): string]]. */
    public function columns(): void
    {
        foreach ($this->all() as $type) {
            $columns = (array) ($type->def['columns'] ?? []);
            if ($columns === []) {
                continue;
            }
            add_filter('manage_' . $type->name . '_posts_columns', static function (array $c) use ($columns): array {
                $extra = [];
                foreach ($columns as $key => $col) {
                    $extra[(string) $key] = (string) ($col['label'] ?? $key);
                }
                return array_slice($c, 0, 2, true) + $extra + $c;
            });
            add_action('manage_' . $type->name . '_posts_custom_column', static function (string $col, int $id) use ($columns): void {
                if (isset($columns[$col]['value']) && is_callable($columns[$col]['value'])) {
                    echo esc_html((string) $columns[$col]['value']($id));
                }
            }, 10, 2);
        }
    }
}
