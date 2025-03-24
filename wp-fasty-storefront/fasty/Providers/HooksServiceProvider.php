<?php

namespace FastyChild\Providers;

use FastyChild\Core\Container;
use FastyChild\Core\ServiceProvider;
use FastyChild\Hooks\HooksManager;
use FastyChild\Hooks\ThemeHooks;
use FastyChild\Hooks\StorefrontHooks;
use FastyChild\Hooks\WooCommerceHooks;

class HooksServiceProvider implements ServiceProvider
{
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Register hooks manager and hook implementations
     * 
     * @return void
     */
    public function register(): void
    {
        // Register hooks manager
        $this->container->singleton('hooks.manager', function() {
            return new HooksManager($this->container);
        });
        
        // Register hooks from configuration if available
        $hooksConfig = $this->container->has('config.hooks') 
            ? $this->container->get('config.hooks') 
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
        $manager = $this->container->get('hooks.manager');
        
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
        $manager = $this->container->get('hooks.manager');
        
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
        $this->container->get('hooks.manager')->registerHooks();
    }
} 