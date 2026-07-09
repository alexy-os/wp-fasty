<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

use WPFasty\Theme\PrimitivesLocator;

final class ThemeHooks extends AbstractHooks
{
    public function register(): void
    {
        $this->addAction('after_setup_theme', 'setup');
        $this->addAction('init', 'menus');
        $this->addAction('widgets_init', 'sidebars');
        $this->addAction('admin_notices', 'primitivesNotice');
    }

    public function setup(): void
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('custom-logo', [
            'height' => 80,
            'width' => 240,
            'flex-height' => true,
            'flex-width' => true,
        ]);
        add_theme_support('responsive-embeds');
        add_theme_support('align-wide');
        add_theme_support('editor-styles');
        add_editor_style('assets/css/tokens.css');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        ]);

        // Comments intentionally disabled — no PII collection in this theme.
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        add_filter('comments_array', static fn (): array => [], 20);

        add_theme_support('starter-content', $this->starterContent());
    }

    public function menus(): void
    {
        register_nav_menus([
            'primary' => esc_html__('Primary', 'latte'),
            'footer' => esc_html__('Footer', 'latte'),
        ]);
    }

    public function sidebars(): void
    {
        register_sidebar([
            'name' => esc_html__('Sidebar', 'latte'),
            'id' => 'sidebar-1',
            'description' => esc_html__('Widgets in the content sidebar.', 'latte'),
            'before_widget' => '<section id="%1$s" class="widget %2$s mb-8">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="widget-title mb-3 text-lg font-semibold">',
            'after_title' => '</h2>',
        ]);

        register_sidebar([
            'name' => esc_html__('Footer', 'latte'),
            'id' => 'footer-1',
            'description' => esc_html__('Widgets in the site footer.', 'latte'),
            'before_widget' => '<section id="%1$s" class="widget %2$s mb-4">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="widget-title mb-2 text-base font-semibold">',
            'after_title' => '</h2>',
        ]);
    }

    /**
     * Fail loud in wp-admin when UI8Kit Latte primitives are missing.
     */
    public function primitivesNotice(): void
    {
        if (!current_user_can('switch_themes')) {
            return;
        }

        if (PrimitivesLocator::resolve() !== null) {
            return;
        }

        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'Latte theme: UI8Kit Latte primitives not found. From the monorepo root run bun run ui:primitives, then composer install in themes/latte.',
            'latte'
        );
        echo '</p></div>';
    }

    /** @return array<string, mixed> */
    private function starterContent(): array
    {
        return [
            'nav_menus' => [
                'primary' => [
                    'name' => __('Primary', 'latte'),
                    'items' => [
                        'link_home',
                        'page_blog',
                        'page_about',
                    ],
                ],
                'footer' => [
                    'name' => __('Footer', 'latte'),
                    'items' => [
                        'link_home',
                        'page_about',
                    ],
                ],
            ],
            'posts' => [
                'home' => [
                    'post_type' => 'page',
                    'post_title' => __('Home', 'latte'),
                    'post_content' => __(
                        'Welcome to Latte — a clean WordPress theme built on UI8Kit Latte primitives.',
                        'latte'
                    ),
                ],
                'about' => [
                    'post_type' => 'page',
                    'post_title' => __('About', 'latte'),
                    'post_content' => __(
                        'This is an example About page. Edit it in the admin to introduce your site.',
                        'latte'
                    ),
                ],
                'blog' => [
                    'post_type' => 'page',
                    'post_title' => __('Blog', 'latte'),
                ],
                'post_one' => [
                    'post_type' => 'post',
                    'post_title' => __('Hello Latte', 'latte'),
                    'post_content' => __(
                        'This is your first sample post. Replace it with your own content.',
                        'latte'
                    ),
                    'post_category' => ['news'],
                ],
                'post_two' => [
                    'post_type' => 'post',
                    'post_title' => __('Design with primitives', 'latte'),
                    'post_content' => __(
                        'Compose pages from UI8Kit blocks — Button, Card, Stack, and more.',
                        'latte'
                    ),
                    'post_category' => ['news'],
                ],
                'post_three' => [
                    'post_type' => 'post',
                    'post_title' => __('Server-rendered and fast', 'latte'),
                    'post_content' => __(
                        'Latte templates stay free of WordPress queries; context is built in PHP.',
                        'latte'
                    ),
                    'post_category' => ['updates'],
                ],
            ],
            'options' => [
                'show_on_front' => 'page',
                'page_on_front' => '{{home}}',
                'page_for_posts' => '{{blog}}',
                'blogname' => __('Latte', 'latte'),
                'blogdescription' => __('Clean Latte theme for WordPress', 'latte'),
            ],
        ];
    }
}
