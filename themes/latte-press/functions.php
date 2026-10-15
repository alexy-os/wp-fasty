<?php

declare(strict_types=1);

/**
 * Latte Press — classic blog child theme of Latte.
 *
 * @package Latte_Press
 */

if (!defined('ABSPATH')) {
    exit;
}

// wordpress.org-style blog: threaded comments on.
add_filter('wpfasty_comments_enabled', '__return_true');

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('latte-press', get_stylesheet_directory() . '/languages');
    add_theme_support('custom-background', ['default-color' => 'fcfbf8']);
});

add_action('wp_enqueue_scripts', static function (): void {
    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
});

/**
 * Blog index shows a friendly heading instead of the bare "Archives".
 *
 * @param array<string, mixed> $context
 * @return array<string, mixed>
 */
add_filter('wpfasty_context', static function (array $context): array {
    if (is_home() && isset($context['archive']) && is_array($context['archive'])) {
        $context['archive']['title'] = get_bloginfo('name');
        $context['archive']['description'] = esc_html(get_bloginfo('description'));
    }
    $context['labels']['read_more'] = __('Continue reading', 'latte-press');
    return $context;
});
