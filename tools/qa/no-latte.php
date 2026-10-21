<?php

/**
 * Plugin Name: WP FastY QA — Latte-loaded marker
 * Description: QA/CI only (never shipped): appends `<!-- wpfasty-qa: latte-loaded -->` to a front-end response when any
 *              `Latte\` class was loaded during the request. tools/qa/no-latte.sh fails on it under the Twig renderer.
 *
 * @package Latte
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('shutdown', static function (): void {
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || (defined('WP_CLI') && WP_CLI)) {
        return;
    }
    foreach (array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits()) as $name) {
        if (str_starts_with($name, 'Latte\\')) {
            echo "\n<!-- wpfasty-qa: latte-loaded -->\n";
            return;
        }
    }
}, PHP_INT_MAX);
