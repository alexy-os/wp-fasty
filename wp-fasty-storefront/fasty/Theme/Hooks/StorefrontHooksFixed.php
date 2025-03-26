<?php
declare(strict_types=1);

namespace FastyChild\Theme\Hooks;

/**
 * Storefront theme specific hooks - corrected version
 */
class StorefrontHooksFixed extends AbstractThemeHook
{
    /**
     * Determine if hooks should be registered
     * 
     * @return bool True if parent theme is Storefront
     */
    public function canRegister(): bool
    {
        // Always return true for testing
        $result = true;
        
        $this->debug('StorefrontHooksFixed::canRegister() called', [
            'result' => $result,
            'class' => get_class($this)
        ]);
        
        return $result;
    }
    
    /**
     * Register Storefront hooks
     * Prioritized registration on early_loading hooks to ensure loading before the parent theme
     * 
     * @return void
     */
    public function register(): void
    {
        $this->debug('StorefrontHooksFixed::register() called', [
            'class' => get_class($this)
        ]);
        
        // Register the header hook on init with a low priority,
        // ensuring it loads before the Storefront theme is activated
        add_action('init', function() {
            $this->debug('Init action executed, registering Storefront header hooks');
            
            // Layout hooks
            add_filter('storefront_page_layout', [$this, 'modifyPageLayout']);
            
            // Footer hooks
            add_filter('storefront_footer_widget_columns', [$this, 'modifyFooterWidgetColumns']);
            add_filter('storefront_credit_text', [$this, 'modifyCreditText']);
            
            // Header override
            if ($this->shouldOverrideHeader()) {
                $this->debug('Header override enabled from init hook');
                remove_all_actions('storefront_header');
                add_action('storefront_header', [$this, 'customHeader'], 10);
                $this->debug('Header actions registered');
            }
            
            // Footer override
            if ($this->shouldOverrideFooter()) {
                $this->debug('Footer override enabled from init hook');
                remove_all_actions('storefront_footer');
                add_action('storefront_footer', [$this, 'customFooter'], 10);
            }
        }, 5); // Low priority for early execution
    }
    
    /**
     * Check if header should be overridden
     * 
     * @return bool True if header override is enabled
     */
    protected function shouldOverrideHeader(): bool
    {
        // For testing always return true
        $this->debug('shouldOverrideHeader called, returning true for testing');
        return true;
    }
    
    /**
     * Check if footer should be overridden
     * 
     * @return bool True if footer override is enabled
     */
    protected function shouldOverrideFooter(): bool
    {
        // Get config from Application
        if ($this->container->has('app')) {
            $app = $this->container->get('app');
            return (bool) $app->config('storefront.override_footer', false);
        }
        
        // Fallback to theme option if app not available
        return (bool) $this->getThemeOption('override_footer', false);
    }
    
    /**
     * Modify page layout
     * 
     * @param string $layout Default layout
     * @return string Modified layout
     */
    public function modifyPageLayout(string $layout): string
    {
        $customLayout = $this->getThemeOption('page_layout', null);
        
        if ($customLayout && in_array($customLayout, ['left', 'right', 'full-width'])) {
            return $customLayout;
        }
        
        return $layout;
    }
    
    /**
     * Modify footer widget columns
     * 
     * @param int $columns Default number of columns
     * @return int Modified number of columns
     */
    public function modifyFooterWidgetColumns(int $columns): int
    {
        $customColumns = (int) $this->getThemeOption('footer_widget_columns', 0);
        
        if ($customColumns > 0 && $customColumns <= 6) {
            return $customColumns;
        }
        
        return $columns;
    }
    
    /**
     * Modify credit text
     * 
     * @param string $text Default credit text
     * @return string Modified credit text
     */
    public function modifyCreditText(string $text): string
    {
        $customText = $this->getThemeOption('credit_text', '');
        
        if (!empty($customText)) {
            return $customText;
        }
        
        return $text;
    }
    
    /**
     * Custom header implementation
     * 
     * @return void
     */
    public function customHeader(): void
    {
        // Add logging for tracing the call
        $this->debug('StorefrontHooksFixed::customHeader() called - Rendering custom header!');
        
        // Add a visible HTML tag to confirm that our hook has been triggered
        echo "<!-- FASTY FIXED CUSTOM HEADER START -->";
        
        // Check if template exists in child theme
        $templatePath = get_stylesheet_directory() . '/template-parts/header.php';
        
        if (file_exists($templatePath)) {
            include $templatePath;
            return;
        }
        
        // Fallback to default implementation
        ?>
        <header class="fasty-navbar">
            <div class="fasty-navbar-container">
                <!-- Brand (logo/name) -->
                <div class="fasty-navbar-brand">
                    <?php if (function_exists('storefront_site_title_or_logo')): ?>
                        <?php storefront_site_title_or_logo(); ?>
                    <?php else: ?>
                        <h1 class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a></h1>
                    <?php endif; ?>
                </div>
                
                <!-- Navigation and actions -->
                <div class="fasty-navbar-actions">
                    <!-- Primary navigation -->
                    <?php if (function_exists('storefront_primary_navigation')): ?>
                        <nav class="fasty-navbar-menu">
                            <?php storefront_primary_navigation(); ?>
                        </nav>
                    <?php endif; ?>
                    
                    <!-- Header icons (cart, search, etc.) -->
                    <div class="fasty-navbar-icons">
                        <?php if (function_exists('storefront_header_cart')): ?>
                            <div class="fasty-navbar-cart">
                                <?php storefront_header_cart(); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (function_exists('storefront_product_search')): ?>
                            <div class="fasty-navbar-search">
                                <?php storefront_product_search(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Mobile menu toggle button -->
                <button class="fasty-navbar-toggle" aria-label="<?php esc_attr_e('Toggle menu', FASTY_TEXTDOMAIN); ?>">
                    <span class="fasty-navbar-toggle-bar"></span>
                    <span class="fasty-navbar-toggle-bar"></span>
                    <span class="fasty-navbar-toggle-bar"></span>
                </button>
            </div>
        </header>
        
        <!-- Mobile menu (hidden by default) -->
        <div class="fasty-mobile-menu">
            <?php if (function_exists('storefront_primary_navigation')): ?>
                <?php storefront_primary_navigation(); ?>
            <?php endif; ?>
        </div>
        <?php
        
        // Closing tag
        echo "<!-- FASTY FIXED CUSTOM HEADER END -->";
    }
    
    /**
     * Custom footer implementation
     * 
     * @return void
     */
    public function customFooter(): void
    {
        // Check if template exists in child theme
        $templatePath = get_stylesheet_directory() . '/template-parts/footer.php';
        
        if (file_exists($templatePath)) {
            include $templatePath;
            return;
        }
        
        // Fallback to default storefront footer
        if (function_exists('storefront_credit')) {
            storefront_credit();
        } else {
            ?>
            <div class="site-info">
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>
            </div>
            <?php
        }
    }
} 