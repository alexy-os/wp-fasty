<?php

declare(strict_types=1);

/**
 * Assets hooks
 *
 * @package WPFasty\Hooks
 */

namespace WPFasty\Hooks;

class AssetsHooks extends AbstractHooks
{
    public function register(): void
    {
        $this->addAction('wp_enqueue_scripts', 'enqueueStyles');
        // $this->addAction('wp_enqueue_scripts', 'enqueueScripts');
    }

    public function enqueueStyles(): void
    {
        // Enqueue main theme style
        wp_enqueue_style(
            'wp-fasty-style',
            get_stylesheet_uri(),
            [],
            wp_get_theme()->get('Version')
        );

        // Enqueue Tailwind styles
        wp_enqueue_style(
            'wp-fasty-ym-tailwind',
            get_template_directory_uri() . '/theme.min.css',
            ['wp-fasty-style'], // Depends on main style
            wp_get_theme()->get('Version')
        );

        // Add inline styles for theme customization
        /*wp_add_inline_style(
            'wp-fasty-ym-tailwind',
            $this->getCustomStyles()
        );*/
    }
    
    private function getCustomStyles(): string
    {
        $custom_css = '';
        
        // Add any dynamic styles here
        if (get_theme_mod('custom_primary_color')) {
            $custom_css .= sprintf(
                ':root { --primary: %s; }',
                esc_attr(get_theme_mod('custom_primary_color'))
            );
        }

        return $custom_css;
    }

    /*public function enqueueScripts(): void {
        $script_path = get_template_directory() . '/assets/js/navigation.js';
        if (!file_exists($script_path)) {
            error_log('Navigation script not found at: ' . $script_path);
            return;
        }

        wp_enqueue_script(
            'wp-fasty-navigation',
            get_template_directory_uri() . '/assets/js/navigation.js',
            [],
            wp_get_theme()->get('Version'),
            true
        );
    }*/
}
