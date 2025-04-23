<?php

/**
 * WP Fasty Theme Functions
 */

// Composer autoload
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Initialize application
require_once __DIR__ . '/wp-fasty-ym/classes/bootstrap.php';

// Other theme functions
// ... 