<?php

return [
    // Core theme hooks - always loaded
    'theme' => \FastyChild\Hooks\ThemeHooks::class,
    
    // Conditional hooks - only load if conditions are met
    'storefront' => \FastyChild\Hooks\StorefrontHooks::class,
    'woocommerce' => \FastyChild\Hooks\WooCommerceHooks::class,
    
    // Custom hooks can be added here
    // 'custom' => \FastyChild\Hooks\CustomHooks::class,
]; 