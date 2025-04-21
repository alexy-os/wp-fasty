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
    public function createPageContext(\WP_Post $post = null): ContextCollection
    {
        $post = $post ?? get_post();
        $context = new ContextCollection();
        
        // Add site data
        $context->add('site', SiteData::fromWordPress());
        
        // Add page data
        if ($post) {
            $context->add('page', PageData::fromPost($post));
        }
        
        // Add menus
        $menus = [];
        $locations = get_nav_menu_locations();
        
        foreach (array_keys($locations) as $location) {
            $menus[$location] = MenuData::fromLocation($location);
        }
        
        $context->add('menu', $menus);
        
        // Allow plugins to modify context
        $context = apply_filters('wpfasty_context', $context, $post);
        
        return $context;
    }
    
    /**
     * Create context for archive template
     */
    public function createArchiveContext(): ContextCollection
    {
        $context = new ContextCollection();
        
        // Add site data
        $context->add('site', SiteData::fromWordPress());
        
        // Add archive data
        $context->add('archive', [
            'title' => get_the_archive_title(),
            'description' => get_the_archive_description(),
        ]);
        
        // Add posts
        $posts = [];
        while (have_posts()) {
            the_post();
            $posts[] = PageData::fromPost(get_post());
        }
        wp_reset_postdata();
        
        $context->add('posts', $posts);
        
        // Add menus
        $menus = [];
        $locations = get_nav_menu_locations();
        
        foreach (array_keys($locations) as $location) {
            $menus[$location] = MenuData::fromLocation($location);
        }
        
        $context->add('menu', $menus);
        
        // Allow plugins to modify context
        $context = apply_filters('wpfasty_context', $context, null);
        
        return $context;
    }
}
