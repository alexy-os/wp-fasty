<?php
/**
 * Bootstrap file for the FastyChild framework
 * Initializes the application and registers necessary providers
 */

namespace FastyChild;

use FastyChild\Core\ThemeLoader;
use FastyChild\Core\Exceptions\FastyException;
use FastyChild\Core\Exceptions\ContainerException;
use FastyChild\Hooks\Constants;

// Ensure this file is not accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize framework core components
 * 
 * @return ThemeLoader|null
 */
function initializeFramework() 
{
    try {
        // Get theme loader instance and initialize base application
        $theme = ThemeLoader::init();
        
        // Register error handler for development environment
        registerErrorHandler();
        
        return $theme;
    } catch (\Throwable $e) {
        logAndNotifyError($e);
        return null;
    }
}

/**
 * Register error handler for development
 * 
 * @return void
 */
function registerErrorHandler(): void 
{
    if (defined('WP_DEBUG') && WP_DEBUG) {
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            if (!(error_reporting() & $errno)) {
                // Error is not included in error_reporting
                return false;
            }
            
            $errfile = wp_normalize_path($errfile);
            $message = sprintf(
                "Error (%d): %s in %s on line %d",
                $errno,
                esc_html($errstr),
                esc_html($errfile),
                esc_html($errline)
            );
            
            error_log("[" . FASTY_PREFIX . "ERROR] " . $message);
            
            if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
                printf(
                    '<div class="fasty-error" style="background-color:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;">' .
                    '<strong>Fasty Framework Error:</strong> %s' .
                    '</div>',
                    esc_html($message)
                );
            }
            
            // Don't execute PHP's internal error handler
            return true;
        });
    }
}

/**
 * Register service providers from configuration
 * 
 * @param ThemeLoader $themeLoader
 * @return ThemeLoader
 */
function registerProviders(ThemeLoader $themeLoader)
{
    try {
        $providersFile = FASTY_CHILD_PATH . '/fasty/config/providers.php';
        
        if (!file_exists($providersFile)) {
            throw new FastyException(
                'config_file',
                'providers.php',
                'Providers configuration file not found'
            );
        }
        
        if (!is_readable($providersFile)) {
            throw new FastyException(
                'config_file',
                'providers.php',
                'Providers configuration file is not readable'
            );
        }
        
        $providers = include $providersFile;
        
        if (!is_array($providers)) {
            throw new FastyException(
                'config_content',
                'providers.php',
                'Providers configuration must return an array'
            );
        }
        
        foreach ($providers as $provider) {
            // Check if provider should be deferred
            $deferred = false;
            if (is_array($provider)) {
                $deferred = $provider['deferred'] ?? false;
                $provider = $provider['class'];
            }
            
            // Register provider
            $themeLoader->registerProvider($provider, $deferred);
        }
        
        return $themeLoader;
    } catch (\Throwable $e) {
        logAndNotifyError($e);
        return $themeLoader;
    }
}

/**
 * Log and notify about errors
 * 
 * @param \Throwable $e
 * @return void
 */
function logAndNotifyError(\Throwable $e): void
{
    // Log error
    $message = sprintf(
        "[%sFATAL] %s in %s on line %d\nStack trace:\n%s",
        FASTY_PREFIX,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    error_log($message);
    
    // Display error message in admin area for administrators only
    if (is_admin() && current_user_can('manage_options')) {
        add_action('admin_notices', function() use ($e) {
            $message = esc_html($e->getMessage());
            $file = esc_html(wp_normalize_path($e->getFile()));
            $line = esc_html($e->getLine());
            
            printf(
                '<div class="notice notice-error">' .
                '<p><strong>Fasty Framework Error:</strong> %s</p>',
                $message
            );
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                printf(
                    '<p>File: %s (Line: %s)</p>' .
                    '<pre>%s</pre>',
                    $file,
                    $line,
                    esc_html($e->getTraceAsString())
                );
            }
            
            echo '</div>';
        });
    }
}

// Bootstrap the framework
try {
    // Initialize framework core
    $theme = initializeFramework();
    if (!$theme) {
        return null;
    }
    
    // Register service providers
    $theme = registerProviders($theme);
    
    // Boot the framework - initialize all registered services
    $theme->boot();
    
    // Add action to notify when framework is fully loaded
    do_action(Constants::HOOK_FASTY_FRAMEWORK_LOADED, $theme);
    
    // Return ThemeLoader instance for potential further use
    return $theme;
} catch (\Throwable $e) {
    logAndNotifyError($e);
    return null;
} 