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
{{#if features.comments}}

// wordpress.org-style blog: threaded comments on.
add_filter('wpfasty_comments_enabled', '__return_true');
{{/if}}

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('{{theme.textDomain}}', get_stylesheet_directory() . '/languages');
{{#if backgroundHex}}
    add_theme_support('custom-background', ['default-color' => '{{backgroundHex}}']);
{{/if}}
{{#if features.fullWidth}}
    add_theme_support('wpfasty-full-width');
{{/if}}
});
{{#if features.comments}}

add_action('wp_enqueue_scripts', static function (): void {
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
});
{{/if}}
{{#if contextFilter}}

/**
{{#if blogIndexSite}}
 * Blog index shows a friendly heading instead of the bare "Archives".
{{/if}}
{{#unless blogIndexSite}}
 * UI copy overrides.
{{/unless}}
 *
 * @param array<string, mixed> $context
 * @return array<string, mixed>
 */
add_filter('wpfasty_context', static function (array $context): array {
{{#if blogIndexSite}}
    if (is_home() && isset($context['archive']) && is_array($context['archive'])) {
        $context['archive']['title'] = get_bloginfo('name');
        $context['archive']['description'] = esc_html(get_bloginfo('description'));
    }
{{/if}}
{{#if readMorePhp}}
    $context['labels']['read_more'] = __('{{readMorePhp}}', '{{theme.textDomain}}');
{{/if}}
    return $context;
});
{{/if}}
