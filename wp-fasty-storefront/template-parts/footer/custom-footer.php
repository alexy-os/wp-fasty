<?php
/**
 * Template part for displaying the custom footer
 *
 * @package wp-fasty-storefront
 */

// Get application instance to access config
$app = FastyChild\Core\Application::getInstance();
$footer_credit = get_theme_mod('fasty_footer_credit', $app->config('storefront.credit_text', ''));
?>

<div class="fasty-custom-footer">
    <div class="container">
        <div class="site-info">
            <?php if ($footer_credit): ?>
                <div class="fasty-footer-credit">
                    <?php echo wp_kses_post($footer_credit); ?>
                </div>
            <?php endif; ?>
            
            <?php if (has_nav_menu('footer')): ?>
                <nav class="footer-navigation" aria-label="<?php esc_attr_e('Footer Navigation', FASTY_TEXTDOMAIN); ?>">
                    <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'footer',
                            'container' => false,
                            'depth' => 1,
                            'menu_class' => 'footer-menu',
                            'fallback_cb' => false,
                        )
                    );
                    ?>
                </nav>
            <?php endif; ?>
            
            <?php if (has_nav_menu('social')): ?>
                <nav class="social-navigation" aria-label="<?php esc_attr_e('Social Links', FASTY_TEXTDOMAIN); ?>">
                    <?php
                    wp_nav_menu(
                        array(
                            'theme_location' => 'social',
                            'container' => false,
                            'depth' => 1,
                            'menu_class' => 'social-links-menu',
                            'link_before' => '<span class="screen-reader-text">',
                            'link_after' => '</span>',
                            'fallback_cb' => false,
                        )
                    );
                    ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
// Fire action for anything to be added below the footer
do_action('fasty_after_footer'); 
?> 