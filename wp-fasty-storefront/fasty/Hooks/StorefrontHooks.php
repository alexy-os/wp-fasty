<?php
/**
 * Storefront Hooks
 * Handles Storefront theme-specific hooks and overrides
 */

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class StorefrontHooks extends AbstractHooks {
    /**
     * Check if Storefront theme is active
     * 
     * @return bool
     */
    public function canRegister(): bool
    {
        $theme = wp_get_theme();
        return ('storefront' === $theme->get_template());
    }
    
    /**
     * Register hook handlers
     * 
     * @return void
     */
    public function register(): void {
        // Ensure actions are removed before they are executed
        add_action('init', [$this, 'processAllHooksFromConfig'], 5);
        
        // Explicitly process the site header to ensure override
        add_action('init', [$this, 'processHeaderOverride'], 5);
        
        // Main Storefront filters
        add_filter('storefront_page_layout', [$this, 'modifyPageLayout']);
        add_filter('storefront_footer_widget_columns', [$this, 'modifyFooterWidgetColumns']);
        add_filter('storefront_credit_text', [$this, 'modifyCreditText']);
    }
    
    /**
     * Processes all hooks from configuration
     */
    public function processAllHooksFromConfig(): void {
        $app = $this->container->get('app');
        $hooks_config = $app->config('storefront.hooks', []);
        
        // Iterate through all hooks from configuration (storefront_header, storefront_footer, etc.)
        foreach ($hooks_config as $hook_name => $config) {
            // Remove hooks specified in the 'remove' section
            if (isset($config['remove']) && is_array($config['remove'])) {
                foreach ($config['remove'] as $callback => $priority) {
                    remove_action($hook_name, $callback, $priority);
                }
            }
            
            // Add hooks specified in the 'add' section
            if (isset($config['add']) && is_array($config['add'])) {
                foreach ($config['add'] as $method => $priority) {
                    // Check if the method exists in this class
                    if (method_exists($this, $method)) {
                        add_action($hook_name, [$this, $method], $priority);
                    }
                }
            }
        }
    }
    
    /**
     * Processes header override if configured
     */
    public function processHeaderOverride(): void {
        $app = $this->container->get('app');
        
        // Check the override_header setting
        if ($app->config('storefront.override_header', false)) {
            error_log("[" . FASTY_PREFIX . "INFO] Overriding Storefront header");
            
            // Remove all actions for the storefront_header hook
            remove_all_actions('storefront_header');
            
            // Add our custom header
            add_action('storefront_header', [$this, 'customHeader'], 10);
        }
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
     * Custom header implementation
     * 
     * @return void
     */
    public function customHeader(): void {
        // Check if Storefront functions exist before using them
        $can_show_title_logo = function_exists('storefront_site_title_or_logo');
        $can_show_navigation = function_exists('storefront_primary_navigation');
        $can_show_cart = function_exists('storefront_header_cart');
        $can_show_search = function_exists('storefront_product_search');
        
        if (!$can_show_title_logo) {
            error_log("[" . FASTY_PREFIX . "ERROR] Function 'storefront_site_title_or_logo' not found");
        }
        
        ?>
        <header class="fasty-navbar">
            <div class="fasty-navbar-container">
                <!-- Brand (logo/name) on the left -->
                <div class="fasty-navbar-brand">
                    <?php if ($can_show_title_logo): ?>
                        <?php storefront_site_title_or_logo(); ?>
                    <?php else: ?>
                        <!-- Fallback if the function is not available -->
                        <h1 class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></h1>
                    <?php endif; ?>
                </div>
                
                <!-- Navigation and actions on the right -->
                <div class="fasty-navbar-actions">
                    <!-- Main navigation -->
                    <?php if ($can_show_navigation): ?>
                        <nav class="fasty-navbar-menu">
                            <?php storefront_primary_navigation(); ?>
                        </nav>
                    <?php endif; ?>
                    
                    <!-- Right icons (cart, search, etc.) -->
                    <div class="fasty-navbar-icons">
                        <?php if ($can_show_cart): ?>
                            <div class="fasty-navbar-cart">
                                <?php storefront_header_cart(); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($can_show_search): ?>
                            <div class="fasty-navbar-search">
                                <?php storefront_product_search(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Mobile menu button (visible only on small screens) -->
                <button class="fasty-navbar-toggle" aria-label="<?php esc_attr_e('Toggle menu', FASTY_TEXTDOMAIN); ?>">
                    <span class="fasty-navbar-toggle-bar"></span>
                    <span class="fasty-navbar-toggle-bar"></span>
                    <span class="fasty-navbar-toggle-bar"></span>
                </button>
            </div>
        </header>
        
        <!-- Mobile menu (hidden by default) -->
        <div class="fasty-mobile-menu">
            <?php if ($can_show_navigation): ?>
                <?php storefront_primary_navigation(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
} 