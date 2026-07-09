<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

class ThemeHooks extends AbstractHooks
{
    public function register(): void
    {
        $this->addAction('after_setup_theme', 'setupTheme');
        // WP 6.7+: avoid __() before init (JIT textdomain notice)
        $this->addAction('init', 'registerMenus');
        $this->addAction('widgets_init', 'registerSidebars');
    }

    public function setupTheme(): void
    {
        add_theme_support('automatic-feed-links');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('customize-selective-refresh-widgets');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ]);
    }

    public function registerMenus(): void
    {
        register_nav_menus([
            'primary' => esc_html__('Primary Menu', 'wp-fasty-ym'),
        ]);
    }

    public function registerSidebars(): void
    {
        register_sidebar([
            'name' => esc_html__('Sidebar', 'wp-fasty-ym'),
            'id' => 'sidebar-1',
            'description' => esc_html__('Add widgets here.', 'wp-fasty-ym'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="widget-title">',
            'after_title' => '</h2>',
        ]);
    }
}
