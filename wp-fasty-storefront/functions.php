<?php
/**
 * WP Fasty Storefront Child Theme functions and definitions
 */

if (!defined('ABSPATH')) {
    exit;
}

// Enqueue parent and child theme styles
add_action('wp_enqueue_scripts', 'fasty_storefront_enqueue_styles');
function fasty_storefront_enqueue_styles() {
    // Get parent theme handle
    $parent_style = 'storefront-style'; 
    $parent_theme_dir = get_template_directory_uri();
    
    // First enqueue parent theme styles
    wp_enqueue_style($parent_style, $parent_theme_dir . '/style.css');
    
    // Then enqueue our generated theme styles (which should override parent styles)
    /*$theme_css_path = get_stylesheet_directory() . '/assets/css/theme.min.css';
    if (file_exists($theme_css_path)) {
        wp_enqueue_style('fasty-theme-style',
            get_stylesheet_directory_uri() . '/assets/css/theme.min.css',
            array($parent_style), 
            filemtime($theme_css_path)
        );
    }
    
    // Finally enqueue child theme's style.css for additional styles
    wp_enqueue_style('fasty-storefront-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array($parent_style, 'fasty-theme-style'), 
        wp_get_theme()->get('Version')
    );*/
}

// 1. UNIVERSAL AUTOLOADER
// ======================
if (!function_exists('fasty_autoloader')) {
    function fasty_autoloader($class) {
        // Only for FastyChild namespace
        $prefix = 'FastyChild\\';
        $len = strlen($prefix);
        
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $base_dir = get_stylesheet_directory() . '/fasty/';
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    }
    
    spl_autoload_register('fasty_autoloader');
}

// 2. FRAMEWORK INITIALIZATION
// ==========================
// Define constants for the framework
if (!defined('FASTY_CHILD_PATH')) {
    define('FASTY_CHILD_PATH', get_stylesheet_directory());
}

if (!defined('FASTY_CHILD_URI')) {
    define('FASTY_CHILD_URI', get_stylesheet_directory_uri());
}

if (!defined('FASTY_PARENT_PATH')) {
    define('FASTY_PARENT_PATH', get_template_directory());
}

if (!defined('FASTY_PARENT_URI')) {
    define('FASTY_PARENT_URI', get_template_directory_uri());
}

// Bootstrap the framework
$bootstrap_file = get_stylesheet_directory() . '/fasty/bootstrap.php';
if (file_exists($bootstrap_file)) {
    require_once $bootstrap_file;
}

/**
 * Helper function to check if a file exists and get its URI
 */
function fasty_file_exists_uri($uri, $default = '') {
    $file_path = str_replace(
        array(get_stylesheet_directory_uri(), get_template_directory_uri()),
        array(get_stylesheet_directory(), get_template_directory()),
        $uri
    );
    
    return file_exists($file_path) ? $uri : $default;
}

/**
 * Add your custom WordPress hooks below this line if you don't want to use the OOP approach
 */