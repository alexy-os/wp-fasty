<?php
declare(strict_types=1);

namespace FastyChild\Theme\Hooks;

use FastyChild\Core\Container;

/**
 * Basic WordPress theme hooks implementation
 */
class ThemeHooks extends AbstractThemeHook
{
    /**
     * Register WordPress theme hooks
     * 
     * @return void
     */
    public function register(): void
    {
        // Theme setup
        $this->addAction('after_setup_theme', 'setupTheme');
        
        // Script and style loading
        $this->addAction('wp_enqueue_scripts', 'enqueueScripts');
        
        // Widgets
        $this->addAction('widgets_init', 'registerWidgets');
        
        // Content filters
        $this->addFilter('body_class', 'filterBodyClasses');
        $this->addFilter('excerpt_more', 'filterExcerptMore');
        $this->addFilter('excerpt_length', 'filterExcerptLength');
    }
    
    /**
     * Theme setup hook callback
     * 
     * @return void
     */
    public function setupTheme(): void
    {
        // Load text domain for translations
        if (defined('FASTY_TEXTDOMAIN')) {
            load_child_theme_textdomain(
                FASTY_TEXTDOMAIN, 
                get_stylesheet_directory() . '/languages'
            );
        }
        
        // Add theme supports
        add_theme_support('automatic-feed-links');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ]);
    }
    
    /**
     * Enqueue scripts and styles
     * 
     * @return void
     */
    public function enqueueScripts(): void
    {
        // Basic theme script and style enqueuing is handled by AssetsService
        // This method can be used for additional assets or conditional loading
    }
    
    /**
     * Register sidebar widgets
     * 
     * @return void
     */
    public function registerWidgets(): void
    {
        register_sidebar([
            'name'          => __('Sidebar', FASTY_TEXTDOMAIN),
            'id'            => 'sidebar-1',
            'description'   => __('Add widgets here.', FASTY_TEXTDOMAIN),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);
    }
    
    /**
     * Filter body classes
     * 
     * @param array $classes Array of body classes
     * @return array Modified array of body classes
     */
    public function filterBodyClasses(array $classes): array
    {
        // Add custom classes if needed
        return $classes;
    }
    
    /**
     * Filter excerpt more string
     * 
     * @param string $more The current "more" string
     * @return string Modified "more" string
     */
    public function filterExcerptMore(string $more): string
    {
        return '&hellip;';
    }
    
    /**
     * Filter excerpt length
     * 
     * @param int $length Current excerpt length
     * @return int Modified excerpt length
     */
    public function filterExcerptLength(int $length): int
    {
        return 55; // Default WordPress value
    }
} 