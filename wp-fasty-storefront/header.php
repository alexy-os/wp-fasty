<?php
/**
 * The header for our child theme
 */

do_action('storefront_before_header'); ?>

<header id="masthead" class="site-header custom-header" role="banner">
    <?php
    /**
     * Functions hooked into storefront_header action
     *
     * @hooked storefront_header_container                 - 0
     * @hooked storefront_skip_links                       - 5
     * @hooked storefront_social_icons                     - 10
     * @hooked storefront_site_branding                    - 20
     * @hooked storefront_secondary_navigation             - 30
     * @hooked storefront_product_search                   - 40
     * @hooked storefront_header_container_close           - 41
     * @hooked storefront_primary_navigation_wrapper       - 42
     * @hooked storefront_primary_navigation               - 50
     * @hooked storefront_header_cart                      - 60
     * @hooked storefront_primary_navigation_wrapper_close - 68
     */
    do_action('storefront_header'); ?>
</header>

<?php
do_action('storefront_before_content'); 