<?php

declare(strict_types=1);

/**
 * {{theme.name}} — {{summary}}.
 *
 * @package {{packageName}}
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/inc/projects.php';

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('{{theme.textDomain}}', get_stylesheet_directory() . '/languages');
    add_image_size('folio-card', 960, 720, true);
{{#if features.fullWidth}}
    add_theme_support('wpfasty-full-width');
{{/if}}
});

add_action('after_switch_theme', static function (): void {
    flush_rewrite_rules();
});

// Post types are plugin territory (wordpress.org): they are registered by the companion plugin.
add_action('admin_notices', static function (): void {
    if (class_exists('WPFastyTypes\\Plugin') || !current_user_can('activate_plugins')) {
        return;
    }
    printf(
        '<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
        esc_html__(
            'This theme needs the "WP FastY Types" plugin (plugins/wpfasty-types) to register its content types.',
            '{{theme.textDomain}}'
        )
    );
});
