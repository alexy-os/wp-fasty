<?php

/**
 * Plugin Name: WP FastY QA — renderer switch
 * Description: QA/CI only (never shipped): `wp option update wpfasty_qa_renderer twig|latte` forces the theme renderer.
 *
 * @package Latte
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('wpfasty_renderer', static function (mixed $engine): mixed {
    $forced = get_option('wpfasty_qa_renderer');
    return in_array($forced, ['latte', 'twig'], true) ? $forced : $engine;
}, 99);
