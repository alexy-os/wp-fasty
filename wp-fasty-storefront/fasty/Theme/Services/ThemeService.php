<?php
/**
 * Theme Service
 * Handles theme setup, features, and customizations
 */

namespace FastyChild\Theme\Services;

use FastyChild\Core\Application;

class ThemeService {
    /**
     * Application instance
     * @var Application
     */
    private $app;
    
    /**
     * Constructor
     * 
     * @param Application $app
     */
    public function __construct(Application $app) {
        $this->app = $app;
    }
    
    /**
     * Setup theme features
     * 
     * @return void
     */
    public function setupTheme(): void {
        // Load text domain
        load_child_theme_textdomain(FASTY_TEXTDOMAIN, FASTY_CHILD_PATH . '/languages');
        
        // Add theme supports from config
        $supports = $this->app->config('theme.supports', []);
        
        foreach ($supports as $feature => $args) {
            if (is_array($args)) {
                add_theme_support($feature, $args);
            } else {
                add_theme_support($feature);
            }
        }
        
        // Register nav menus from config
        $menus = $this->app->config('theme.menus', []);
        
        if (!empty($menus)) {
            register_nav_menus($menus);
        }
    }
    
    /**
     * Register custom image sizes
     * 
     * @return void
     */
    public function registerImageSizes(): void {
        $image_sizes = $this->app->config('theme.image_sizes', []);
        
        foreach ($image_sizes as $name => $size) {
            add_image_size(
                $name,
                $size['width'] ?? 150,
                $size['height'] ?? 150,
                $size['crop'] ?? false
            );
        }
    }
    
    /**
     * Register customizer settings
     * 
     * @param \WP_Customize_Manager $wp_customize
     * @return void
     */
    public function registerCustomizer($wp_customize): void {
        $customizer = $this->app->config('theme.customizer', []);
        
        // Register panels
        if (isset($customizer['panels']) && is_array($customizer['panels'])) {
            foreach ($customizer['panels'] as $id => $panel) {
                $wp_customize->add_panel($id, $panel);
            }
        }
        
        // Register sections
        if (isset($customizer['sections']) && is_array($customizer['sections'])) {
            foreach ($customizer['sections'] as $id => $section) {
                $wp_customize->add_section($id, $section);
            }
        }
        
        // Register settings and controls
        if (isset($customizer['settings']) && is_array($customizer['settings'])) {
            foreach ($customizer['settings'] as $id => $setting) {
                $wp_customize->add_setting($id, $setting['args'] ?? []);
                
                if (isset($setting['control'])) {
                    $wp_customize->add_control(
                        $setting['control']['type'] ?? 'text', 
                        $setting['control']['args'] ?? []
                    );
                }
            }
        }
    }
    
    /**
     * Register custom widgets
     * 
     * @return void
     */
    public function registerWidgets(): void {
        $widget_areas = $this->app->config('theme.widget_areas', []);
        
        foreach ($widget_areas as $id => $args) {
            register_sidebar(array_merge([
                'id' => $id,
                'name' => ucfirst($id),
                'description' => sprintf(__('Widget area for %s', FASTY_TEXTDOMAIN), $id),
                'before_widget' => '<div id="%1$s" class="widget %2$s">',
                'after_widget' => '</div>',
                'before_title' => '<h4 class="widget-title">',
                'after_title' => '</h4>',
            ], $args));
        }
        
        // Register custom widgets if needed
        $widgets = $this->app->config('theme.widgets', []);
        
        foreach ($widgets as $widget_class) {
            if (class_exists($widget_class)) {
                register_widget($widget_class);
            }
        }
    }
} 