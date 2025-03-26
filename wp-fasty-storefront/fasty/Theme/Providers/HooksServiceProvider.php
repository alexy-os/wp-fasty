<?php
declare(strict_types=1);

namespace FastyChild\Theme\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Core\Hooks\HooksManager;
use FastyChild\Theme\Hooks\ThemeHooks;
use FastyChild\Theme\Hooks\StorefrontHooksFixed;

class HooksServiceProvider extends AbstractServiceProvider
{
    /**
     * Register hooks manager and hook implementations
     * 
     * @return void
     */
    public function register(): void
    {
        $this->debug('HooksServiceProvider::register() called');
        
        // Register hooks manager
        $this->singleton('hooks.manager', function() {
            return new HooksManager($this->container);
        });
        
        // Register hooks from configuration if available
        $hooksConfig = $this->hasService('config.hooks') 
            ? $this->getService('config.hooks') 
            : [];
            
        if (!empty($hooksConfig)) {
            $this->debug('Registering hooks from config', ['config' => $hooksConfig]);
            $this->registerHooksFromConfig($hooksConfig);
        } else {
            $this->debug('No hooks config found, registering default hooks');
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
            $this->debug("Registering hook from config: {$name}", ['class' => $hookClass]);
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
        
        $this->debug('Registering default hooks', [
            'theme' => ThemeHooks::class,
            'storefront' => StorefrontHooksFixed::class
        ]);
        
        $manager->addHook('theme', ThemeHooks::class)
                ->addHook('storefront', StorefrontHooksFixed::class);
    }
    
    /**
     * Boot hooks - register them with WordPress
     * 
     * @return void
     */
    public function boot(): void
    {
        $this->debug('HooksServiceProvider::boot() called');
        $manager = $this->getService('hooks.manager');
        $hooks = $manager->getHooks();
        
        $this->debug('Registered hooks before booting:', array_keys($hooks));
        
        $manager->registerHooks();
    }
} 