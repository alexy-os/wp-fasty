<?php
/**
 * Bootstrap file for the FastyChild framework
 * Initializes the application and registers necessary providers
 */

namespace FastyChild;

use FastyChild\Core\ThemeLoader;

// Ensure this file is not accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get theme loader instance and initialize base application
$theme = ThemeLoader::init();

// Register basic service providers
$theme->registerProvider(Core\Providers\AssetsServiceProvider::class);
$theme->registerProvider(Core\Providers\ThemeServiceProvider::class);
$theme->registerProvider(Core\Providers\HooksProvider::class);

// Load additional providers from configuration (if exists)
if (file_exists(FASTY_CHILD_PATH . '/fasty/config/providers.php')) {
    $providers = include FASTY_CHILD_PATH . '/fasty/config/providers.php';
    if (is_array($providers)) {
        foreach ($providers as $provider) {
            $theme->registerProvider($provider);
        }
    }
}

// Boot the framework - initialize all registered services
$theme->boot();

// Add action to notify when framework is fully loaded
do_action('fasty_child_framework_loaded', $theme);

// Return ThemeLoader instance for potential further use
return $theme; 