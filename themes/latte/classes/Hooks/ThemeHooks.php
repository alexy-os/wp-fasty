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

        // Comments are intentionally disabled for this presentation theme.
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);
        add_filter('comments_array', static fn (): array => [], 20);
    }

    public function menus(): void
    {
        register_nav_menus([
            'primary' => esc_html__('Primary', 'latte'),
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
}
