<?php
/**
 * WP FastY Types — data registration for declarative post-type modules.
 *
 * @package WPFasty_Types
 */

declare(strict_types=1);

namespace WPFastyTypes;

use WPFasty\Types\PostType;
use WPFasty\Types\Registry;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data side of the module system; the theme's Registry hands itself over on `wpfasty_types_loaded`.
 */
final class Plugin
{
    public function __construct(private readonly Registry $types)
    {
    }

    public static function attach(Registry $types): void
    {
        $plugin = new self($types);
        add_action('init', [$plugin, 'register']);
        if (is_admin()) {
            add_action('add_meta_boxes', [$plugin, 'metaBoxes']);
            add_action('save_post', [$plugin, 'save'], 10, 2);
            add_action('admin_init', [$plugin, 'columns']);
        }
    }

    public function register(): void
    {
        foreach ($this->types->all() as $type) {
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

    public function metaBoxes(string $postType): void
    {
        $type = $this->types->get($postType);
        $box = $type?->metabox();
        if ($type === null || $box === false || $type->fields === []) {
            return;
        }
        add_meta_box(
            'wpfasty-' . $type->name . '-fields',
            (string) ($box['title'] ?? __('Details', 'wpfasty-types')),
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
        $type = $this->types->get($post->post_type);
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
        foreach ($this->types->all() as $type) {
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
