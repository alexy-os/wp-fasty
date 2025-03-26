<?php
declare(strict_types=1);

/**
 * Assets Service Provider
 * Handles registration and loading of theme assets (scripts and styles)
 */

namespace FastyChild\Core\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Theme\Services\AssetsService;

class AssetsServiceProvider extends AbstractServiceProvider
{
    /**
     * Register assets service in the container
     * 
     * @return void
     */
    public function register(): void
    {
        // Register the assets service in the container
        $this->singleton('assets', function() {
            return new AssetsService($this->getService('app'));
        });
    }
    
    /**
     * Boot assets service - hook into WordPress
     * 
     * @return void
     */
    public function boot(): void
    {
        // Get assets service
        $assets = $this->getService('assets');
        
        // Register hooks for enqueueing styles and scripts
        add_action('wp_enqueue_scripts', [$assets, 'enqueueStyles']);
        add_action('wp_enqueue_scripts', [$assets, 'enqueueScripts']);
        
        // Register admin assets if needed
        add_action('admin_enqueue_scripts', [$assets, 'enqueueAdminStyles']);
        add_action('admin_enqueue_scripts', [$assets, 'enqueueAdminScripts']);
    }
} 