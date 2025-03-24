<?php
/**
 * Theme Service Provider
 * Handles theme setup, features, and customizations
 */

namespace FastyChild\Core\Providers;

use FastyChild\Core\Container;
use FastyChild\Core\ServiceProvider;
use FastyChild\Services\ThemeService;

class ThemeServiceProvider implements ServiceProvider {
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
     * Register theme service in the container
     * 
     * @return void
     */
    public function register(): void {
        // Register theme service in container
        $this->container->singleton('theme', function() {
            return new ThemeService($this->container->get('app'));
        });
    }
    
    /**
     * Boot theme service - register features and customizations
     * 
     * @return void
     */
    public function boot(): void {
        $theme = $this->container->get('theme');
        
        // Setup theme features
        add_action('after_setup_theme', [$theme, 'setupTheme']);
        
        // Register custom image sizes if needed
        add_action('after_setup_theme', [$theme, 'registerImageSizes']);
        
        // Register customizer settings if needed
        add_action('customize_register', [$theme, 'registerCustomizer']);
        
        // Register custom widgets if needed
        add_action('widgets_init', [$theme, 'registerWidgets']);
    }
} 