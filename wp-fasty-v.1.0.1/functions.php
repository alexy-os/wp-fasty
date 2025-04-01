<?php
/**
 * WP FastY theme bootstrap file
 */

if (!defined('ABSPATH')) {
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

add_filter('wp_img_tag_add_auto_sizes', '__return_false');

// Retain the autoloader for proper class loading
$cached_paths = [];

spl_autoload_register(function ($class) use (&$cached_paths) {
    if (isset($cached_paths[$class])) {
        require $cached_paths[$class];
        return;
    }
    
    $prefix = 'WPFasty\\';
    $base_dir = get_template_directory() . '/src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        $cached_paths[$class] = $file;
        require $file;
    } else {
        error_log("Class file not found: " . $file);
    }
});

// Bootstrap the application
WPFasty\Core\Application::getInstance();

add_action('init', function() {
    load_theme_textdomain('wp-fasty', get_template_directory() . '/languages');
});