<?php
/**
 * Main template file with database query optimization and tracking
 *
 * @package WPFasty
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enable query tracking
global $wpdb;
$wpdb->queries = []; // Reset queries

// Define SAVEQUERIES only if not already defined
if (!defined('SAVEQUERIES')) {
    define('SAVEQUERIES', true);
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
        h1, h2, h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            margin-top: 2rem;
        }
        pre {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 4px;
            overflow: auto;
            max-height: 70vh;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            text-align: left;
            padding: 0.5rem;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .stats-box {
            background: #f0f8ff;
            border: 1px solid #ccc;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .stats-box h3 {
            margin-top: 0;
            border-bottom: none;
        }
        .footer {
            margin-top: 2rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
            font-size: 0.875rem;
            color: #666;
        }
        .query-wrapper {
            margin: 1rem 0;
        }
        .query-time {
            font-weight: bold;
            color: #d44;
        }
        .query-group {
            margin-bottom: 2rem;
        }
    </style>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <h1>WP FastY Theme Debug Console</h1>
    
    <?php
    // Show database query stats box
    global $wpdb;
    $query_count = count($wpdb->queries);
    $query_time = 0;

    foreach ($wpdb->queries as $query) {
        $query_time += $query[1];
    }
    ?>
    <div class="stats-box">
        <h3>Database Performance</h3>
        <p><strong>Total Queries:</strong> <?php echo $query_count; ?></p>
        <p><strong>Total Query Time:</strong> <?php echo round($query_time * 1000, 2); ?> ms</p>
        <p><strong>Average Query Time:</strong> <?php echo $query_count ? round(($query_time / $query_count) * 1000, 2) : 0; ?> ms per query</p>
    </div>

    <div class="context-dump">
        <h2>Page Context</h2>
        <pre><?php echo esc_html(prettyPrintArray(getPageContext())); ?></pre>
    </div>

    <?php if (shortcode_exists('go_frontend')) : ?>
    <h2>Go Frontend</h2>
    <div class="stats-box">
        <h3>Go Frontend Integration</h3>
        <p>Go фронтенд интегрирован. Используйте шорткод <code>[go_frontend]</code> для вывода.</p>
        <p>Вы также можете создать страницу и выбрать шаблон "Go Frontend".</p>
        
        <div class="go-frontend-test">
            <button onclick="document.getElementById('go-frontend-preview').style.display = 'block';" class="px-4 py-2 bg-indigo-600 text-white rounded">
                Показать превью Go Frontend
            </button>
            <div id="go-frontend-preview" style="display: none; margin-top: 1rem; padding: 1rem; border: 1px solid #ddd; height: 300px; overflow: auto;">
                <iframe width="100%" height="100%" style="border: none;" src="<?php echo esc_url(home_url('?go_preview=1')); ?>"></iframe>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <h2>Database Queries</h2>
    
    <?php
    // Group queries by caller
    $query_groups = [];
    foreach ($wpdb->queries as $query) {
        $sql = $query[0];
        $time = $query[1];
        $stack = $query[2];

        // Extract the caller function from the stack
        $caller = "Unknown";
        if (preg_match('/\s+(\w+(?:->)?\w+)\s+/', $stack, $matches)) {
            $caller = $matches[1];
        }

        if (!isset($query_groups[$caller])) {
            $query_groups[$caller] = [
                'count' => 0,
                'time' => 0,
                'queries' => []
            ];
        }

        $query_groups[$caller]['count']++;
        $query_groups[$caller]['time'] += $time;
        $query_groups[$caller]['queries'][] = [
            'sql' => $sql,
            'time' => $time
        ];
    }

    // Sort query groups by count (desc)
    uasort($query_groups, function ($a, $b) {
        return $b['count'] - $a['count'];
    });
    ?>
    
    <table>
        <thead>
            <tr>
                <th>Caller Function</th>
                <th>Query Count</th>
                <th>Total Time (ms)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($query_groups as $caller => $group) : ?>
            <tr>
                <td><?php echo esc_html($caller); ?></td>
                <td><?php echo esc_html($group['count']); ?></td>
                <td><?php echo esc_html(round($group['time'] * 1000, 2)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php foreach ($query_groups as $caller => $group) : ?>
    <div class="query-group">
        <h3><?php echo esc_html($caller); ?> (<?php echo esc_html($group['count']); ?> queries)</h3>
        
        <div class="query-wrapper">
            <?php foreach ($group['queries'] as $query) : ?>
            <div class="query">
                <p class="query-time"><?php echo esc_html(round($query['time'] * 1000, 2)); ?> ms</p>
                <pre><?php echo esc_html($query['sql']); ?></pre>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <h2>Optimization Recommendations</h2>
    <ul>
        <?php
        // Get optimization recommendations
        $recommendations = getOptimizationRecommendations($wpdb->queries);
        foreach ($recommendations as $recommendation) :
            ?>
        <li><?php echo esc_html($recommendation); ?></li>
        <?php endforeach; ?>
    </ul>

    <div class="footer">
        <p>WP FastY Theme Debug Console - PHP version: <?php echo PHP_VERSION; ?></p>
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
function getPageContext(): array
{
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
 * @param integer $post_id The post ID
 * @return array Meta data
 */
function getPostMeta(int $post_id): array
{
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
 * @param integer $level The current indent level
 * @return string The formatted array
 */
function prettyPrintArray(array $array, int $level = 0): string
{
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

/**
 * Get optimization recommendations based on queries
 *
 * @param array $queries List of queries from $wpdb->queries
 * @return array List of recommendations
 */
function getOptimizationRecommendations(array $queries): array
{
    $recommendations = [];
    $callers = [];

    // Count queries by caller
    foreach ($queries as $query) {
        $stack = $query[2];

        // Extract the caller function
        $caller = "Unknown";
        if (preg_match('/\s+(\w+(?:->)?\w+)\s+/', $stack, $matches)) {
            $caller = $matches[1];
        }

        if (!isset($callers[$caller])) {
            $callers[$caller] = 0;
        }

        $callers[$caller]++;
    }

    // Check for multiple get_option calls
    if (isset($callers['get_option']) && $callers['get_option'] > 5) {
        $recommendations[] = "Reduce get_option calls ({$callers['get_option']} calls) by using wp_load_alloptions() for multiple options.";
    }

    // Check for multiple update_meta_cache calls
    if (isset($callers['update_meta_cache']) && $callers['update_meta_cache'] > 1) {
        $recommendations[] = "Consolidate update_meta_cache calls ({$callers['update_meta_cache']} calls) to reduce queries.";
    }

    // Check for WP_Query usage
    if (isset($callers['WP_Query->get_posts'])) {
        $recommendations[] = "Consider using 'fields' => 'ids' in WP_Query when only IDs are needed.";
        $recommendations[] = "Use 'no_found_rows' => true in WP_Query when pagination is not needed.";
    }

    // General recommendations
    $recommendations[] = "Enable object caching with a persistent cache like Redis or Memcached.";
    $recommendations[] = "Consider using the _prime_post_caches() function to preload posts in a single query.";
    $recommendations[] = "Implement an optimization class in the theme to reduce common WordPress query patterns.";

    return $recommendations;
}

/**
 * Function to optimize common WordPress queries
 * Add this to your theme's functions.php
 */
function wpfasty_optimize_queries(): void
{
    // Reduce calls to get_option
    if (!is_admin()) {
        // Preload options in a single query
        wp_load_alloptions();
    }

    // Disable emoji support if not needed
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');

    // Disable wp-embed if not needed
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');

    // Disable self pingbacks
    add_action('pre_ping', function (&$links): void {
        $home = get_option('home');
        foreach ($links as $l => $link) {
            if (strpos($link, $home) === 0) {
                unset($links[$l]);
            }
        }
    });
}

// Uncomment this to activate optimizations
add_action('init', 'wpfasty_optimize_queries');
