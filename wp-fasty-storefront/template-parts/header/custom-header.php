<?php
/**
 * Template part for displaying the custom header
 *
 * @package wp-fasty-storefront
 */

// Get application instance to access config
/*$app = FastyChild\Core\Application::getInstance();
$header_style = get_theme_mod('fasty_header_style', 'default');
?>

<div class="fasty-custom-header fasty-header-style-<?php echo esc_attr($header_style); ?>">
    <div class="container">
        <div class="site-branding">
            <?php //storefront_site_title_or_logo(); ?>
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
            
            <?php if (is_user_logged_in()): ?>
                <div class="fasty-account-link">
                    <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>">
                        <span class="dashicons dashicons-admin-users"></span>
                        <span class="screen-reader-text"><?php esc_html_e('My Account', FASTY_TEXTDOMAIN); ?></span>
                    </a>
                </div>
            <?php else: ?>
                <div class="fasty-login-link">
                    <a href="<?php echo esc_url(get_permalink(get_option('woocommerce_myaccount_page_id'))); ?>">
                        <?php esc_html_e('Login', FASTY_TEXTDOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Fire action for anything to be added below the header
do_action('fasty_after_header'); */
?> 