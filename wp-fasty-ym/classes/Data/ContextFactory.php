<?php

declare(strict_types=1);

namespace WPFasty\Data;

use WPFasty\Core\ContainerInterface;

/**
 * Factory for creating context collections
 */
class ContextFactory
{
    /**
     * Service container
     * 
     * @var ContainerInterface
     */
    private readonly ContainerInterface $container;
    
    /**
     * Constructor
     * 
     * @param ContainerInterface $container Service container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Create context for page template
     * 
     * @param \WP_Post|null $post Post object
     * @return array Complete page context
     */
    public function createPageContext(\WP_Post $post = null): array
    {
        $post = $post ?? get_post();
        
        try {
            $common = $this->createCommonContext();
        } catch (\Exception $e) {
            error_log('Error creating common context: ' . $e->getMessage());
            $common = $this->createFallbackCommonContext();
        }
        
        $context = array_merge($common, [
            'page' => $this->extractPostData($post),
        ]);
        
        // Allow plugins to modify context
        $context = apply_filters('wpfasty_context', $context, $post);
        
        return $context;
    }
    
    /**
     * Create context for archive template
     * 
     * @return array Complete archive context
     */
    public function createArchiveContext(): array
    {
        try {
            $common = $this->createCommonContext();
        } catch (\Exception $e) {
            error_log('Error creating common context for archive: ' . $e->getMessage());
            $common = $this->createFallbackCommonContext();
        }
        
        // Create archive specific context
        $archive_context = [
            'archive' => [
                'title' => get_the_archive_title(),
                'description' => get_the_archive_description(),
            ]
        ];
        
        // Add posts
        $posts = [];
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $posts[] = $this->extractPostData(get_post());
            }
            wp_reset_postdata();
        }
        
        $archive_context['posts'] = $posts;
        
        // Add pagination
        $archive_context['pagination'] = $this->getPaginationContext();
        
        // Merge with common context
        $context = array_merge($common, $archive_context);
        
        // Allow plugins to modify context
        $context = apply_filters('wpfasty_context', $context, null);
        
        return $context;
    }

    /**
     * Creates common context data used across multiple template types
     * 
     * @return array Common context data
     */
    protected function createCommonContext(): array
    {
        return [
            'site' => SiteData::fromWordPress(),
            'menu' => $this->getMenus()
        ];
    }
    
    /**
     * Create fallback context when common context generation fails
     * 
     * @return array Basic site and menu data
     */
    private function createFallbackCommonContext(): array
    {
        return [
            'site' => [
                'title' => get_bloginfo('name'),
                'url' => home_url(),
                'theme_url' => get_template_directory_uri(),
                'lang' => get_locale(),
                'description' => get_bloginfo('description'),
                'charset' => get_bloginfo('charset')
            ],
            'menu' => ['primary' => ['items' => []]]
        ];
    }

    /**
     * Extract post data for context
     * 
     * @param \WP_Post $post Post object
     * @return array Post data structure
     */
    private function extractPostData(\WP_Post $post): array
    {
        // Basic post information
        $post_data = [
            'title' => get_the_title($post),
            'content' => apply_filters('the_content', $post->post_content),
            'slug' => $post->post_name,
            'url' => get_permalink($post),
            'id' => $post->ID,
            'excerpt' => has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(strip_shortcodes($post->post_content), 55),
        ];
        
        // Featured image
        $post_data['featuredImage'] = null;
        $post_data['thumbnail'] = null;
        
        if (has_post_thumbnail($post)) {
            $thumbnail_id = get_post_thumbnail_id($post);
            $thumbnail = wp_get_attachment_image_src($thumbnail_id, 'full');
            
            if ($thumbnail) {
                $post_data['featuredImage'] = [
                    'url' => $thumbnail[0],
                    'width' => $thumbnail[1],
                    'height' => $thumbnail[2],
                    'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true)
                ];
                
                // Add thumbnail version too
                $thumb = wp_get_attachment_image_src($thumbnail_id, 'thumbnail');
                if ($thumb) {
                    $post_data['thumbnail'] = [
                        'url' => $thumb[0],
                        'width' => $thumb[1],
                        'height' => $thumb[2],
                        'alt' => get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true)
                    ];
                }
            }
        }
        
        // Meta data
        $post_data['meta'] = [];
        $meta_keys = get_post_meta($post->ID);
        if ($meta_keys) {
            foreach ($meta_keys as $key => $values) {
                // Skip WordPress internal fields
                if (substr($key, 0, 1) === '_' && $key !== '_edit_last' && $key !== '_edit_lock') {
                    continue;
                }
                
                $post_data['meta'][$key] = count($values) > 1 ? $values : $values[0];
            }
        }
        
        // Categories
        $post_data['categories'] = [];
        $categories = get_the_category($post->ID);
        
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $post_data['categories'][] = [
                    'name' => $category->name,
                    'url' => get_category_link($category->term_id),
                    'id' => $category->term_id,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'count' => $category->count
                ];
            }
        }
        
        // Date information
        $post_data['date'] = [
            'formatted' => get_the_date('Y-m-d', $post),
            'display' => get_the_date(get_option('date_format'), $post),
            'modified' => get_the_modified_date('Y-m-d', $post),
            'modified_display' => get_the_modified_date(get_option('date_format'), $post),
            'timestamp' => get_post_timestamp($post),
            'year' => get_the_date('Y', $post),
            'month' => get_the_date('m', $post),
            'day' => get_the_date('d', $post)
        ];
        
        return $post_data;
    }
    
    /**
     * Get pagination context
     * 
     * @return array|null Pagination data or null if not available
     */
    private function getPaginationContext(): ?array
    {
        global $wp_query;
        
        // If there's only one page, don't show pagination
        if ($wp_query->max_num_pages <= 1) {
            return null;
        }
        
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $max = (int)$wp_query->max_num_pages;
        
        // Previous and next links
        $prev_url = $paged > 1 ? get_pagenum_link($paged - 1) : null;
        $next_url = $paged < $max ? get_pagenum_link($paged + 1) : null;
        
        // Calculate page range (show 2 pages on either side of current)
        $pages = [];
        $start = max(1, $paged - 2);
        $end = min($max, $paged + 2);
        
        // Always show first and last pages
        if ($start > 1) {
            $pages[] = [
                'number' => 1,
                'url' => get_pagenum_link(1),
                'is_current' => false
            ];
            
            // Add dots if needed
            if ($start > 2) {
                $pages[] = [
                    'number' => '...',
                    'url' => null,
                    'is_current' => false
                ];
            }
        }
        
        // Add pages in range
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = [
                'number' => $i,
                'url' => get_pagenum_link($i),
                'is_current' => ($i === $paged)
            ];
        }
        
        // Add last page and dots if needed
        if ($end < $max) {
            // Add dots if needed
            if ($end < $max - 1) {
                $pages[] = [
                    'number' => '...',
                    'url' => null,
                    'is_current' => false
                ];
            }
            
            $pages[] = [
                'number' => $max,
                'url' => get_pagenum_link($max),
                'is_current' => false
            ];
        }
        
        return [
            'prev_url' => $prev_url,
            'next_url' => $next_url,
            'pages' => $pages,
            'current' => $paged,
            'total' => $max
        ];
    }

    /**
     * Get menus available in the system
     * 
     * @return array Menu data by location
     */
    private function getMenus(): array
    {
        $menus = [];
        $locations = get_nav_menu_locations();
        
        if (empty($locations)) {
            return ['primary' => ['items' => []]];
        }
        
        foreach (array_keys($locations) as $location) {
            // Use simple structure if MenuData class is not available
            if (class_exists('\\WPFasty\\Data\\MenuData')) {
                $menus[$location] = MenuData::fromLocation($location);
            } else {
                // Basic menu structure
                $menu_obj = wp_get_nav_menu_object($locations[$location]);
                
                if (!$menu_obj) {
                    $menus[$location] = ['items' => []];
                    continue;
                }
                
                $menu_items = wp_get_nav_menu_items($menu_obj->term_id);
                
                $items = [];
                if ($menu_items) {
                    foreach ($menu_items as $item) {
                        $items[] = [
                            'title' => $item->title,
                            'url' => $item->url,
                            'id' => $item->ID,
                            'order' => $item->menu_order,
                            'parent' => $item->menu_item_parent ? $item->menu_item_parent : null,
                            'classes' => empty($item->classes) ? [] : $item->classes,
                            'current' => false
                        ];
                    }
                }
                
                $menus[$location] = ['items' => $items];
            }
        }
        
        // If no menus are available, create empty primary menu
        if (empty($menus)) {
            $menus['primary'] = ['items' => []];
        }
        
        return $menus;
    }
}
