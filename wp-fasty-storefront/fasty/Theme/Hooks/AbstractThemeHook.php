<?php
declare(strict_types=1);

namespace FastyChild\Theme\Hooks;

use FastyChild\Core\Hooks\AbstractHook;

/**
 * Abstract base class for theme-specific hooks
 */
abstract class AbstractThemeHook extends AbstractHook
{
    /**
     * Check if current theme is the given theme or child of it
     * 
     * @param string $themeSlug Theme slug/text domain to check
     * @return bool True if current theme matches or is child of specified theme
     */
    protected function isTheme(string $themeSlug): bool
    {
        $theme = wp_get_theme();
        
        // Check if this is the theme itself
        if ($theme->get('TextDomain') === $themeSlug) {
            return true;
        }
        
        // Check if this is a child theme
        $parent = $theme->parent();
        if ($parent && $parent->get('TextDomain') === $themeSlug) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if WooCommerce is active
     * 
     * @return bool True if WooCommerce is active
     */
    protected function isWooCommerceActive(): bool
    {
        return class_exists('WooCommerce');
    }
    
    /**
     * Get theme option with fallback
     * 
     * @param string $option Option name
     * @param mixed $default Default value
     * @return mixed Option value or default
     */
    protected function getThemeOption(string $option, $default = null)
    {
        // Try to get option from theme mods
        $value = get_theme_mod($option, null);
        
        // If not found in theme mods, try regular options
        if ($value === null) {
            $value = get_option($option, $default);
        }
        
        return $value;
    }
    
    /**
     * Load template part from theme
     * 
     * @param string $slug Template slug
     * @param string $name Template name
     * @param array $args Additional arguments
     * @return void
     */
    protected function getTemplatePart(string $slug, string $name = '', array $args = []): void
    {
        if (function_exists('get_template_part')) {
            if (empty($args)) {
                get_template_part($slug, $name);
            } else {
                // WP 5.5+ has args parameter
                get_template_part($slug, $name, $args);
            }
        }
    }
} 