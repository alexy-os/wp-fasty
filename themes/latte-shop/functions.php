<?php

declare(strict_types=1);

/**
 * Latte Shop — WooCommerce-free shop child theme of Latte.
 *
 * @package Latte_Shop
 */

if (!defined('ABSPATH')) {
    exit;
}

foreach (['money', 'catalog', 'cart', 'orders', 'handlers', 'front'] as $file) {
    require_once __DIR__ . "/inc/{$file}.php";
}

LatteShop\Catalog::register();
LatteShop\Orders::register();
LatteShop\Handlers::register();
LatteShop\Front::register();

add_action('after_setup_theme', static function (): void {
    load_child_theme_textdomain('latte-shop', get_stylesheet_directory() . '/languages');
});
