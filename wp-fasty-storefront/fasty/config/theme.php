<?php
/**
 * Theme Configuration
 * Configuration for theme features and settings
 */

return [
    // Theme supports
    'supports' => [
        'title-tag' => true,
        'post-thumbnails' => true,
        'custom-logo' => [
            'height' => 100,
            'width' => 400,
            'flex-height' => true,
            'flex-width' => true,
        ],
        'html5' => [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ],
        'custom-background' => [
            'default-color' => 'ffffff',
            'default-image' => '',
        ],
        'responsive-embeds' => true,
        'woocommerce' => true,
    ],
    
    // Navigation menus
    'menus' => [
        'primary' => __('Primary Menu', 'wp-fasty-storefront'),
        'footer' => __('Footer Menu', 'wp-fasty-storefront'),
        'social' => __('Social Media Menu', 'wp-fasty-storefront'),
    ],
    
    // Custom image sizes
    'image_sizes' => [
        'fasty-featured' => [
            'width' => 1200,
            'height' => 600,
            'crop' => true,
        ],
        'fasty-square' => [
            'width' => 600,
            'height' => 600,
            'crop' => true,
        ],
    ],
    
    // Widget areas
    'widget_areas' => [
        'sidebar-1' => [
            'name' => __('Primary Sidebar', 'wp-fasty-storefront'),
            'description' => __('Main sidebar that appears on the left or right.', 'wp-fasty-storefront'),
        ],
        'footer-1' => [
            'name' => __('Footer Widget Area 1', 'wp-fasty-storefront'),
            'description' => __('Appears in the footer section of the site.', 'wp-fasty-storefront'),
        ],
        'footer-2' => [
            'name' => __('Footer Widget Area 2', 'wp-fasty-storefront'),
            'description' => __('Appears in the footer section of the site.', 'wp-fasty-storefront'),
        ],
        'footer-3' => [
            'name' => __('Footer Widget Area 3', 'wp-fasty-storefront'),
            'description' => __('Appears in the footer section of the site.', 'wp-fasty-storefront'),
        ],
    ],
    
    // Customizer settings
    'customizer' => [
        'panels' => [
            'fasty_theme_options' => [
                'title' => __('Fasty Theme Options', 'wp-fasty-storefront'),
                'priority' => 130,
            ],
        ],
        'sections' => [
            'fasty_header_options' => [
                'title' => __('Header Options', 'wp-fasty-storefront'),
                'panel' => 'fasty_theme_options',
                'priority' => 10,
            ],
            'fasty_footer_options' => [
                'title' => __('Footer Options', 'wp-fasty-storefront'),
                'panel' => 'fasty_theme_options',
                'priority' => 20,
            ],
        ],
        'settings' => [
            'fasty_header_style' => [
                'args' => [
                    'default' => 'default',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'control' => [
                    'type' => 'select',
                    'args' => [
                        'label' => __('Header Style', 'wp-fasty-storefront'),
                        'section' => 'fasty_header_options',
                        'choices' => [
                            'default' => __('Default', 'wp-fasty-storefront'),
                            'centered' => __('Centered', 'wp-fasty-storefront'),
                            'minimal' => __('Minimal', 'wp-fasty-storefront'),
                        ],
                    ],
                ],
            ],
            'fasty_footer_credit' => [
                'args' => [
                    'default' => __('Powered by FastyChild Framework', 'wp-fasty-storefront'),
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'control' => [
                    'type' => 'text',
                    'args' => [
                        'label' => __('Footer Credit Text', 'wp-fasty-storefront'),
                        'section' => 'fasty_footer_options',
                    ],
                ],
            ],
        ],
    ],
    
    // Theme optimization
    'clean_head' => true,
    'disable_emoji' => true,
    
    // Content settings
    'excerpt_length' => 30,
    'excerpt_more' => '&hellip; <a href="%s" class="read-more">Read More</a>',
    
    // Custom body classes
    'body_classes' => [
        'fasty-theme',
        'custom-background',
    ],
]; 