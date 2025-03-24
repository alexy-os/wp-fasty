<?php
/**
 * ServiceProvider Interface
 * Defines the contract for all service providers
 */

namespace FastyChild\Core;

interface ServiceProvider {
    /**
     * Register services in the container
     * 
     * @return void
     */
    public function register(): void;
    
    /**
     * Boot services after all providers are registered
     * 
     * @return void
     */
    public function boot(): void;
} 