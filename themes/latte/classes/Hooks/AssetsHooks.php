<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

final class AssetsHooks extends AbstractHooks
{
    public function register(): void
    {
        $this->addAction('wp_enqueue_scripts', 'enqueue');
    }

    public function enqueue(): void
    {
        $ver = wp_get_theme()->get('Version') ?: '1.0.0';
        wp_enqueue_style(
            'latte-theme',
            get_template_directory_uri() . '/assets/css/theme.min.css',
            [],
            $ver
        );
        wp_enqueue_style(
            'latte-tokens',
            get_template_directory_uri() . '/assets/css/tokens.css',
            ['latte-theme'],
            $ver
        );
    }
}
