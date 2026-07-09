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
        $parent = wp_get_theme(get_template());
        $parentVer = $parent->get('Version') ?: '1.0.0';
        $child = wp_get_theme();
        $childVer = $child->get('Version') ?: $parentVer;

        wp_enqueue_style(
            'latte-theme',
            get_template_directory_uri() . '/assets/css/theme.min.css',
            [],
            $parentVer
        );
        wp_enqueue_style(
            'latte-tokens',
            get_template_directory_uri() . '/assets/css/tokens.css',
            ['latte-theme'],
            $parentVer
        );

        $a11y = get_template_directory() . '/assets/css/a11y.css';
        if (is_file($a11y)) {
            wp_enqueue_style(
                'latte-a11y',
                get_template_directory_uri() . '/assets/css/a11y.css',
                ['latte-tokens'],
                $parentVer
            );
        }

        $ariaJs = get_template_directory() . '/assets/js/ui8kit.js';
        if (is_file($ariaJs)) {
            wp_enqueue_script(
                'latte-ui8kit',
                get_template_directory_uri() . '/assets/js/ui8kit.js',
                [],
                $parentVer,
                true
            );
        }

        if (!is_child_theme()) {
            return;
        }

        $childTokens = get_stylesheet_directory() . '/assets/css/tokens.css';
        if (is_file($childTokens)) {
            wp_enqueue_style(
                'latte-skin-tokens',
                get_stylesheet_directory_uri() . '/assets/css/tokens.css',
                ['latte-tokens'],
                $childVer
            );
        }

        $childStyle = get_stylesheet_directory() . '/style.css';
        if (is_file($childStyle)) {
            wp_enqueue_style(
                'latte-skin-style',
                get_stylesheet_uri(),
                ['latte-tokens'],
                $childVer
            );
        }
    }
}
