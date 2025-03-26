<?php
declare(strict_types=1);

/**
 * Theme Service Provider
 * Handles theme setup, features, and customizations
 */

namespace FastyChild\Core\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Theme\Services\ThemeService;

class ThemeServiceProvider extends AbstractServiceProvider
{
    /**
     * Register theme service in the container
     * 
     * @return void
     */
    public function register(): void
    {
        // Register theme service in container
        $this->singleton('theme', function() {
            return new ThemeService($this->getService('app'));
        });
    }
    
    /**
     * Boot theme service - register features and customizations
     * 
     * @return void
     */
    public function boot(): void
    {
        $theme = $this->getService('theme');
        
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