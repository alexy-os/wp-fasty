<?php
/**
 * Plugin Name:       WP FastY Builder
 * Description:       Drag-and-drop page builder for WP FastY Latte themes, in wp-admin only. Pages are saved
 *                    as plain Latte templates for the theme pipeline; no builder script on the front end.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Author:            alexy-os
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpfasty-builder
 *
 * @package WPFasty_Builder
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 15)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

WPFastyBuilder\Plugin::boot(__FILE__);
