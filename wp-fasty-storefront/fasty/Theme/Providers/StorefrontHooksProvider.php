<?php
declare(strict_types=1);

/**
 * Storefront Hooks Provider
 * Manages Storefront and WooCommerce specific hooks
 */

namespace FastyChild\Theme\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Theme\Hooks\ThemeHooks;
use FastyChild\Theme\Hooks\StorefrontHooks;
use FastyChild\Theme\Hooks\WooCommerceHooks;

/**
 * Theme-specific hooks provider for Storefront
 */
class StorefrontHooksProvider extends AbstractServiceProvider
{
    /**
     * Register theme-specific hook implementations
     * 
     * @return void
     */
    public function register(): void
    {
        // Check if hooks manager is registered
        if (!$this->hasService('hooks.manager')) {
            $this->warning('Hooks manager not found, theme hooks will not be registered');
            return;
        }
        
        // Register basic theme hooks
        $this->singleton('theme.hooks.theme', function() {
            return new ThemeHooks($this->container);
        });
        
        // Register Storefront-specific hooks
        $this->singleton('theme.hooks.storefront', function() {
            return new StorefrontHooks($this->container);
        });
        
        // Register WooCommerce hooks if WooCommerce is active
        if (class_exists('WooCommerce')) {
            $this->singleton('theme.hooks.woocommerce', function() {
                return new WooCommerceHooks($this->container);
            });
        }
    }
    
    /**
     * Boot theme hooks - register with hooks manager
     * 
     * @return void
     */
    public function boot(): void
    {
        // Check if hooks manager is registered
        if (!$this->hasService('hooks.manager')) {
            return;
        }
        
        $manager = $this->getService('hooks.manager');
        
        // Register theme hooks with manager
        if ($this->hasService('theme.hooks.theme')) {
            $manager->addHook('theme', $this->getService('theme.hooks.theme'));
        }
        
        // Register storefront hooks with manager if parent theme is Storefront
        if ($this->hasService('theme.hooks.storefront') && $this->isStorefrontTheme()) {
            $manager->addHook('storefront', $this->getService('theme.hooks.storefront'));
        }
        
        // Register WooCommerce hooks with manager if WooCommerce is active
        if (class_exists('WooCommerce') && $this->hasService('theme.hooks.woocommerce')) {
            $manager->addHook('woocommerce', $this->getService('theme.hooks.woocommerce'));
        }
        
        // Register all hooks with WordPress
        $manager->registerHooks();
    }
    
    /**
     * Check if parent theme is Storefront
     * 
     * @return bool True if parent theme is Storefront
     */
    private function isStorefrontTheme(): bool
    {
        $theme = wp_get_theme();
        if ($theme->parent()) {
            return $theme->parent()->get('TextDomain') === 'storefront';
        }
        return $theme->get('TextDomain') === 'storefront';
    }
} 