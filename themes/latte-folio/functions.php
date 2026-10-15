<?php

declare(strict_types=1);

/**
 * Latte Folio — portfolio child theme of Latte.
 *
 * @package Latte_Folio
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/inc/projects.php';

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('latte-folio', get_stylesheet_directory() . '/languages');
    add_image_size('folio-card', 960, 720, true);
});

add_action('after_switch_theme', static function (): void {
    flush_rewrite_rules();
});

add_filter('get_the_archive_title_prefix', '__return_empty_string');
add_filter('post_type_archive_title', static fn (string $title, string $type): string
    => $type === 'project' ? __('Work', 'latte-folio') : $title, 10, 2);
