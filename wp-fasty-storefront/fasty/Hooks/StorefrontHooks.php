<?php
/**
 * Storefront Hooks
 * Handles Storefront theme-specific hooks and overrides
 */

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class StorefrontHooks {
    /**
     * Container instance
     * @var Container
     */
    private $container;
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    /**
     * Register hook handlers
     * 
     * @return void
     */
    public function register(): void {
        // Modify Storefront layout
        add_filter('storefront_page_layout', [$this, 'modifyPageLayout']);
        
        // Override Storefront structure hooks
        $this->overrideStorefrontHooks();
        
        // Modify Storefront footer widgets
        add_filter('storefront_footer_widget_columns', [$this, 'modifyFooterWidgetColumns']);
        
        // Modify Storefront credit text
        add_filter('storefront_credit_text', [$this, 'modifyCreditText']);
    }
    
    /**
     * Modify page layout
     * 
     * @param string $layout Default layout
     * @return string Modified layout
     */
    public function modifyPageLayout(string $layout): string {
        $app = $this->container->get('app');
        $custom_layout = $app->config('storefront.page_layout', $layout);
        
        return $custom_layout;
    }
    
    /**
     * Modify footer widget columns
     * 
     * @param int $columns Default number of columns
     * @return int Modified number of columns
     */
    public function modifyFooterWidgetColumns(int $columns): int {
        $app = $this->container->get('app');
        $custom_columns = $app->config('storefront.footer_widget_columns', $columns);
        
        return $custom_columns;
    }
    
    /**
     * Modify credit text
     * 
     * @param string $text Default credit text
     * @return string Modified credit text
     */
    public function modifyCreditText(string $text): string {
        $app = $this->container->get('app');
        $custom_text = $app->config('storefront.credit_text', $text);
        
        if ($custom_text !== $text) {
            return $custom_text;
        }
        
        // If no custom text is defined, you can still modify the default
        return $text;
    }
    
    /**
     * Override Storefront hooks
     * 
     * @return void
     */
    private function overrideStorefrontHooks(): void {
        $app = $this->container->get('app');
        $hooks_config = $app->config('storefront.hooks', []);
        
        // Remove hooks that need to be replaced
        foreach ($hooks_config as $hook => $config) {
            if (isset($config['remove']) && is_array($config['remove'])) {
                foreach ($config['remove'] as $callback => $priority) {
                    remove_action($hook, $callback, $priority);
                }
            }
        }
        
        // Add replacement hooks
        foreach ($hooks_config as $hook => $config) {
            if (isset($config['add']) && is_array($config['add'])) {
                foreach ($config['add'] as $method => $priority) {
                    if (method_exists($this, $method)) {
                        add_action($hook, [$this, $method], $priority);
                    }
                }
            }
        }
        
        // Example: Replace Storefront header
        if ($app->config('storefront.override_header', false)) {
            remove_action('storefront_header', 'storefront_header_container', 0);
            remove_action('storefront_header', 'storefront_skip_links', 5);
            remove_action('storefront_header', 'storefront_site_branding', 20);
            remove_action('storefront_header', 'storefront_secondary_navigation', 30);
            remove_action('storefront_header', 'storefront_primary_navigation_wrapper', 42);
            remove_action('storefront_header', 'storefront_primary_navigation', 50);
            remove_action('storefront_header', 'storefront_header_cart', 60);
            remove_action('storefront_header', 'storefront_primary_navigation_wrapper_close', 68);
            
            add_action('storefront_header', [$this, 'customHeader'], 10);
        }
    }
    
    /**
     * Custom header implementation
     * 
     * @return void
     */
    public function customHeader(): void {
        ?>
        <div class="fasty-custom-header">
            <div class="site-branding">
                <?php storefront_site_title_or_logo(); ?>
            </div>
            
            <div class="fasty-header-navigation">
                <?php 
                if (function_exists('storefront_primary_navigation')) {
                    storefront_primary_navigation();
                }
                ?>
            </div>
            
            <div class="fasty-header-actions">
                <?php
                if (function_exists('storefront_header_cart')) {
                    storefront_header_cart();
                }
                
                if (function_exists('storefront_product_search')) {
                    storefront_product_search();
                }
                ?>
            </div>
        </div>
        <?php
    }
} 