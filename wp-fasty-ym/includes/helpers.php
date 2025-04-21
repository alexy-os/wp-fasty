<?php
declare(strict_types=1);

/**
 * Helper functions for WP FastY theme
 * 
 * These functions are loaded only in WordPress context
 */

// Run only in WordPress context
if (function_exists('add_filter')) {
    // Adding basic classes for the body
    function wp_fasty_neve_body_classes(array $classes) {
        $classes[] = 'font-sans';
        $classes[] = 'bg-background';
        $classes[] = 'text-foreground';
        $classes[] = 'antialiased';
        return $classes;
    }
    add_filter('body_class', 'wp_fasty_neve_body_classes');
}