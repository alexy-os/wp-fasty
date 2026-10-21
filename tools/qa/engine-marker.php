<?php

/**
 * Plugin Name: WP FastY QA — engine class markers
 * Description: QA/CI only (never shipped): appends `<!-- wpfasty-qa: loaded latte|twig -->` to a front-end response for
 *              every template library (`Latte\`, `Twig\`) whose classes were loaded during the request.
 *              tools/qa/site-pair.sh requires exactly the site engine's marker (ADR-001, K7: isolated runtimes).
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
    $loaded = [];
    foreach (array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits()) as $name) {
        foreach (['Latte\\' => 'latte', 'Twig\\' => 'twig'] as $prefix => $engine) {
            if (str_starts_with($name, $prefix)) {
                $loaded[$engine] = true;
            }
        }
    }
    foreach (array_keys($loaded) as $engine) {
        echo "\n<!-- wpfasty-qa: loaded " . esc_html($engine) . " -->\n";
    }
}, PHP_INT_MAX);
