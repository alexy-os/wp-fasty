<?php
declare(strict_types=1);

/**
 * ServiceProvider Interface
 * Defines the contract for all service providers
 */

namespace FastyChild\Core;

/**
 * Interface for service providers
 */
interface ServiceProvider
{
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
    
    /**
     * Get the services provided by the provider
     * Used for deferred loading
     * 
     * @return array<string> Array of service identifiers
     */
    public function provides(): array;
} 