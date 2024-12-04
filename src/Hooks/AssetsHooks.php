<?php
namespace WPFasty\Hooks;

class AssetsHooks extends AbstractHooks {
    public function register(): void {
        $this->addAction('wp_enqueue_scripts', 'enqueueStyles');
        $this->addAction('wp_enqueue_scripts', 'enqueueScripts');
    }

    public function enqueueStyles(): void {
        wp_enqueue_style(
            'wp-fasty-style',
            get_stylesheet_uri(),
            [],
            wp_get_theme()->get('Version')
        );
    }

    public function enqueueScripts(): void {
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
    }
} 