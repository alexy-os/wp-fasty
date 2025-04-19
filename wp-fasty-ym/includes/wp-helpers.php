<?php declare(strict_types=1);
/**
* Helper functions for the theme
*
* @package WPFasty
*/

// Check if we are working in the WordPress context
if (!function_exists('add_filter')) {
   // If we are outside WordPress (e.g. in the context of phpcs)
   return;
}

// Now we can safely use WordPress functions
add_filter('wp_img_tag_add_auto_sizes', '__return_false');

// Adding basic classes for the body
function wp_fasty_neve_body_classes( array $classes ) {
    $classes[] = 'font-sans';
    $classes[] = 'bg-background';
    $classes[] = 'text-foreground';
    $classes[] = 'antialiased';
    return $classes;
}
add_filter('body_class', 'wp_fasty_neve_body_classes');