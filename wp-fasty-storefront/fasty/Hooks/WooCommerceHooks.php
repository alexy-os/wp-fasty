<?php
/**
 * WooCommerce Hooks
 * Handles WooCommerce-specific customizations
 */

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class WooCommerceHooks {
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
        // Only proceed if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Product listings
        add_filter('woocommerce_product_loop_title_classes', [$this, 'modifyProductTitleClasses']);
        add_filter('woocommerce_sale_flash', [$this, 'modifySaleFlash'], 10, 3);
        
        // Modify number of related products
        add_filter('woocommerce_output_related_products_args', [$this, 'modifyRelatedProductsArgs']);
        
        // Modify checkout fields
        add_filter('woocommerce_checkout_fields', [$this, 'modifyCheckoutFields']);
        
        // Override WooCommerce templates
        add_filter('woocommerce_locate_template', [$this, 'overrideWooCommerceTemplates'], 10, 3);
        
        // Modify product tabs
        add_filter('woocommerce_product_tabs', [$this, 'modifyProductTabs']);
        
        // Override WooCommerce hooks from config
        $this->overrideWooCommerceHooks();
    }
    
    /**
     * Modify product title classes
     * 
     * @param string $classes Default classes
     * @return string Modified classes
     */
    public function modifyProductTitleClasses(string $classes): string {
        $app = $this->container->get('app');
        $custom_classes = $app->config('woocommerce.product_title_classes', '');
        
        if (!empty($custom_classes)) {
            return $classes . ' ' . $custom_classes;
        }
        
        return $classes;
    }
    
    /**
     * Modify sale flash
     * 
     * @param string $html Sale flash HTML
     * @param \WP_Post $post Post object
     * @param \WC_Product $product Product object
     * @return string Modified HTML
     */
    public function modifySaleFlash(string $html, $post, $product): string {
        $app = $this->container->get('app');
        $custom_flash = $app->config('woocommerce.sale_flash', '');
        
        if (!empty($custom_flash)) {
            $percentage = '';
            
            // If we need to show percentage discount
            if (strpos($custom_flash, '{percentage}') !== false && $product->is_on_sale() && $product->get_regular_price()) {
                $percentage = round((($product->get_regular_price() - $product->get_sale_price()) / $product->get_regular_price()) * 100);
                $custom_flash = str_replace('{percentage}', $percentage, $custom_flash);
            }
            
            return '<span class="onsale">' . $custom_flash . '</span>';
        }
        
        return $html;
    }
    
    /**
     * Modify related products args
     * 
     * @param array $args Default args
     * @return array Modified args
     */
    public function modifyRelatedProductsArgs(array $args): array {
        $app = $this->container->get('app');
        $custom_args = $app->config('woocommerce.related_products', []);
        
        if (!empty($custom_args)) {
            return array_merge($args, $custom_args);
        }
        
        return $args;
    }
    
    /**
     * Modify checkout fields
     * 
     * @param array $fields Default fields
     * @return array Modified fields
     */
    public function modifyCheckoutFields(array $fields): array {
        $app = $this->container->get('app');
        $checkout_fields = $app->config('woocommerce.checkout_fields', []);
        
        if (!empty($checkout_fields)) {
            // Process field modifications
            foreach ($checkout_fields as $section => $section_fields) {
                if (!isset($fields[$section])) {
                    continue;
                }
                
                foreach ($section_fields as $field => $props) {
                    // Remove fields
                    if (isset($props['remove']) && $props['remove'] === true) {
                        unset($fields[$section][$field]);
                        continue;
                    }
                    
                    // Modify existing fields
                    if (isset($fields[$section][$field])) {
                        $fields[$section][$field] = array_merge($fields[$section][$field], $props);
                    }
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * Override WooCommerce templates
     * 
     * @param string $template Template path
     * @param string $template_name Template name
     * @param string $template_path Template directory
     * @return string Modified template path
     */
    public function overrideWooCommerceTemplates(string $template, string $template_name, string $template_path): string {
        $theme_file = FASTY_CHILD_PATH . '/woocommerce/' . $template_name;
        
        // Check if template exists in child theme
        if (file_exists($theme_file)) {
            return $theme_file;
        }
        
        return $template;
    }
    
    /**
     * Modify product tabs
     * 
     * @param array $tabs Default tabs
     * @return array Modified tabs
     */
    public function modifyProductTabs(array $tabs): array {
        $app = $this->container->get('app');
        $tabs_config = $app->config('woocommerce.product_tabs', []);
        
        if (!empty($tabs_config)) {
            // Remove tabs
            if (isset($tabs_config['remove']) && is_array($tabs_config['remove'])) {
                foreach ($tabs_config['remove'] as $tab) {
                    unset($tabs[$tab]);
                }
            }
            
            // Add new tabs
            if (isset($tabs_config['add']) && is_array($tabs_config['add'])) {
                foreach ($tabs_config['add'] as $tab_id => $tab) {
                    $tabs[$tab_id] = $tab;
                }
            }
            
            // Reorder tabs
            if (isset($tabs_config['reorder']) && is_array($tabs_config['reorder'])) {
                foreach ($tabs_config['reorder'] as $tab_id => $priority) {
                    if (isset($tabs[$tab_id])) {
                        $tabs[$tab_id]['priority'] = $priority;
                    }
                }
            }
        }
        
        return $tabs;
    }
    
    /**
     * Override WooCommerce hooks
     * 
     * @return void
     */
    private function overrideWooCommerceHooks(): void {
        $app = $this->container->get('app');
        $hooks_config = $app->config('woocommerce.hooks', []);
        
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
    }
} 