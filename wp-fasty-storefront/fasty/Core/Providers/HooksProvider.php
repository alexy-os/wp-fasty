<?php
declare(strict_types=1);

/**
 * Hooks Service Provider
 * Manages WordPress action and filter hooks for the theme
 */

namespace FastyChild\Core\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Hooks\ThemeHooks;
use FastyChild\Hooks\StorefrontHooks;
use FastyChild\Hooks\WooCommerceHooks;

class HooksProvider extends AbstractServiceProvider
{
    /**
     * Register hook services in the container
     * 
     * @return void
     */
    public function register(): void
    {
        // Register basic theme hooks
        $this->singleton('hooks.theme', function() {
            return new ThemeHooks($this->container);
        });
        
        // Register Storefront-specific hooks
        $this->singleton('hooks.storefront', function() {
            return new StorefrontHooks($this->container);
        });
        
        // Register WooCommerce hooks if WooCommerce is active
        if (class_exists('WooCommerce')) {
            $this->singleton('hooks.woocommerce', function() {
                return new WooCommerceHooks($this->container);
            });
        }
    }
    
    /**
     * Boot hook services - register WordPress hooks
     * 
     * @return void
     */
    public function boot(): void
    {
        // Initialize basic theme hooks
        $this->getService('hooks.theme')->register();
        
        // Initialize Storefront-specific hooks
        $this->getService('hooks.storefront')->register();
        
        // Initialize WooCommerce hooks if WooCommerce is active
        if (class_exists('WooCommerce') && $this->hasService('hooks.woocommerce')) {
            $this->getService('hooks.woocommerce')->register();
        }
    }
} 