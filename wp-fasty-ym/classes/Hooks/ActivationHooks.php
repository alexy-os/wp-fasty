<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

use WPFasty\Core\ContainerInterface;

/**
 * Hooks for theme activation events
 */
class ActivationHooks extends AbstractHooks
{
    /**
     * Register hooks
     */
    public function register(): void
    {
        // Hook for after theme activation
        $this->addAction('after_switch_theme', 'onThemeActivated');
        
        // Hook for theme update
        $this->addAction('upgrader_process_complete', 'onThemeUpdated', 10, 2);
    }
    
    /**
     * Handle theme activation
     */
    public function onThemeActivated(): void
    {
        // Trigger generation of context schema
        do_action('wpfasty_update_context_schema');
    }
    
    /**
     * Handle theme update
     * 
     * @param \WP_Upgrader $upgrader The upgrader instance
     * @param array<string, mixed> $data The upgrade data
     */
    public function onThemeUpdated($upgrader, array $data): void
    {
        // Check if it's a theme update
        if (isset($data['type']) && $data['type'] === 'theme') {
            $currentTheme = wp_get_theme();
            
            // Check if our theme was updated
            if (isset($data['themes']) && in_array($currentTheme->get_stylesheet(), $data['themes'], true)) {
                // Trigger generation of context schema
                do_action('wpfasty_update_context_schema');
            }
        }
    }
} 