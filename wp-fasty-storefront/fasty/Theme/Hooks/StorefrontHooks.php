<?php
declare(strict_types=1);

namespace FastyChild\Theme\Hooks;

/**
 * Storefront theme specific hooks
 */
class StorefrontHooks extends AbstractThemeHook
{
    /**
     * Determine if hooks should be registered
     * 
     * @return bool True if parent theme is Storefront
     */
    public function canRegister(): bool
    {
        return $this->isTheme('storefront');
    }
    
    /**
     * Register Storefront hooks
     * 
     * @return void
     */
    public function register(): void
    {
        // Layout hooks
        $this->addFilter('storefront_page_layout', 'modifyPageLayout');
        
        // Footer hooks
        $this->addFilter('storefront_footer_widget_columns', 'modifyFooterWidgetColumns');
        $this->addFilter('storefront_credit_text', 'modifyCreditText');
        
        // Check if header override is enabled
        if ($this->shouldOverrideHeader()) {
            $this->removeAllActions('storefront_header');
            $this->addAction('storefront_header', 'customHeader');
        }
        
        // Check if footer override is enabled
        if ($this->shouldOverrideFooter()) {
            $this->removeAllActions('storefront_footer');
            $this->addAction('storefront_footer', 'customFooter');
        }
    }
    
    /**
     * Check if header should be overridden
     * 
     * @return bool True if header override is enabled
     */
    protected function shouldOverrideHeader(): bool
    {
        return (bool) $this->getThemeOption('override_header', false);
    }
    
    /**
     * Check if footer should be overridden
     * 
     * @return bool True if footer override is enabled
     */
    protected function shouldOverrideFooter(): bool
    {
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