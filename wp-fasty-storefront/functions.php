<?php
/**
 * WP Fasty Storefront Child Theme functions and definitions
 */

add_action('wp_enqueue_scripts', 'wp_fasty_storefront_child_enqueue_styles');
function wp_fasty_storefront_child_enqueue_styles() {
    $parent_style = 'storefront-style';
    
    // Enqueue parent theme's stylesheet
    wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css');
    
    // Enqueue child theme's stylesheet
    wp_enqueue_style('wp-fasty-storefront-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array($parent_style),
        wp_get_theme()->get('Version')
    );
}

/**
 * Add your custom functions below this line
 */ 