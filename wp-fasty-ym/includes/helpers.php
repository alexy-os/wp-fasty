<?php
declare(strict_types=1);

// Adding basic classes for the body
function wp_fasty_neve_body_classes( array $classes ) {
    $classes[] = 'font-sans';
    $classes[] = 'bg-background';
    $classes[] = 'text-foreground';
    $classes[] = 'antialiased';
    return $classes;
}
add_filter('body_class', 'wp_fasty_neve_body_classes');