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
        'primary' => __('Primary Menu', FASTY_TEXTDOMAIN),
        'footer' => __('Footer Menu', FASTY_TEXTDOMAIN),
        'social' => __('Social Media Menu', FASTY_TEXTDOMAIN),
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
            'name' => __('Primary Sidebar', FASTY_TEXTDOMAIN),
            'description' => __('Main sidebar that appears on the left or right.', FASTY_TEXTDOMAIN),
        ],
        'footer-1' => [
            'name' => __('Footer Widget Area 1', FASTY_TEXTDOMAIN),
            'description' => __('Appears in the footer section of the site.', FASTY_TEXTDOMAIN),
        ],
        'footer-2' => [
            'name' => __('Footer Widget Area 2', FASTY_TEXTDOMAIN),
            'description' => __('Appears in the footer section of the site.', FASTY_TEXTDOMAIN),
        ],
        'footer-3' => [
            'name' => __('Footer Widget Area 3', FASTY_TEXTDOMAIN),
            'description' => __('Appears in the footer section of the site.', FASTY_TEXTDOMAIN),
        ],
    ],
    
    // Customizer settings
    'customizer' => [
        'panels' => [
            'fasty_theme_options' => [
                'title' => __('Fasty Theme Options', FASTY_TEXTDOMAIN),
                'priority' => 130,
            ],
        ],
        'sections' => [
            'fasty_header_options' => [
                'title' => __('Header Options', FASTY_TEXTDOMAIN),
                'panel' => 'fasty_theme_options',
                'priority' => 10,
            ],
            'fasty_footer_options' => [
                'title' => __('Footer Options', FASTY_TEXTDOMAIN),
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
                        'label' => __('Header Style', FASTY_TEXTDOMAIN),
                        'section' => 'fasty_header_options',
                        'choices' => [
                            'default' => __('Default', FASTY_TEXTDOMAIN),
                            'centered' => __('Centered', FASTY_TEXTDOMAIN),
                            'minimal' => __('Minimal', FASTY_TEXTDOMAIN),
                        ],
                    ],
                ],
            ],
            'fasty_footer_credit' => [
                'args' => [
                    'default' => __('Powered by FastyChild Framework', FASTY_TEXTDOMAIN),
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'control' => [
                    'type' => 'text',
                    'args' => [
                        'label' => __('Footer Credit Text', FASTY_TEXTDOMAIN),
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