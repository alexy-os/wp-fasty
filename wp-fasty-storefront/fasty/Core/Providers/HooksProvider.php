<?php
declare(strict_types=1);

/**
 * Core Hooks Provider
 * Base provider for WordPress hooks management
 */

namespace FastyChild\Core\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Core\Hooks\HooksManager;

/**
 * Core hooks provider without theme-specific dependencies
 */
class HooksProvider extends AbstractServiceProvider
{
    /**
     * Register hooks manager service in the container
     * 
     * @return void
     */
    public function register(): void
    {
        // Register hooks manager
        $this->singleton('hooks.manager', function() {
            return new HooksManager($this->container);
        });
    }
    
    /**
     * Boot hooks manager
     * This only sets up the hooks manager itself, not specific hooks
     * Specific hooks will be added by theme providers
     * 
     * @return void
     */
    public function boot(): void
    {
        // Core hooks provider doesn't register any hooks by default
        // This is left to theme-specific providers
        $this->debug('Core hooks provider booted');
    }
} 