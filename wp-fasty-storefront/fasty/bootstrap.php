<?php
/**
 * Bootstrap file for the FastyChild framework
 * Initializes the application and registers necessary providers
 */

namespace FastyChild;

use FastyChild\Core\ThemeLoader;
use FastyChild\Hooks\Constants;

// Ensure this file is not accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define framework constants
if (!defined('FASTY_LOG_PREFIX')) {
    define('FASTY_LOG_PREFIX', 'fasty_');
}

// Bootstrap the framework
try {
    // Get theme loader instance and initialize base application
    $theme = ThemeLoader::init();
    
    // Register error handler for development environment
    if (defined('WP_DEBUG') && WP_DEBUG) {
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            if (!(error_reporting() & $errno)) {
                // Ошибка не входит в error_reporting
                return false;
            }
            
            $message = "Error ($errno): $errstr in $errfile on line $errline";
            error_log("[FASTY_ERROR] " . $message);
            
            if (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY) {
                echo "<div style='background-color:#f8d7da;color:#721c24;padding:10px;margin:10px 0;border:1px solid #f5c6cb;border-radius:4px;'>";
                echo "<strong>Fasty Framework Error:</strong> " . esc_html($message);
                echo "</div>";
            }
            
            // Не запускаем стандартный обработчик ошибок PHP
            return true;
        });
    }
    
    // Load providers configuration
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
    do_action(Constants::HOOK_FASTY_FRAMEWORK_LOADED, $theme);
    
    // Return ThemeLoader instance for potential further use
    return $theme;
} catch (\Throwable $e) {
    // Log the error
    error_log("[FASTY_FATAL] Bootstrap error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // Display error message in admin area
    if (is_admin() && current_user_can('manage_options') && defined('WP_DEBUG') && WP_DEBUG) {
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p><strong>Fasty Framework Error:</strong> <?php echo esc_html($e->getMessage()); ?></p>
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                <p>File: <?php echo esc_html($e->getFile()); ?> (Line: <?php echo esc_html($e->getLine()); ?>)</p>
                <pre><?php echo esc_html($e->getTraceAsString()); ?></pre>
                <?php endif; ?>
            </div>
            <?php
        });
    }
    
    // Return null in case of error
    return null;
} 