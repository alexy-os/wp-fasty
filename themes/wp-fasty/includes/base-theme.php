<?php

declare(strict_types=1);

/**
 * Base theme layer ("Extends:" header).
 *
 * WordPress allows only two theme generations (child → parent). A brand child of a child theme
 * (e.g. wpfasty-market on top of latte-shop) declares `Template: wp-fasty` plus `Extends: latte-shop` in style.css;
 * the parent then layers the base theme between the active child and itself: its functions.php is loaded, its views and
 * types are searched after the child's and before the parent's, its tokens.css is enqueued under the child's.
 */

/** Absolute directory of the base theme of the active child, or '' (none, unknown or not a direct child). */
function wpfasty_base_theme_dir(): string
{
    static $cachedTheme = '';
    static $dir = '';
    // Keep the one-entry cache, but follow switch_theme() in the same request (Customizer/CLI).
    $theme = get_template().'|'.get_stylesheet();
    if ($cachedTheme === $theme) {
        return $dir;
    }
    $cachedTheme = $theme;
    $dir = '';
    if (get_stylesheet() === get_template()) {
        return $dir;
    }
    $style = get_stylesheet_directory().'/style.css';
    $slug = is_file($style) ? (string) (get_file_data($style, ['Extends' => 'Extends'])['Extends'] ?? '') : '';
    if (1 !== preg_match('/^[a-z0-9_-]{1,64}$/', $slug) || get_stylesheet() === $slug || get_template() === $slug) {
        return $dir;
    }
    $candidate = get_theme_root().'/'.$slug;
    $baseStyle = $candidate.'/style.css';
    if (!is_file($baseStyle)) {
        return $dir;
    }
    // The base must itself be a direct child of the same parent (one extra generation, no chains).
    $template = (string) (get_file_data($baseStyle, ['Template' => 'Template'])['Template'] ?? '');
    if (get_template() === $template) {
        $dir = $candidate;
    }

    return $dir;
}

/** URL of the base theme directory, or ''. */
function wpfasty_base_theme_uri(): string
{
    $dir = wpfasty_base_theme_dir();

    return '' === $dir ? '' : get_theme_root_uri().'/'.basename($dir);
}

/** Hooks of the base layer: theme dirs for codex lookups, slots in the view context. */
function wpfasty_base_theme_hooks(): void
{
    /** Load the base theme's functions.php (once, before the parent boots — the same order WordPress gives a child). */
    function wpfasty_load_base_theme(): void
    {
        $dir = wpfasty_base_theme_dir();
        if ('' !== $dir && is_file($dir.'/functions.php')) {
            require_once $dir.'/functions.php';
        }
    }

    // Post type files of the base theme for registries built outside the parent's services (ModulY types module).
    add_filter('wpfasty_type_dirs', static function (array $dirs): array {
        $base = wpfasty_base_theme_dir();
        if ('' === $base || in_array($base.'/types', $dirs, true)) {
            return $dirs;
        }
        array_splice($dirs, min(1, count($dirs)), 0, [$base.'/types']);

        return $dirs;
    });

    // Codex / Builder look up the base theme's types/ after the child's (wpfasty-types Site::current()).
    add_filter('wpfasty_theme_dirs', static function (array $dirs): array {
        $base = wpfasty_base_theme_dir();
        if ('' === $base) {
            return $dirs;
        }
        array_splice($dirs, 1, 0, [$base]);

        return $dirs;
    });

    /*
     * Extension points ("slots"): modules and child themes add views to named places of the templates without
     * overriding them. `wpfasty_slots` (array $slots, array $context) returns name => list of
     * ['view' => 'moduly/<id>/<view>' (no extension; the engine adds .latte / .html.twig), 'data' => array].
     * Templates render a slot with {include 'blocks/slot.latte', name: '<name>'} / the Twig twin.
     * Module views are found through `wpfasty_view_roots`.
     */
    add_filter('wpfasty_context', static function (array $context): array {
        $slots = (array) apply_filters('wpfasty_slots', [], $context);
        $clean = [];
        foreach ($slots as $name => $list) {
            foreach ((array) $list as $slot) {
                $view = is_array($slot) ? (string) ($slot['view'] ?? '') : '';
                if (1 === preg_match('#^[a-z0-9/_-]{1,128}$#', $view) && !str_contains($view, '..')) {
                    $clean[(string) $name][] = ['view' => $view, 'data' => (array) ($slot['data'] ?? [])];
                }
            }
        }
        $context['slots'] = $clean;

        return $context;
    }, 99);
}
