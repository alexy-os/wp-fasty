<?php
/**
 * WP Fasty Storefront Child Theme functions and definitions
 */

if (!defined('ABSPATH')) {
    exit;
}

class FastyThemeBootstrap {
    private static $instance = null;
    private static $textdomain = 'wp-fasty-storefront';
    
    private function __construct() {
        $this->defineConstants();
        $this->registerAutoloader();
        $this->registerHooks();
        $this->bootstrapFramework();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function textdomain() {
        if (self::$textdomain === null) {
            self::$textdomain = 'wp-fasty-child';
        }
        return self::$textdomain;
    }
    
    private function defineConstants() {
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
        if (!defined('FASTY_TEXTDOMAIN')) {
            define('FASTY_TEXTDOMAIN', $this->textdomain());
        }
    }
    
    private function registerAutoloader() {
        spl_autoload_register(function($class) {
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
        });
    }
    
    private function registerHooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
    }
    
    public function enqueueStyles() {
        $parent_style = 'storefront-style';
        $parent_theme_dir = get_template_directory_uri();
        wp_enqueue_style($parent_style, $parent_theme_dir . '/style.css');
    }
    
    private function bootstrapFramework() {
        $bootstrap_file = get_stylesheet_directory() . '/fasty/bootstrap.php';
        if (file_exists($bootstrap_file)) {
            require_once $bootstrap_file;
        }
    }
    
    public static function fileExistsUri($uri, $default = '') {
        $file_path = str_replace(
            array(get_stylesheet_directory_uri(), get_template_directory_uri()),
            array(get_stylesheet_directory(), get_template_directory()),
            $uri
        );
        
        return file_exists($file_path) ? $uri : $default;
    }
}

// Initialize the theme
FastyThemeBootstrap::getInstance();

/**
 * Add your custom WordPress hooks below this line if you don't want to use the OOP approach
 */