<?php
/**
 * Assets Configuration
 * Configuration for theme styles and scripts
 */

return [
    // Styles for frontend
    'styles' => [
        'main' => '/assets/css/theme.min.css',
        /*'custom' => [
            'src' => '/assets/css/custom.css',
            'deps' => ['wp-block-library'],
            'media' => 'all'
        ]*/
    ],
    
    // Scripts for frontend
    'scripts' => [
        'main' => '/assets/js/theme.min.js',
        /*'custom' => [
            'src' => '/assets/js/custom.js',
            'deps' => ['jquery', 'wp-util'],
            'in_footer' => true,
            'localize' => [
                'object_name' => 'fastyChildData',
                'data' => [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('fasty-child-nonce')
                ]
            ]
        ]*/
    ],
    
    /*// Admin styles
    'admin_styles' => [
        'admin' => '/assets/css/admin.css'
    ],
    
    // Admin scripts
    'admin_scripts' => [
        'admin' => '/assets/js/admin.js'
    ]*/
]; 