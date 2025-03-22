<?php
/**
 * Theme Hooks
 * Handles basic WordPress hook overrides
 */

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class ThemeHooks {
    /**
     * Container instance
     * @var Container
     */
    private $container;
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    /**
     * Register hook handlers
     * 
     * @return void
     */
    public function register(): void {
        // Basic WordPress hooks
        add_filter('body_class', [$this, 'addBodyClasses']);
        add_filter('excerpt_more', [$this, 'modifyExcerptMore']);
        add_filter('excerpt_length', [$this, 'modifyExcerptLength']);
        
        // Disable WordPress emoji
        $this->disableEmoji();
        
        // Clean up WordPress head
        $this->cleanHead();
    }
    
    /**
     * Add custom body classes
     * 
     * @param array $classes Existing body classes
     * @return array Modified classes
     */
    public function addBodyClasses(array $classes): array {
        $app = $this->container->get('app');
        
        // Add class for Storefront child theme
        $classes[] = 'fasty-storefront-child';
        
        // Add custom classes from configuration
        $custom_classes = $app->config('theme.body_classes', []);
        
        return array_merge($classes, $custom_classes);
    }
    
    /**
     * Modify excerpt "read more" string
     * 
     * @param string $more Default "read more" string
     * @return string Modified string
     */
    public function modifyExcerptMore(string $more): string {
        $app = $this->container->get('app');
        $custom_more = $app->config('theme.excerpt_more', '&hellip;');
        
        return $custom_more;
    }
    
    /**
     * Modify excerpt length
     * 
     * @param int $length Default excerpt length
     * @return int Modified length
     */
    public function modifyExcerptLength(int $length): int {
        $app = $this->container->get('app');
        $custom_length = $app->config('theme.excerpt_length', 55);
        
        return $custom_length;
    }
    
    /**
     * Disable WordPress emoji functionality
     * 
     * @return void
     */
    private function disableEmoji(): void {
        $app = $this->container->get('app');
        
        if ($app->config('theme.disable_emoji', true)) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
            
            // Remove emoji from TinyMCE
            add_filter('tiny_mce_plugins', function($plugins) {
                if (is_array($plugins)) {
                    return array_diff($plugins, ['wpemoji']);
                }
                return $plugins;
            });
        }
    }
    
    /**
     * Clean up WordPress head
     * 
     * @return void
     */
    private function cleanHead(): void {
        $app = $this->container->get('app');
        
        if ($app->config('theme.clean_head', true)) {
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
            
            // Disable REST API link in header
            remove_action('wp_head', 'rest_output_link_wp_head');
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
        }
    }
} 