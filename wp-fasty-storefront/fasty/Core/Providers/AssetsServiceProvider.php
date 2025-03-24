<?php
/**
 * Assets Service Provider
 * Handles registration and loading of theme assets (scripts and styles)
 */

namespace FastyChild\Core\Providers;

use FastyChild\Core\Container;
use FastyChild\Core\ServiceProvider;
use FastyChild\Services\AssetsService;

class AssetsServiceProvider implements ServiceProvider {
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
     * Register assets service in the container
     * 
     * @return void
     */
    public function register(): void {
        // Register the assets service in the container
        $this->container->singleton('assets', function() {
            return new AssetsService($this->container->get('app'));
        });
    }
    
    /**
     * Boot assets service - hook into WordPress
     * 
     * @return void
     */
    public function boot(): void {
        // Get assets service
        $assets = $this->container->get('assets');
        
        // Register hooks for enqueueing styles and scripts
        add_action('wp_enqueue_scripts', [$assets, 'enqueueStyles']);
        add_action('wp_enqueue_scripts', [$assets, 'enqueueScripts']);
        
        // Register admin assets if needed
        add_action('admin_enqueue_scripts', [$assets, 'enqueueAdminStyles']);
        add_action('admin_enqueue_scripts', [$assets, 'enqueueAdminScripts']);
    }
} 