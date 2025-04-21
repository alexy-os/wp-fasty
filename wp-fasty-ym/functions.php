<?php
/**
 * WP Fasty Theme functions and definitions
 *
 * @package WPFasty
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Error reporting for development
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

add_filter('wp_img_tag_add_auto_sizes', '__return_false');

// Load Composer autoloader if it exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback autoloader for when Composer is not available
    spl_autoload_register(function ($class) {
        // Base namespace and directory
        $prefix = 'WPFasty\\';
        $base_dir = __DIR__ . '/classes/';
        
        // Check if the class uses our namespace
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        // Get the relative class name
        $relative_class = substr($class, $len);
        
        // Replace namespace separators with directory separators
        // and append .php
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    });
    
    // Include helper functions
    if (file_exists(__DIR__ . '/includes/helpers.php')) {
        require_once __DIR__ . '/includes/helpers.php';
    }
}

// Bootstrap the application
if (class_exists('WPFasty\\Core\\Application')) {
    WPFasty\Core\Application::getInstance();
}

// Loading the text domain
add_action('init', function() {
    load_theme_textdomain('wp-fasty-ym', get_stylesheet_directory() . '/languages');
});

