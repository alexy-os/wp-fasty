<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

final class ThemeHooks extends AbstractHooks
{
    public function register(): void
    {
        $this->addAction('after_setup_theme', 'setup');
        $this->addAction('init', 'menus');
    }

    public function setup(): void
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ]);
    }

    public function menus(): void
    {
        register_nav_menus([
            'primary' => esc_html__('Primary', 'latte'),
        ]);
    }
}
