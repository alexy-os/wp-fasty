<?php
declare(strict_types=1);

namespace FastyChild\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Hooks\HooksManager;
use FastyChild\Hooks\ThemeHooks;
use FastyChild\Hooks\StorefrontHooks;
use FastyChild\Hooks\WooCommerceHooks;

class HooksServiceProvider extends AbstractServiceProvider
{
    /**
     * Register hooks manager and hook implementations
     * 
     * @return void
     */
    public function register(): void
    {
        // Register hooks manager
        $this->singleton('hooks.manager', function() {
            return new HooksManager($this->container);
        });
        
        // Register hooks from configuration if available
        $hooksConfig = $this->hasService('config.hooks') 
            ? $this->getService('config.hooks') 
            : [];
            
        if (!empty($hooksConfig)) {
            $this->registerHooksFromConfig($hooksConfig);
        } else {
            // Register default hooks
            $this->registerDefaultHooks();
        }
    }
    
    /**
     * Register hooks from configuration
     * 
     * @param array $config
     * @return void
     */
    private function registerHooksFromConfig(array $config): void
    {
        $manager = $this->getService('hooks.manager');
        
        foreach ($config as $name => $hookClass) {
            $manager->addHook($name, $hookClass);
        }
    }
    
    /**
     * Register default hooks
     * 
     * @return void
     */
    private function registerDefaultHooks(): void
    {
        $manager = $this->getService('hooks.manager');
        
        $manager->addHook('theme', ThemeHooks::class)
                ->addHook('storefront', StorefrontHooks::class)
                ->addHook('woocommerce', WooCommerceHooks::class);
    }
    
    /**
     * Boot hooks - register them with WordPress
     * 
     * @return void
     */
    public function boot(): void
    {
        $this->getService('hooks.manager')->registerHooks();
    }
} 