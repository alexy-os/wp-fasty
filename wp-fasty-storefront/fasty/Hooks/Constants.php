<?php
/**
 * Hook Constants
 * Contains constants for all hooks used in the framework
 */

namespace FastyChild\Hooks;

/**
 * Constants for hook types
 */
class Constants
{
    /**
     * Prefix for all hooks in the framework
     */
    const HOOK_PREFIX = 'fasty_';
    
    /**
     * Common WordPress hooks
     */
    const HOOK_INIT = 'init';
    const HOOK_ADMIN_INIT = 'admin_init';
    const HOOK_WP_LOADED = 'wp_loaded';
    const HOOK_WIDGETS_INIT = 'widgets_init';
    const HOOK_WP_ENQUEUE_SCRIPTS = 'wp_enqueue_scripts';
    const HOOK_ADMIN_ENQUEUE_SCRIPTS = 'admin_enqueue_scripts';
    
    /**
     * Theme hooks
     */
    const HOOK_THEME_SETUP = 'after_setup_theme';
    const HOOK_BODY_CLASS = 'body_class';
    const HOOK_EXCERPT_MORE = 'excerpt_more';
    const HOOK_EXCERPT_LENGTH = 'excerpt_length';
    
    /**
     * Storefront hooks
     */
    const HOOK_STOREFRONT_HEADER = 'storefront_header';
    const HOOK_STOREFRONT_FOOTER = 'storefront_footer';
    const HOOK_STOREFRONT_PAGE = 'storefront_page';
    const HOOK_STOREFRONT_LAYOUT = 'storefront_page_layout';
    const HOOK_STOREFRONT_FOOTER_WIDGETS = 'storefront_footer_widget_columns';
    const HOOK_STOREFRONT_CREDIT = 'storefront_credit_text';
    
    /**
     * WooCommerce hooks
     */
    const HOOK_WOOCOMMERCE_INIT = 'woocommerce_init';
    const HOOK_WOOCOMMERCE_PRODUCT_TABS = 'woocommerce_product_tabs';
    const HOOK_WOOCOMMERCE_CHECKOUT_FIELDS = 'woocommerce_checkout_fields';
    const HOOK_WOOCOMMERCE_SINGLE_PRODUCT = 'woocommerce_single_product_summary';
    
    /**
     * Framework hooks
     */
    const HOOK_FASTY_FRAMEWORK_LOADED = 'fasty_framework_loaded';
    const HOOK_FASTY_THEME_SETUP = 'fasty_theme_setup';
    const HOOK_FASTY_LOG = 'fasty_log';
    
    /**
     * Get all WordPress hooks with a specific prefix
     * 
     * @param string $prefix Prefix to filter hooks
     * @return array Array of hook constants
     */
    public static function getHooksWithPrefix(string $prefix): array
    {
        $hooks = [];
        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        
        foreach ($constants as $name => $value) {
            if (strpos($name, 'HOOK_') === 0 && strpos($value, $prefix) === 0) {
                $hooks[$name] = $value;
            }
        }
        
        return $hooks;
    }
    
    /**
     * Get all theme hooks
     * 
     * @return array Array of theme hook constants
     */
    public static function getThemeHooks(): array
    {
        return self::getHooksWithPrefix('');
    }
    
    /**
     * Get all Storefront hooks
     * 
     * @return array Array of Storefront hook constants
     */
    public static function getStorefrontHooks(): array
    {
        return self::getHooksWithPrefix('storefront_');
    }
    
    /**
     * Get all WooCommerce hooks
     * 
     * @return array Array of WooCommerce hook constants
     */
    public static function getWooCommerceHooks(): array
    {
        return self::getHooksWithPrefix('woocommerce_');
    }
    
    /**
     * Get all framework-specific hooks
     * 
     * @return array Array of framework hook constants
     */
    public static function getFrameworkHooks(): array
    {
        return self::getHooksWithPrefix('fasty_');
    }
} 