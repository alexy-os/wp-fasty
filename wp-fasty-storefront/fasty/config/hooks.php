<?php

return [
    // Core theme hooks - always loaded
    'theme' => \FastyChild\Theme\Hooks\ThemeHooks::class,
    
    // Conditional hooks - only load if conditions are met
    'storefront' => \FastyChild\Theme\Hooks\StorefrontHooks::class,
    //'woocommerce' => \FastyChild\Theme\Hooks\WooCommerceHooks::class,
    
    // Custom hooks can be added here
    // 'custom' => \FastyChild\Theme\Hooks\CustomHooks::class,
]; 