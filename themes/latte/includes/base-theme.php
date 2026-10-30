<?php

declare(strict_types=1);

/**
 * Base theme layer ("Extends:" header).
 *
 * WordPress allows only two theme generations (child → parent). A brand child of a Latte child theme
 * (e.g. wpfasty-market on top of latte-shop) declares `Template: latte` plus `Extends: latte-shop` in style.css;
 * Latte then layers the base theme between the active child and itself: its functions.php is loaded, its views and
 * types are searched after the child's and before Latte's, its tokens.css is enqueued under the child's.
 *
 * @package Latte
 */

/** Absolute directory of the base theme of the active child, or '' (none, unknown or not a direct Latte child). */
function latte_base_theme_dir(): string
{
    static $dir = null;
    if (is_string($dir)) {
        return $dir;
    }
    $dir = '';
    if (get_stylesheet() === get_template()) {
        return $dir;
    }
    $style = get_stylesheet_directory() . '/style.css';
    $slug = is_file($style) ? (string) (get_file_data($style, ['Extends' => 'Extends'])['Extends'] ?? '') : '';
    if (preg_match('/^[a-z0-9_-]{1,64}$/', $slug) !== 1 || $slug === get_stylesheet() || $slug === get_template()) {
        return $dir;
    }
    $candidate = get_theme_root() . '/' . $slug;
    $baseStyle = $candidate . '/style.css';
    if (!is_file($baseStyle)) {
        return $dir;
    }
    // The base must itself be a direct child of the same parent (one extra generation, no chains).
    $template = (string) (get_file_data($baseStyle, ['Template' => 'Template'])['Template'] ?? '');
    if ($template === get_template()) {
        $dir = $candidate;
    }
    return $dir;
}

/** URL of the base theme directory, or ''. */
function latte_base_theme_uri(): string
{
    $dir = latte_base_theme_dir();
    return $dir === '' ? '' : get_theme_root_uri() . '/' . basename($dir);
}

/** Hooks of the base layer: theme dirs for codex lookups, slots in the view context. */
function latte_base_theme_hooks(): void
{
    /** Load the base theme's functions.php (once, before Latte boots — the same order WordPress gives a child). */
    function latte_load_base_theme(): void
    {
        $dir = latte_base_theme_dir();
        if ($dir !== '' && is_file($dir . '/functions.php')) {
            require_once $dir . '/functions.php';
        }
    }

    // Codex / Builder look up the base theme's types/ after the child's (wpfasty-types Site::current()).
    add_filter('wpfasty_theme_dirs', static function (array $dirs): array {
        $base = latte_base_theme_dir();
        if ($base === '') {
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
                if (preg_match('#^[a-z0-9/_-]{1,128}$#', $view) === 1 && !str_contains($view, '..')) {
                    $clean[(string) $name][] = ['view' => $view, 'data' => (array) ($slot['data'] ?? [])];
                }
            }
        }
        $context['slots'] = $clean;
        return $context;
    }, 99);
}
