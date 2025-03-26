<?php
declare(strict_types=1);

/**
 * Hook Constants
 * Contains constants for all hooks used in the framework
 */

namespace FastyChild\Hooks;

/**
 * Constants for hook types
 */
final class Constants
{
    /**
     * Framework version
     */
    public const VERSION = FASTY_VERSION;
    
    /**
     * Prefix for all hooks in the framework
     */
    public const HOOK_PREFIX = FASTY_PREFIX;
    
    /**
     * Framework core hooks
     */
    public const HOOK_FASTY_FRAMEWORK_LOADED = 'fasty_framework_loaded';
    public const HOOK_FASTY_THEME_SETUP = 'fasty_theme_setup';
    public const HOOK_FASTY_LOG = 'fasty_log';
    public const HOOK_FASTY_INIT = 'fasty_init';
    public const HOOK_FASTY_CONFIG_LOADED = 'fasty_config_loaded';
    public const HOOK_FASTY_MIGRATION = 'fasty_migration';
    
    /**
     * Common WordPress hooks
     */
    public const HOOK_INIT = 'init';
    public const HOOK_ADMIN_INIT = 'admin_init';
    public const HOOK_WP_LOADED = 'wp_loaded';
    public const HOOK_WIDGETS_INIT = 'widgets_init';
    public const HOOK_WP_ENQUEUE_SCRIPTS = 'wp_enqueue_scripts';
    public const HOOK_ADMIN_ENQUEUE_SCRIPTS = 'admin_enqueue_scripts';
    
    /**
     * Theme hooks
     */
    public const HOOK_THEME_SETUP = 'after_setup_theme';
    public const HOOK_BODY_CLASS = 'body_class';
    public const HOOK_EXCERPT_MORE = 'excerpt_more';
    public const HOOK_EXCERPT_LENGTH = 'excerpt_length';
    
    /**
     * Storefront hooks
     */
    public const HOOK_STOREFRONT_HEADER = 'storefront_header';
    public const HOOK_STOREFRONT_FOOTER = 'storefront_footer';
    public const HOOK_STOREFRONT_PAGE = 'storefront_page';
    public const HOOK_STOREFRONT_LAYOUT = 'storefront_page_layout';
    public const HOOK_STOREFRONT_FOOTER_WIDGETS = 'storefront_footer_widget_columns';
    public const HOOK_STOREFRONT_CREDIT = 'storefront_credit_text';
    
    /**
     * WooCommerce hooks
     */
    public const HOOK_WOOCOMMERCE_INIT = 'woocommerce_init';
    public const HOOK_WOOCOMMERCE_PRODUCT_TABS = 'woocommerce_product_tabs';
    public const HOOK_WOOCOMMERCE_CHECKOUT_FIELDS = 'woocommerce_checkout_fields';
    public const HOOK_WOOCOMMERCE_SINGLE_PRODUCT = 'woocommerce_single_product_summary';
    
    /**
     * Get all hooks with a specific prefix
     * 
     * @param string $prefix Prefix to filter hooks
     * @return array<string, string> Array of hook constants
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
     * Get all framework hooks
     * 
     * @return array<string, string> Array of framework hook constants
     */
    public static function getFrameworkHooks(): array
    {
        return self::getHooksWithPrefix(self::HOOK_PREFIX);
    }
    
    /**
     * Get all theme hooks
     * 
     * @return array<string, string> Array of theme hook constants
     */
    public static function getThemeHooks(): array
    {
        return array_merge(
            self::getHooksWithPrefix('after_'),
            self::getHooksWithPrefix('body_'),
            self::getHooksWithPrefix('excerpt_')
        );
    }
    
    /**
     * Get all Storefront hooks
     * 
     * @return array<string, string> Array of Storefront hook constants
     */
    public static function getStorefrontHooks(): array
    {
        return self::getHooksWithPrefix('storefront_');
    }
    
    /**
     * Get all WooCommerce hooks
     * 
     * @return array<string, string> Array of WooCommerce hook constants
     */
    public static function getWooCommerceHooks(): array
    {
        return self::getHooksWithPrefix('woocommerce_');
    }
} 