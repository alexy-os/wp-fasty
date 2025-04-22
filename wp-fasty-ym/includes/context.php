<?php
/**
 * Main template file
 *
 * This is a debug version that shows the current page context in formatted array.
 *
 * @package WPFasty
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Start HTML output
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title('|', true, 'right'); ?><?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.5;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        h1 {
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 2rem;
        }
        pre {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
            overflow: auto;
            max-height: 70vh;
            border: 1px solid #ddd;
        }
        .context-dump {
            margin-bottom: 2rem;
        }
        .footer {
            margin-top: 2rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
            font-size: 0.875rem;
            color: #666;
        }
    </style>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <h1>WP FastY Theme Context Debug</h1>
    
    <div class="context-dump">
        <h2>Page Context</h2>
        <pre><?php echo esc_html(prettyPrintArray(getPageContext())); ?></pre>
    </div>

    <div class="footer">
        <p>This is a debug view of the WP FastY Theme context data. This structured data is available to all templates.</p>
        <p>Current PHP version: <?php echo PHP_VERSION; ?></p>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
<?php

/**
 * Get current page context without using the full theme architecture
 *
 * This is a temporary function for debugging purposes.
 *
 * @return array Context data
 */
function getPageContext() {
    global $post;

    // Site context
    $site = [
        'title' => get_bloginfo('name'),
        'url' => home_url(),
        'lang' => get_bloginfo('language'),
        'description' => get_bloginfo('description'),
        'charset' => get_bloginfo('charset'),
        'version' => get_bloginfo('version'),
        'admin_email' => get_bloginfo('admin_email')
    ];

    // Page context
    $page = [];
    if (is_singular() && $post) {
        $page = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'content' => apply_filters('the_content', $post->post_content),
            'slug' => $post->post_name,
            'excerpt' => has_excerpt($post) ? get_the_excerpt($post) : null,
            'date' => get_the_date('c', $post),
            'modified' => get_the_modified_date('c', $post),
            'author' => [
                'id' => $post->post_author,
                'name' => get_the_author_meta('display_name', $post->post_author),
                'url' => get_author_posts_url($post->post_author)
            ],
            'meta' => getPostMeta($post->ID),
            'thumbnail' => has_post_thumbnail($post) ? 
                get_the_post_thumbnail_url($post, 'full') : null,
            'type' => $post->post_type,
            'format' => get_post_format($post) ?: 'standard',
            'url' => get_permalink($post)
        ];
    }

    // Archive context
    $archive = [];
    if (is_archive() || is_search()) {
        $archive = [
            'title' => get_the_archive_title(),
            'description' => get_the_archive_description(),
            'is_search' => is_search(),
            'search_query' => get_search_query(),
            'posts_count' => $GLOBALS['wp_query']->found_posts
        ];
    }

    // Menu context
    $menus = [];
    $locations = get_nav_menu_locations();
    
    foreach ($locations as $location => $menu_id) {
        $menu = wp_get_nav_menu_object($menu_id);
        if ($menu) {
            $menu_items = wp_get_nav_menu_items($menu->term_id);
            $menu_items_array = [];
            
            if ($menu_items) {
                foreach ($menu_items as $item) {
                    $menu_items_array[] = [
                        'id' => $item->ID,
                        'title' => $item->title,
                        'url' => $item->url,
                        'order' => $item->menu_order,
                        'parent' => $item->menu_item_parent ? (int)$item->menu_item_parent : null,
                        'classes' => $item->classes ?: null,
                        'target' => $item->target,
                        'current' => $item->object_id == get_queried_object_id()
                    ];
                }
            }
            
            $menus[$location] = $menu_items_array;
        }
    }

    // Query context
    $query = [];
    $wp_query = $GLOBALS['wp_query'];
    if ($wp_query) {
        $query = [
            'is_singular' => is_singular(),
            'is_page' => is_page(),
            'is_single' => is_single(),
            'is_archive' => is_archive(),
            'is_category' => is_category(),
            'is_tag' => is_tag(),
            'is_tax' => is_tax(),
            'is_author' => is_author(),
            'is_date' => is_date(),
            'is_home' => is_home(),
            'is_front_page' => is_front_page(),
            'is_search' => is_search(),
            'is_404' => is_404(),
            'found_posts' => $wp_query->found_posts,
            'max_num_pages' => $wp_query->max_num_pages,
            'current_page' => max(1, get_query_var('paged'))
        ];
    }

    // Build complete context
    $context = [
        'site' => $site,
        'query' => $query
    ];

    if ($page) {
        $context['page'] = $page;
    }

    if ($archive) {
        $context['archive'] = $archive;
    }

    if ($menus) {
        $context['menu'] = $menus;
    }

    // Get posts if on archive/home/search
    if (is_archive() || is_home() || is_search()) {
        $posts = [];
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $posts[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'excerpt' => get_the_excerpt(),
                    'url' => get_permalink(),
                    'date' => get_the_date('c'),
                    'thumbnail' => has_post_thumbnail() ? 
                        get_the_post_thumbnail_url(null, 'thumbnail') : null,
                    'author' => [
                        'id' => get_the_author_meta('ID'),
                        'name' => get_the_author(),
                        'url' => get_author_posts_url(get_the_author_meta('ID'))
                    ]
                ];
            }
            wp_reset_postdata();
        }
        $context['posts'] = $posts;
    }

    return $context;
}

/**
 * Get post meta as formatted array
 *
 * @param int $post_id The post ID
 * @return array Meta data
 */
function getPostMeta($post_id) {
    $meta = get_post_meta($post_id);
    $formatted = [];
    
    foreach ($meta as $key => $values) {
        // Skip internal WordPress meta
        if (strpos($key, '_') === 0 && $key !== '_wp_page_template') {
            continue;
        }
        
        // Format the value
        $value = $values[0] ?? null;
        
        // Try to unserialize if it looks like serialized data
        if (is_string($value) && strpos($value, 'a:') === 0 || strpos($value, 'O:') === 0) {
            $unserialized = @unserialize($value);
            if ($unserialized !== false) {
                $value = $unserialized;
            }
        }
        
        $formatted[$key] = $value;
    }
    
    return $formatted;
}

/**
 * Pretty print an array with indentation
 *
 * @param array $array The array to print
 * @param int $level The current indent level
 * @return string The formatted array
 */
function prettyPrintArray($array, $level = 0) {
    if (!is_array($array)) {
        if (is_bool($array)) {
            return $array ? 'true' : 'false';
        } elseif (is_null($array)) {
            return 'null';
        } elseif (is_string($array)) {
            // Truncate long strings
            if (strlen($array) > 1000) {
                return "'" . htmlspecialchars(substr($array, 0, 1000)) . "...'";
            }
            return "'" . htmlspecialchars($array) . "'";
        } else {
            return var_export($array, true);
        }
    }
    
    // Initialize the output string
    $output = "[\n";
    
    // Calculate indentation
    $indent = str_repeat("    ", $level + 1);
    
    // Process each element
    foreach ($array as $key => $value) {
        $output .= $indent;
        
        // Add the key
        if (is_string($key)) {
            $output .= "'" . addslashes($key) . "' => ";
        } else {
            $output .= $key . " => ";
        }
        
        // Add the value
        $output .= prettyPrintArray($value, $level + 1);
        $output .= ",\n";
    }
    
    // Close the array and return
    $output .= str_repeat("    ", $level) . "]";
    
    return $output;
}