<?php
/**
 * Hooks Service Provider
 * Manages WordPress action and filter hooks for the theme
 */

namespace FastyChild\Core\Providers;

use FastyChild\Core\Container;
use FastyChild\Core\ServiceProvider;
use FastyChild\Hooks\ThemeHooks;
use FastyChild\Hooks\StorefrontHooks;
use FastyChild\Hooks\WooCommerceHooks;

class HooksProvider implements ServiceProvider {
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
     * Register hook services in the container
     * 
     * @return void
     */
    public function register(): void {
        // Register basic theme hooks
        $this->container->singleton('hooks.theme', function() {
            return new ThemeHooks($this->container);
        });
        
        // Register Storefront-specific hooks
        $this->container->singleton('hooks.storefront', function() {
            return new StorefrontHooks($this->container);
        });
        
        // Register WooCommerce hooks if WooCommerce is active
        if (class_exists('WooCommerce')) {
            $this->container->singleton('hooks.woocommerce', function() {
                return new WooCommerceHooks($this->container);
            });
        }
    }
    
    /**
     * Boot hook services - register WordPress hooks
     * 
     * @return void
     */
    public function boot(): void {
        // Initialize basic theme hooks
        $this->container->get('hooks.theme')->register();
        
        // Initialize Storefront-specific hooks
        $this->container->get('hooks.storefront')->register();
        
        // Initialize WooCommerce hooks if WooCommerce is active
        if (class_exists('WooCommerce') && $this->container->has('hooks.woocommerce')) {
            $this->container->get('hooks.woocommerce')->register();
        }
    }
} 