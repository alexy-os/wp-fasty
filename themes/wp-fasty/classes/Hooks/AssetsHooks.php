<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

final class AssetsHooks extends AbstractHooks
{
    public function register(): void
    {
        $this->addAction('wp_enqueue_scripts', 'enqueue');
        $this->addAction('wp_head', 'printColorSchemeScript', 1);
        add_filter('language_attributes', [$this, 'languageAttributes']);
    }

    /**
     * Sets `dark` on <html> before any stylesheet prints (wp_print_styles runs at priority 8) so there is no flash:
     * stored choice first, then prefers-color-scheme. Static string, no user data.
     */
    public function printColorSchemeScript(): void
    {
        if (null !== $this->colorScheme()) {
            return;
        }
        wp_print_inline_script_tag(
            '(function(){var d;try{var s=localStorage.getItem("wpfasty-color-scheme");'
            .'d=s?s==="dark":window.matchMedia("(prefers-color-scheme: dark)").matches;}catch(e){d=false;}'
            .'document.documentElement.classList.toggle("dark",d);})();',
            ['id' => 'wpfasty-color-scheme']
        );
    }

    /** A forced server scheme is request-local (e.g. an authenticated, script-free editor preview). */
    public function languageAttributes(string $attributes): string
    {
        $scheme = $this->colorScheme();
        if (null === $scheme) {
            return $attributes;
        }
        $tag = new \WP_HTML_Tag_Processor('<html '.$attributes.'>');
        $tag->next_tag();
        $tag->remove_class('dark');
        if ('dark' === $scheme) {
            $tag->add_class('dark');
        }

        return substr($tag->get_updated_html(), 6, -1);
    }

    public function enqueue(): void
    {
        $parent = wp_get_theme(get_template());
        $parentVer = $parent->get('Version') ?: '1.0.0';
        $child = wp_get_theme();
        $childVer = $child->get('Version') ?: $parentVer;

        wp_enqueue_style(
            'wpfasty-theme',
            get_template_directory_uri().'/assets/css/theme.min.css',
            [],
            $parentVer
        );
        $parentFonts = $this->fonts(
            'wpfasty-fonts',
            get_template_directory(),
            get_template_directory_uri(),
            ['wpfasty-theme'],
            $parentVer
        );
        wp_enqueue_style(
            'wpfasty-tokens',
            get_template_directory_uri().'/assets/css/tokens.css',
            $parentFonts,
            $parentVer
        );

        $a11y = get_template_directory().'/assets/css/a11y.css';
        if (is_file($a11y)) {
            wp_enqueue_style(
                'wpfasty-a11y',
                get_template_directory_uri().'/assets/css/a11y.css',
                ['wpfasty-tokens'],
                $parentVer
            );
        }

        wp_enqueue_style(
            'wpfasty-wp-core',
            get_template_directory_uri().'/assets/css/wp-core.css',
            ['wpfasty-tokens'],
            $parentVer
        );

        $ariaJs = get_template_directory().'/assets/js/ui8kit.js';
        if (is_file($ariaJs)) {
            wp_enqueue_script(
                'wpfasty-ui8kit',
                get_template_directory_uri().'/assets/js/ui8kit.js',
                [],
                $parentVer,
                true
            );
        }

        $toggleJs = get_template_directory().'/assets/js/theme-toggle.js';
        if (is_file($toggleJs)) {
            wp_enqueue_script(
                'wpfasty-theme-toggle',
                get_template_directory_uri().'/assets/js/theme-toggle.js',
                [],
                $parentVer,
                ['in_footer' => true, 'strategy' => 'defer']
            );
        }

        if (!is_child_theme()) {
            return;
        }

        $tokenDeps = ['wpfasty-tokens'];
        $baseDir = function_exists('wpfasty_base_theme_dir') ? wpfasty_base_theme_dir() : '';
        $baseTokens = '' !== $baseDir && function_exists('wpfasty_base_theme_uri');
        if ($baseTokens) {
            $baseVer = wp_get_theme(basename($baseDir))->get('Version') ?: $childVer;
            $tokenDeps = $this->fonts(
                'wpfasty-base-fonts',
                $baseDir,
                wpfasty_base_theme_uri(),
                $tokenDeps,
                $baseVer
            );
            if (is_file($baseDir.'/assets/css/tokens.css')) {
                wp_enqueue_style(
                    'wpfasty-base-tokens',
                    wpfasty_base_theme_uri().'/assets/css/tokens.css',
                    $tokenDeps,
                    $baseVer
                );
                $tokenDeps = ['wpfasty-base-tokens'];
            }
        }

        $tokenDeps = $this->fonts(
            'latte-skin-fonts',
            get_stylesheet_directory(),
            get_stylesheet_directory_uri(),
            $tokenDeps,
            $childVer
        );
        $childTokens = get_stylesheet_directory().'/assets/css/tokens.css';
        if (is_file($childTokens)) {
            wp_enqueue_style(
                'latte-skin-tokens',
                get_stylesheet_directory_uri().'/assets/css/tokens.css',
                $tokenDeps,
                $childVer
            );
            $tokenDeps = ['latte-skin-tokens'];
        }

        $childStyle = get_stylesheet_directory().'/style.css';
        if (is_file($childStyle)) {
            wp_enqueue_style(
                'latte-skin-style',
                get_stylesheet_uri(),
                $tokenDeps,
                $childVer
            );
        }
    }

    private function colorScheme(): ?string
    {
        $scheme = apply_filters('wpfasty_color_scheme', null);

        return in_array($scheme, ['light', 'dark'], true) ? $scheme : null;
    }

    /**
     * Optional self-hosted @font-face definitions. The theme, not Builder/admin, owns family, weight and files.
     *
     * @param list<string> $dependencies
     *
     * @return list<string>
     */
    private function fonts(string $handle, string $directory, string $uri, array $dependencies, string $version): array
    {
        $file = $directory.'/assets/css/fonts.css';
        if (!is_file($file)) {
            return $dependencies;
        }
        wp_enqueue_style($handle, $uri.'/assets/css/fonts.css', $dependencies, $version);

        return [$handle];
    }
}
