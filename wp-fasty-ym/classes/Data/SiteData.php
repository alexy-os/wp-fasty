<?php

declare(strict_types=1);

namespace WPFasty\Data;

/**
 * Site data object
 */
final readonly class SiteData extends DataObject
{
    public function __construct(
        public string $title,
        public string $url,
        public string $lang,
        public ?string $description = null,
        public ?string $charset = null
    ) {
    }
    
    /**
     * Create from WordPress globals
     */
    public static function fromWordPress(): self
    {
        return new self(
            title: get_bloginfo('name'),
            url: home_url(),
            lang: get_bloginfo('language'),
            description: get_bloginfo('description'),
            charset: get_bloginfo('charset')
        );
    }
}

/**
 * Page data object
 */
final readonly class PageData extends DataObject
{
    public function __construct(
        public string $title,
        public string $content,
        public string $slug,
        public ?int $id = null,
        public ?string $url = null,
        public ?string $excerpt = null,
        public ?string $featuredImage = null,
        public ?array $meta = null
    ) {
    }
    
    /**
     * Create from WordPress post
     */
    public static function fromPost(\WP_Post $post): self
    {
        return new self(
            title: get_the_title($post),
            content: apply_filters('the_content', $post->post_content),
            slug: $post->post_name,
            url: get_the_permalink($post),
            id: $post->ID,
            excerpt: has_excerpt($post) ? get_the_excerpt($post) : null,
            featuredImage: has_post_thumbnail($post) ? 
                get_the_post_thumbnail_url($post, 'full') : null,
            meta: self::getPostMeta($post->ID)
        );
    }
    
    /**
     * Get post meta as array
     */
    private static function getPostMeta(int $postId): array
    {
        $meta = get_post_meta($postId);
        return array_map(function($value) {
            return count($value) === 1 ? $value[0] : $value;
        }, $meta);
    }
}

/**
 * Menu item data object
 */
final readonly class MenuItemData extends DataObject
{
    public function __construct(
        public string $title,
        public string $url,
        public int $id,
        public int $order,
        public ?int $parent = null,
        public ?array $children = null,
        public ?array $classes = null,
        public bool $current = false
    ) {
    }
}

/**
 * Menu data object
 */
final readonly class MenuData extends DataObject
{
    /**
     * @param array<MenuItemData> $items
     */
    public function __construct(
        public array $items = []
    ) {
    }
    
    /**
     * Create from WordPress menu location
     */
    public static function fromLocation(string $location): self
    {
        $locations = get_nav_menu_locations();
        
        if (!isset($locations[$location])) {
            return new self([]);
        }
        
        $menu = wp_get_nav_menu_object($locations[$location]);
        
        if (!$menu) {
            return new self([]);
        }
        
        $menuItems = wp_get_nav_menu_items($menu->term_id);
        
        if (!$menuItems) {
            return new self([]);
        }
        
        $items = [];
        $currentUrl = home_url($_SERVER['REQUEST_URI']);
        
        foreach ($menuItems as $item) {
            $items[] = new MenuItemData(
                title: $item->title,
                url: $item->url,
                id: $item->ID,
                order: $item->menu_order,
                parent: (int)$item->menu_item_parent ?: null,
                classes: $item->classes ?: null,
                current: $item->url === $currentUrl
            );
        }
        
        return new self($items);
    }
}
