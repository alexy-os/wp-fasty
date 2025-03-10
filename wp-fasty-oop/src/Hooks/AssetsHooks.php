<?php
namespace WPFasty\Hooks;

class AssetsHooks extends AbstractHooks {
    public function register(): void {
        $this->addAction('wp_enqueue_scripts', 'enqueueStyles');
        $this->addAction('wp_enqueue_scripts', 'enqueueScripts');
    }

    public function enqueueStyles(): void {
        // Enqueue main theme style
        wp_enqueue_style(
            'wp-fasty-style',
            get_stylesheet_uri(),
            [],
            wp_get_theme()->get('Version')
        );
    }
} 