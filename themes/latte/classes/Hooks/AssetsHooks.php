<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

final class AssetsHooks extends AbstractHooks
{
    public function register(): void
    {
        $this->addAction('wp_enqueue_scripts', 'enqueue');
        $this->addAction('wp_head', 'printColorSchemeScript', 1);
    }

    /**
     * Sets `dark` on <html> before any stylesheet prints (wp_print_styles runs at priority 8) so there is no flash:
     * stored choice first, then prefers-color-scheme. Static string, no user data.
     */
    public function printColorSchemeScript(): void
    {
        wp_print_inline_script_tag(
            '(function(){var d;try{var s=localStorage.getItem("wpfasty-color-scheme");'
            . 'd=s?s==="dark":window.matchMedia("(prefers-color-scheme: dark)").matches;}catch(e){d=false;}'
            . 'document.documentElement.classList.toggle("dark",d);})();',
            ['id' => 'latte-color-scheme']
        );
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

        wp_enqueue_style(
            'latte-wp-core',
            get_template_directory_uri() . '/assets/css/wp-core.css',
            ['latte-tokens'],
            $parentVer
        );

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

        $toggleJs = get_template_directory() . '/assets/js/theme-toggle.js';
        if (is_file($toggleJs)) {
            wp_enqueue_script(
                'latte-theme-toggle',
                get_template_directory_uri() . '/assets/js/theme-toggle.js',
                [],
                $parentVer,
                ['in_footer' => true, 'strategy' => 'defer']
            );
        }

        if (!is_child_theme()) {
            return;
        }

        $tokenDeps = ['latte-tokens'];
        $baseDir = function_exists('latte_base_theme_dir') ? latte_base_theme_dir() : '';
        if ($baseDir !== '' && is_file($baseDir . '/assets/css/tokens.css')) {
            wp_enqueue_style(
                'latte-base-tokens',
                latte_base_theme_uri() . '/assets/css/tokens.css',
                ['latte-tokens'],
                $childVer
            );
            $tokenDeps[] = 'latte-base-tokens';
        }

        $childTokens = get_stylesheet_directory() . '/assets/css/tokens.css';
        if (is_file($childTokens)) {
            wp_enqueue_style(
                'latte-skin-tokens',
                get_stylesheet_directory_uri() . '/assets/css/tokens.css',
                $tokenDeps,
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
