<?php
/**
 * WP Fasty Storefront Child Theme functions
 */

// Отключаем стили родительской темы и подключаем свои
function wp_fasty_sf_enqueue_styles() {
    // Отключаем стили родительской темы
    //wp_dequeue_style('storefront-style');
    //wp_dequeue_style('storefront-woocommerce-style');
    //wp_dequeue_style('storefront-gutenberg-blocks');
    
    // Подключаем свои стили
    wp_enqueue_style(
        'wp-fasty-sf-style',
        get_stylesheet_directory_uri() . '/css/theme.min.css',
        array(),
        wp_get_theme()->get('Version')
    );
}
//add_action('wp_enqueue_scripts', 'wp_fasty_sf_enqueue_styles', 20);

/*// Отключаем инлайн-стили Storefront
function wp_fasty_sf_remove_inline_styles() {
    remove_action('wp_enqueue_scripts', 'storefront_add_customizer_css', 130);
}
add_action('init', 'wp_fasty_sf_remove_inline_styles');

// Отключаем инлайн-стили Gutenberg блоков
function wp_fasty_sf_remove_gutenberg_inline_styles() {
    wp_dequeue_style('storefront-gutenberg-blocks-inline');
}
add_action('wp_enqueue_scripts', 'wp_fasty_sf_remove_gutenberg_inline_styles', 20);

// Отключаем ненужные скрипты Storefront (опционально)
function wp_fasty_sf_dequeue_scripts() {
    wp_dequeue_script('storefront-header-cart');
}
add_action('wp_enqueue_scripts', 'wp_fasty_sf_dequeue_scripts', 20);*/

// Добавляем базовые классы для body
function wp_fasty_sf_body_classes($classes) {
    $classes[] = 'font-sans';
    $classes[] = 'bg-background';
    $classes[] = 'text-foreground';
    $classes[] = 'antialiased';
    return $classes;
}
//add_filter('body_class', 'wp_fasty_sf_body_classes'); 