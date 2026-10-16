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
        add_action('init', [self::class, 'registerCurrency']);
        if (is_admin()) {
            add_action('admin_init', [self::class, 'currencyField']);
            add_action('add_meta_boxes', [$plugin, 'metaBoxes']);
            add_action('save_post', [$plugin, 'save'], 10, 2);
            add_action('admin_init', [$plugin, 'columns']);
        }
    }

    /** Store currency (ISO 4217) for `money` fields, which hold integer minor units. Settings → General; REST /wp/v2/settings. */
    public static function registerCurrency(): void
    {
        register_setting('general', 'wpfasty_currency', [
            'type' => 'string',
            'default' => 'USD',
            'show_in_rest' => ['schema' => ['pattern' => '^[A-Z]{3}$']],
            'sanitize_callback' => [self::class, 'sanitizeCurrency'],
            'description' => __('Currency of money fields (ISO 4217 code, e.g. USD, EUR).', 'wpfasty-types'),
        ]);
    }

    public static function sanitizeCurrency(mixed $value): string
    {
        $code = strtoupper(trim(is_scalar($value) ? (string) $value : ''));
        return preg_match('/^[A-Z]{3}$/', $code) === 1 ? $code : 'USD';
    }

    public static function currencyField(): void
    {
        add_settings_field('wpfasty_currency', __('Store currency', 'wpfasty-types'), static function (): void {
            printf(
                '<input name="wpfasty_currency" id="wpfasty_currency" type="text" maxlength="3" class="small-text" value="%s"> <span class="description">%s</span>',
                esc_attr((string) get_option('wpfasty_currency', 'USD')),
                esc_html__('ISO 4217 code, e.g. USD, EUR.', 'wpfasty-types')
            );
        }, 'general');
    }

    public function register(): void
    {
        foreach ($this->types->all() as $type) {
            register_post_type($type->name, $type->args());
            foreach ($type->taxonomies() as $taxonomy => $args) {
                register_taxonomy((string) $taxonomy, $type->name, (array) $args);
            }
            if ($type->fields !== []) {
                // REST exposes `meta` only for post types that support custom-fields (keys are `_`-prefixed, so the core box hides them).
                add_post_type_support($type->name, 'custom-fields');
            }
            if ($type->fields !== []) {
                add_filter('rest_prepare_' . $type->name, [$this, 'restNullMeta'], 10, 2);
            }
            foreach ($type->fields as $field) {
                register_post_meta($type->name, $field->metaKey, [
                    'type' => $field->metaType(),
                    'single' => true,
                    // Typed values in REST (money = integer minor units). Opt out per field with 'show_in_rest' => false.
                    'show_in_rest' => (bool) ($field->def['show_in_rest'] ?? true),
                    'sanitize_callback' => [$field, 'toStorage'],
                    'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
                ]);
            }
        }
    }

    /**
     * REST would show a missing integer/number/boolean meta as 0/false; send null instead, so "not set"
     * (e.g. stock = unlimited) differs from 0.
     */
    public function restNullMeta(\WP_REST_Response $response, \WP_Post $post): \WP_REST_Response
    {
        $data = $response->get_data();
        $type = $this->types->get($post->post_type);
        if ($type === null || !isset($data['meta']) || !is_array($data['meta'])) {
            return $response;
        }
        foreach ($type->fields as $field) {
            if (array_key_exists($field->metaKey, $data['meta']) && !metadata_exists('post', $post->ID, $field->metaKey)) {
                $data['meta'][$field->metaKey] = null;
            }
        }
        $response->set_data($data);
        return $response;
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
            $value = $field->toInput(get_post_meta($post->ID, $field->metaKey, true));
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
            $value = $field->fromInput($input[$name] ?? '');
            if ($value === null) {
                delete_post_meta($postId, $field->metaKey);
            } else {
                update_post_meta($postId, $field->metaKey, $value);
            }
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
