<?php
declare(strict_types=1);

/**
 * Helper functions for WP FastY theme
 * 
 * These functions are loaded only in WordPress context
 */

// Run only in WordPress context
if (function_exists('add_filter')) {
    // Adding basic classes for the body and removing incorrect theme class
    function wp_fasty_body_classes(array $classes) {
        // Adding necessary classes
        $classes[] = 'font-sans';
        $classes[] = 'bg-background';
        $classes[] = 'text-foreground';
        $classes[] = 'antialiased';
        
        // Adding correct theme class
        $classes[] = 'wp-fasty';
        
        return $classes;
    }
    add_filter('body_class', 'wp_fasty_body_classes');
}

/**
 * Function to clean body classes from incorrect theme classes
 * Runs after all other filters to remove duplicate classes
 */
function wpfasty_cleanup_body_classes(array $classes): array {
    // Find and remove all classes containing the theme path wp-theme-wp-fasty
    $classes = array_filter($classes, function($class) {
        // Remove classes with incorrect theme path
        return strpos($class, 'wp-theme-wp-fasty') === false;
    });
    
    // Remove any duplicates that may have appeared from other filters
    $classes = array_unique($classes);
    
    return $classes;
}
// Add a high priority filter to ensure it runs after all other filters
add_filter('body_class', 'wpfasty_cleanup_body_classes', 999);

add_filter('wp_img_tag_add_auto_sizes', '__return_false');

/**
 * Adds a custom SVG favicon
 *
 */
/*function wpfasty_custom_favicon(): void
{
    // Disable the default WordPress site icon
    remove_action('wp_head', 'wp_site_icon', 99);

    // Add our SVG favicon
    echo '<link rel="icon" type="image/svg+xml" href="/wp-content/themes/wp-fasty-ym/assets/images/favicon.svg" />' . "\n";
}

add_action('wp_head', 'wpfasty_custom_favicon', 2);*/

/**
 * Replaces the default icon tags with our custom SVG favicon
 *
 * @param array<string> $meta_tags Standard WordPress icon meta tags
 * @return array<string> Modified array of icon tags
 */
/*function wpfasty_replace_site_icon(array $meta_tags): array
{
    // Replace all default icon tags with our custom SVG favicon
    return ['<link rel="icon" type="image/svg+xml" href="/wp-content/themes/wp-fasty-ym/assets/images/favicon.svg" />'];
}

add_filter('site_icon_meta_tags', 'wpfasty_replace_site_icon');*/

// Disable the default site-icon support, so it doesn't suggest uploading an icon in the admin
/*function wpfasty_theme_setup(): void
{
    remove_theme_support('site-icon');
}

add_action('after_setup_theme', 'wpfasty_theme_setup');*/

add_filter( 'get_site_icon_url', '__return_false' );

/**
 * WordPress performance optimization
 */

// Pre-load commonly used options during init to reduce DB queries
add_action('init', function (): void {
    // Only run on frontend
    if (is_admin()) {
        return;
    }
    
    // Load all options in a single query instead of multiple individual ones
    $all_options = wp_load_alloptions();
    
    // Cache all loaded options in our cache
    global $wpfasty_options_cache;
    if (!is_array($wpfasty_options_cache)) {
        $wpfasty_options_cache = [];
    }
    
    // Copy all options to our cache
    $wpfasty_options_cache = array_merge($wpfasty_options_cache, $all_options);
    
    // Now we can use wpfasty_get_option without accessing the database
    
    // Preload site logo
    wpfasty_get_site_logo();
    
    // Preload common page hierarchies
    // This will prevent individual queries for common parent pages
    global $wpfasty_page_hierarchy_cache;
    
    if (!is_array($wpfasty_page_hierarchy_cache)) {
        $wpfasty_page_hierarchy_cache = [];
        
        // Get all pages in one query to build a complete hierarchy
        $all_pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
        
        // Build hierarchy structure
        foreach ($all_pages as $page) {
            $parent_id = $page->post_parent;
            
            if (!isset($wpfasty_page_hierarchy_cache[$parent_id])) {
                $wpfasty_page_hierarchy_cache[$parent_id] = [];
            }
            
            $wpfasty_page_hierarchy_cache[$parent_id][] = $page;
        }
    }
    
    // Optimize WordPress queries
    // Disable emojis
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

    // Disable oEmbed generation
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');

    // Disable REST API, if not used in the theme
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);

    // Disable generating extra tags and versions
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
}, 1);

/**
 * Override wpfasty_get_option to use preloaded options
 * 
 * @param string $option_name Option name
 * @param mixed $default Default value
 * @return mixed Option value
 */
function wpfasty_get_option($option_name, $default = false)
{
    global $wpfasty_options_cache;
    
    // If options were not preloaded, load them now
    if (!is_array($wpfasty_options_cache)) {
        $wpfasty_options_cache = [];
        $all_options = wp_load_alloptions();
        $wpfasty_options_cache = array_merge($wpfasty_options_cache, $all_options);
    }
    
    // Check if option exists in cache
    if (isset($wpfasty_options_cache[$option_name])) {
        return maybe_unserialize($wpfasty_options_cache[$option_name]);
    }
    
    // If option is not in cache but we've already loaded all options, it doesn't exist
    if (did_action('wp_loaded')) {
        return $default;
    }
    
    // If we're here, the option is not in cache but we haven't loaded all options yet
    // Get the option in standard way
    $value = get_option($option_name, $default);
    $wpfasty_options_cache[$option_name] = $value;
    
    return $value;
}

/**
 * Get site logo URL with caching
 * 
 * @param int|null $size Optional. Size of the logo. Default null (full size).
 * @return string|false Logo URL or false if no logo is set
 */
function wpfasty_get_site_logo(?int $size = null)
{
    static $logo_cache = [];
    $cache_key = $size ?? 'full';
    
    // Return from cache if available
    if (isset($logo_cache[$cache_key])) {
        return $logo_cache[$cache_key];
    }
    
    // Get logo ID from our cached options
    $custom_logo_id = wpfasty_get_option('site_logo', 0);
    
    if (!$custom_logo_id) {
        // Try WordPress core logo if site_logo is not set
        // theme_mod uses separate cache in WordPress
        $custom_logo_id = get_theme_mod('custom_logo');
    }
    
    if (!$custom_logo_id) {
        $logo_cache[$cache_key] = false;
        return false;
    }
    
    // Get logo URL
    $logo_url = false;
    if ($size) {
        $logo_data = wp_get_attachment_image_src($custom_logo_id, [$size, $size]);
        if ($logo_data) {
            $logo_url = $logo_data[0];
        }
    } else {
        $logo_url = wp_get_attachment_url($custom_logo_id);
    }
    
    // Cache and return
    $logo_cache[$cache_key] = $logo_url;
    return $logo_url;
}

/**
 * Get site logo HTML with caching
 * 
 * @return string Logo HTML or empty string if no logo
 */
function wpfasty_get_site_logo_html(): string
{
    static $html_cache = null;
    
    if ($html_cache !== null) {
        return $html_cache;
    }
    
    $logo_url = wpfasty_get_site_logo();
    if (!$logo_url) {
        $html_cache = '';
        return '';
    }
    
    $site_name = get_bloginfo('name');
    $html = sprintf(
        '<a href="%1$s" class="site-logo-link" rel="home" itemprop="url">
            <img src="%2$s" alt="%3$s" class="site-logo" itemprop="logo" />
        </a>',
        esc_url(home_url('/')),
        esc_url($logo_url),
        esc_attr($site_name)
    );
    
    $html_cache = $html;
    return $html;
}

// WordPress user data optimization
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

/**
 * WordPress child pages optimization with global cache to prevent DB queries
 * 
 * @param int $parent_id Parent page ID
 * @param array $args Additional arguments
 * @return array Array of child pages
 */
function wpfasty_get_child_pages($parent_id, $args = array())
{
    // Use global hierarchy cache to avoid DB queries
    global $wpfasty_page_hierarchy_cache;
    
    // Generate cache key for the specific query
    $cache_key = $parent_id . '_' . md5(serialize($args));
    
    // Static cache for function calls with the same arguments
    static $children_cache = array();
    
    // Check function-level cache first
    if (isset($children_cache[$cache_key])) {
        return $children_cache[$cache_key];
    }
    
    // Default arguments
    $default_args = array(
        'post_parent' => $parent_id,
        'post_type' => 'page',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
    // Merge with provided arguments
    $args = wp_parse_args($args, $default_args);
    
    // Initialize cache if not already
    if (!is_array($wpfasty_page_hierarchy_cache)) {
        $wpfasty_page_hierarchy_cache = [];
        
        // Get all pages in one query to build a complete hierarchy
        $all_pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order title',
            'order' => 'ASC',
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
        
        // Build hierarchy structure
        foreach ($all_pages as $page) {
            $pid = $page->post_parent;
            
            if (!isset($wpfasty_page_hierarchy_cache[$pid])) {
                $wpfasty_page_hierarchy_cache[$pid] = [];
            }
            
            $wpfasty_page_hierarchy_cache[$pid][] = $page;
        }
    }
    
    // Check for children in global hierarchy cache
    if (isset($wpfasty_page_hierarchy_cache[$parent_id])) {
        $children = $wpfasty_page_hierarchy_cache[$parent_id];
        
        // Apply additional filtering based on args
        if ($args['orderby'] === 'title' && $args['order'] === 'ASC') {
            // Already sorted by title ASC in our cache
            $filtered_children = $children;
        } else {
            // Need custom sorting, use apply_filters directly
            $filtered_children = $children;
            
            // Apply custom sorting if needed
            if ($args['orderby'] !== 'title' || $args['order'] !== 'ASC') {
                // Sort by the requested field
                usort($filtered_children, function($a, $b) use ($args) {
                    $orderby = $args['orderby'];
                    $order = strtoupper($args['order']);
                    
                    // Handle common orderby values
                    switch ($orderby) {
                        case 'date':
                            $valA = strtotime($a->post_date);
                            $valB = strtotime($b->post_date);
                            break;
                        case 'menu_order':
                            $valA = $a->menu_order;
                            $valB = $b->menu_order;
                            break;
                        case 'modified':
                            $valA = strtotime($a->post_modified);
                            $valB = strtotime($b->post_modified);
                            break;
                        case 'title':
                        default:
                            $valA = $a->post_title;
                            $valB = $b->post_title;
                    }
                    
                    // Apply sort direction
                    if ($order === 'DESC') {
                        return $valB <=> $valA;
                    }
                    
                    return $valA <=> $valB;
                });
            }
        }
        
        // Store in function-level cache
        $children_cache[$cache_key] = $filtered_children;
        return $filtered_children;
    }
    
    // If not found in cache, fall back to database query (should rarely happen)
    $children = get_posts($args);
    $children_cache[$cache_key] = $children;
    
    return $children;
}

// WordPress scripts and styles optimization
add_action('wp_enqueue_scripts', function (): void {
    // Move jQuery to the footer to speed up page loading
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_register_script('jquery', includes_url('/js/jquery/jquery.min.js'), false, null, true);
        wp_enqueue_script('jquery');
    }

    // Disable wp-embed.min.js
    wp_deregister_script('wp-embed');
}, 100);

/**
 * Disable speculationrules script and prefetch in the footer
 * 
 * This tag is added by WordPress or a plugin and provides link prefetching functionality
 * but is not always needed and can clutter the HTML output.
 */
function wpfasty_disable_speculation_rules(): void
{
    // Remove any speculation rules scripts using output buffering
    add_action('wp_footer', function (): void {
        ob_start(function ($buffer) {
            // Remove speculationrules script tags
            $buffer = preg_replace('/<script type="speculationrules">.*?<\/script>/s', '', $buffer);
            return $buffer;
        }, 0, PHP_OUTPUT_HANDLER_REMOVABLE);
    }, 0);
    
    // Flush the buffer at the end of wp_footer
    add_action('wp_footer', function (): void {
        ob_end_flush();
    }, PHP_INT_MAX);
}

/**
 * Removes WordPress robots meta tag
 * 
 * WordPress adds the robots meta tag automatically, 
 * this function disables it for cleaner HTML
 */
function wpfasty_disable_robots_meta(): void
{
    remove_filter('wp_robots', 'wp_robots_max_image_preview_large');
    
    // Completely disables the robots meta tag if needed
    add_filter('wp_robots', '__return_empty_array');
}

/**
 * Completely disable jQuery if not needed
 * 
 * This will completely remove jQuery from the site
 * Only use this if you're sure no frontend functionality requires jQuery
 */
function wpfasty_disable_jquery_completely(): void
{
    if (!is_admin()) {
        // Completely disable jQuery registration
        add_action('wp_enqueue_scripts', function (): void {
            wp_deregister_script('jquery');
            wp_deregister_script('jquery-core');
            wp_deregister_script('jquery-migrate');
        }, 1);
        
        // Filter script tags before outputting to the page
        add_filter('script_loader_tag', function ($tag, $handle, $src) {
            // Remove jquery scripts
            if (strpos($src, 'jquery.min.js') !== false || 
                strpos($handle, 'jquery') !== false) {
                return '';
            }
            return $tag;
        }, 10, 3);
        
        // Additionally remove any remaining jquery script tags
        add_action('wp_footer', function (): void {
            ob_start(function ($buffer) {
                // Remove all jquery script tags
                $buffer = preg_replace('/<script(.*)jquery\.min\.js(.*)<\/script>/i', '', $buffer);
                return $buffer;
            }, 0, PHP_OUTPUT_HANDLER_REMOVABLE);
        }, 1);
        
        // Flush the buffer
        add_action('wp_footer', function (): void {
            if (ob_get_level()) {
                ob_end_flush();
            }
        }, PHP_INT_MAX);
    }
}

/**
 * Initialize frontend optimizations for clean output
 */
function wpfasty_clean_frontend(): void {
    // Disable speculation rules
    wpfasty_disable_speculation_rules();
    
    // Disable robots meta tag
    wpfasty_disable_robots_meta();
    
    // Completely disable jQuery
    wpfasty_disable_jquery_completely();
}

// Initialize frontend optimizations
add_action('init', 'wpfasty_clean_frontend');