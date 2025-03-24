<?php
/**
 * Service Providers Configuration
 * List of additional service providers to be registered
 */

return [
    // Core providers are registered automatically
    FastyChild\Core\Providers\AssetsServiceProvider::class,
    FastyChild\Core\Providers\ThemeServiceProvider::class,
    FastyChild\Core\Providers\HooksProvider::class,
    
    // Register your additional service providers here
    // Example:
    // FastyChild\Providers\CustomizerServiceProvider::class,
    // FastyChild\Providers\WidgetsServiceProvider::class,
]; 