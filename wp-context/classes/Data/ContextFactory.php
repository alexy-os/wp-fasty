<?php

declare(strict_types=1);

namespace WPFasty\Data;

use WPFasty\Core\ContainerInterface;

/**
 * Factory for creating context collections
 */
class ContextFactory
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }
    
    /**
     * Create context for page template
     */
    public function createPageContext(\WP_Post $post = null): array
    {
        $post = $post ?? get_post();
        $context = [];
        
        // Add site data
        $context['site'] = SiteData::fromWordPress();
        
        // Add page data
        if ($post) {
            $context['page'] = PageData::fromPost($post);
        }
        
        // Add menus
        $menus = [];
        $locations = get_nav_menu_locations();
        
        foreach (array_keys($locations) as $location) {
            $menus[$location] = MenuData::fromLocation($location);
        }
        
        $context['menu'] = $menus;
        
        // Allow plugins to modify context
        $context = apply_filters('wpfasty_context', $context, $post);
        
        return $context;
    }
    
    /**
     * Create context for archive template
     */
    public function createArchiveContext(): array
    {
        $context = [];
        
        // Add site data
        $context['site'] = SiteData::fromWordPress();
        
        // Add archive data
        $context['archive'] = [
            'title' => get_the_archive_title(),
            'description' => get_the_archive_description(),
        ];
        
        // Add posts
        $posts = [];
        while (have_posts()) {
            the_post();
            $posts[] = PageData::fromPost(get_post());
        }
        wp_reset_postdata();
        
        $context['posts'] = $posts;
        
        // Add pagination
        $context['pagination'] = $this->getPaginationContext();
        
        // Add menus
        $menus = [];
        $locations = get_nav_menu_locations();
        
        foreach (array_keys($locations) as $location) {
            $menus[$location] = MenuData::fromLocation($location);
        }
        
        $context['menu'] = $menus;
        
        // Allow plugins to modify context
        $context = apply_filters('wpfasty_context', $context, null);
        
        return $context;
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
}
