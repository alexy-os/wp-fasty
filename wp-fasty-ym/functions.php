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

// Custom SVG favicon
function wpfasty_custom_favicon() {
    // Отключаем стандартную иконку сайта WordPress
    remove_action('wp_head', 'wp_site_icon', 99);
    
    // Добавляем свой SVG фавикон
    echo '<link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NyA0MCIgZmlsbD0iIzBlYTVlOSI+DQogICAgPHBhdGggZD0iTTIzLjUgNi41QzE3LjUgNi41IDEzLjc1IDkuNSAxMi4yNSAxNS41QzE0LjUgMTIuNSAxNy4xMjUgMTEuMzc1IDIwLjEyNSAxMi4xMjVDMjEuODM2NyAxMi41NTI5IDIzLjA2MDEgMTMuNzk0NyAyNC40MTQyIDE1LjE2OTJDMjYuNjIwMiAxNy40MDg0IDI5LjE3MzQgMjAgMzQuNzUgMjBDNDAuNzUgMjAgNDQuNSAxNyA0NiAxMUM0My43NSAxNCA0MS4xMjUgMTUuMTI1IDM4LjEyNSAxNC4zNzVDMzYuNDEzMyAxMy45NDcxIDM1LjE4OTkgMTIuNzA1MyAzMy44MzU3IDExLjMzMDhDMzEuNjI5NyA5LjA5MTU4IDI5LjA3NjYgNi41IDIzLjUgNi41Wk0xMi4yNSAyMEM2LjI1IDIwIDIuNSAyMyAxIDI5QzMuMjUgMjYgNS44NzUgMjQuODc1IDguODc1IDI1LjYyNUMxMC41ODY3IDI2LjA1MjkgMTEuODEwMSAyNy4yOTQ3IDEzLjE2NDIgMjguNjY5M0MxNS4zNzAyIDMwLjkwODQgMTcuOTIzNCAzMy41IDIzLjUgMzMuNUMyOS41IDMzLjUgMzMuMjUgMzAuNSAzNC43NSAyNC41QzMyLjUgMjcuNSAyOS44NzUgMjguNjI1IDI2Ljg3NSAyNy44NzVDMjUuMTYzMyAyNy40NDcxIDIzLjkzOTkgMjYuMjA1MyAyMi41ODU4IDI0LjgzMDdDMjAuMzc5OCAyMi41OTE2IDE3LjgyNjYgMjAgMTIuMjUgMjBaIj48L3BhdGg+DQo8L3N2Zz4=" />' . "\n";
}
add_action('wp_head', 'wpfasty_custom_favicon', 2);

// Альтернативный способ через фильтр site_icon_meta_tags
function wpfasty_replace_site_icon($meta_tags) {
    // Заменяем все стандартные теги иконок на наш SVG фавикон
    return '<link rel="icon" type="image/svg+xml" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0NyA0MCIgZmlsbD0iIzBlYTVlOSI+DQogICAgPHBhdGggZD0iTTIzLjUgNi41QzE3LjUgNi41IDEzLjc1IDkuNSAxMi4yNSAxNS41QzE0LjUgMTIuNSAxNy4xMjUgMTEuMzc1IDIwLjEyNSAxMi4xMjVDMjEuODM2NyAxMi41NTI5IDIzLjA2MDEgMTMuNzk0NyAyNC40MTQyIDE1LjE2OTJDMjYuNjIwMiAxNy40MDg0IDI5LjE3MzQgMjAgMzQuNzUgMjBDNDAuNzUgMjAgNDQuNSAxNyA0NiAxMUM0My43NSAxNCA0MS4xMjUgMTUuMTI1IDM4LjEyNSAxNC4zNzVDMzYuNDEzMyAxMy45NDcxIDM1LjE4OTkgMTIuNzA1MyAzMy44MzU3IDExLjMzMDhDMzEuNjI5NyA5LjA5MTU4IDI5LjA3NjYgNi41IDIzLjUgNi41Wk0xMi4yNSAyMEM2LjI1IDIwIDIuNSAyMyAxIDI5QzMuMjUgMjYgNS44NzUgMjQuODc1IDguODc1IDI1LjYyNUMxMC41ODY3IDI2LjA1MjkgMTEuODEwMSAyNy4yOTQ3IDEzLjE2NDIgMjguNjY5M0MxNS4zNzAyIDMwLjkwODQgMTcuOTIzNCAzMy41IDIzLjUgMzMuNUMyOS41IDMzLjUgMzMuMjUgMzAuNSAzNC43NSAyNC41QzMyLjUgMjcuNSAyOS44NzUgMjguNjI1IDI2Ljg3NSAyNy44NzVDMjUuMTYzMyAyNy40NDcxIDIzLjkzOTkgMjYuMjA1MyAyMi41ODU4IDI0LjgzMDdDMjAuMzc5OCAyMi41OTE2IDE3LjgyNjYgMjAgMTIuMjUgMjBaIj48L3BhdGg+DQo8L3N2Zz4=" />';
}
add_filter('site_icon_meta_tags', 'wpfasty_replace_site_icon');

// Отключаем стандартную поддержку site-icon, чтобы в админке не предлагалось загружать иконку
function wpfasty_theme_setup() {
    remove_theme_support('site-icon');
}
add_action('after_setup_theme', 'wpfasty_theme_setup');

/**
 * Оптимизация производительности WordPress
 */

// Кэширование опций из wp_options
function wpfasty_get_option($option_name, $default = false) {
    static $options_cache = array();
    
    if (isset($options_cache[$option_name])) {
        return $options_cache[$option_name];
    }
    
    $value = get_option($option_name, $default);
    $options_cache[$option_name] = $value;
    
    return $value;
}

// Оптимизация запросов к данным пользователя
function wpfasty_get_user_data($user_id, $fields = 'all') {
    static $users_cache = array();
    $cache_key = $user_id . '_' . (is_array($fields) ? implode('_', $fields) : $fields);
    
    if (isset($users_cache[$cache_key])) {
        return $users_cache[$cache_key];
    }
    
    $user_data = get_userdata($user_id);
    $users_cache[$cache_key] = $user_data;
    
    return $user_data;
}

// Оптимизация запросов дочерних страниц
function wpfasty_get_child_pages($parent_id, $args = array()) {
    static $children_cache = array();
    $cache_key = $parent_id . '_' . md5(serialize($args));
    
    if (isset($children_cache[$cache_key])) {
        return $children_cache[$cache_key];
    }
    
    $default_args = array(
        'post_parent' => $parent_id,
        'post_type' => 'page',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    $args = wp_parse_args($args, $default_args);
    $children = get_posts($args);
    $children_cache[$cache_key] = $children;
    
    return $children;
}

// Уменьшение количества запросов
add_action('init', function() {
    // Отключаем эмодзи
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    
    // Отключаем генерацию oEmbed
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
    
    // Отключаем REST API, если не используется в теме
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);
    
    // Отключаем генерацию лишних тегов и версий
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
}, 1);

// Оптимизация загрузки скриптов и стилей
add_action('wp_enqueue_scripts', function() {
    // Перемещаем jQuery в footer для ускорения загрузки страницы
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_register_script('jquery', includes_url('/js/jquery/jquery.min.js'), false, null, true);
        wp_enqueue_script('jquery');
    }
    
    // Отключаем wp-embed.min.js
    wp_deregister_script('wp-embed');
}, 100);

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

