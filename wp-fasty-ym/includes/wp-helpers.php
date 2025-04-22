<?php
declare(strict_types=1);

/**
 * Helper functions for WP FastY theme
 * 
 * These functions are loaded only in WordPress context
 */

// Run only in WordPress context
if (function_exists('add_filter')) {
    // Adding basic classes for the body
    function wp_fasty_neve_body_classes(array $classes) {
        $classes[] = 'font-sans';
        $classes[] = 'bg-background';
        $classes[] = 'text-foreground';
        $classes[] = 'antialiased';
        return $classes;
    }
    add_filter('body_class', 'wp_fasty_neve_body_classes');
}

add_filter('wp_img_tag_add_auto_sizes', '__return_false');

/**
 * Добавляет кастомный SVG фавикон
 *
 */
function wpfasty_custom_favicon(): void
{
    // Отключаем стандартную иконку сайта WordPress
    remove_action('wp_head', 'wp_site_icon', 99);

    // Добавляем свой SVG фавикон
    echo '<link rel="icon" type="image/svg+xml" href="/wp-content/themes/wp-fasty-ym/assets/images/favicon.svg" />' . "\n";
}

add_action('wp_head', 'wpfasty_custom_favicon', 2);

/**
 * Заменяет стандартные теги иконок на кастомный SVG фавикон
 *
 * @param array<string> $meta_tags Стандартные теги мета-иконок WordPress
 * @return string Замененный тег иконки
 */
function wpfasty_replace_site_icon(array $meta_tags): string
{
    // Заменяем все стандартные теги иконок на наш SVG фавикон
    return '<link rel="icon" type="image/svg+xml" href="/wp-content/themes/wp-fasty-ym/assets/images/favicon.svg" />';
}

add_filter('site_icon_meta_tags', 'wpfasty_replace_site_icon');

// Отключаем стандартную поддержку site-icon, чтобы в админке не предлагалось загружать иконку
function wpfasty_theme_setup(): void
{
    remove_theme_support('site-icon');
}

add_action('after_setup_theme', 'wpfasty_theme_setup');

/**
 * Оптимизация производительности WordPress
 */

// Кэширование опций из wp_options
function wpfasty_get_option($option_name, $default = false)
{
    static $options_cache = array();

    if (isset($options_cache[$option_name])) {
        return $options_cache[$option_name];
    }

    $value = get_option($option_name, $default);
    $options_cache[$option_name] = $value;

    return $value;
}

// Оптимизация запросов к данным пользователя
function wpfasty_get_user_data($user_id, $fields = 'all')
{
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
function wpfasty_get_child_pages($parent_id, $args = array())
{
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
add_action('init', function (): void {
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
add_action('wp_enqueue_scripts', function (): void {
    // Перемещаем jQuery в footer для ускорения загрузки страницы
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_register_script('jquery', includes_url('/js/jquery/jquery.min.js'), false, null, true);
        wp_enqueue_script('jquery');
    }

    // Отключаем wp-embed.min.js
    wp_deregister_script('wp-embed');
}, 100);