<?php
/**
 * Тестовый файл для прямого перехвата хука storefront_header
 */

// Удалить все существующие действия на хуке
remove_all_actions('storefront_header');

// Добавить наше собственное действие
add_action('storefront_header', function() {
    echo '<!-- FASTY DIRECT CUSTOM HEADER START -->';
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
            <button class="fasty-navbar-toggle" aria-label="<?php esc_attr_e('Toggle menu', 'wp-fasty'); ?>">
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
    echo '<!-- FASTY DIRECT CUSTOM HEADER END -->';
}, 10); 