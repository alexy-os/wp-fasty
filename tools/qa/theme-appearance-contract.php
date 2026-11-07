<?php

/** NON-LIVE, own disposable WP only. Run with wp --skip-themes eval 'require "tools/qa/theme-appearance-contract.php";'. */
declare(strict_types=1);

use WPFasty\Core\Container;
use WPFasty\Hooks\AssetsHooks;
use WPFastyBuilder\Front;

if ('cli' !== PHP_SAPI) {
    throw new RuntimeException('This disposable fixture is CLI-only.');
}
$root = get_theme_root();
$originalTheme = get_stylesheet();
$originalThemeSwitched = get_option('theme_switched', false);
$originalUser = get_current_user_id();
$originalQuery = $GLOBALS['wp_query'];
$originalPost = $GLOBALS['post'] ?? null;
$originalStyles = $GLOBALS['wp_styles'] ?? null;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- CLI fixture snapshot only, restored in finally
$originalGet = $_GET;
$hadServerName = isset($_SERVER['SERVER_NAME']);
$_SERVER['SERVER_NAME'] ??= 'localhost';
$names = ['wpfb-appearance-base', 'wpfb-appearance-child'];
$files = [];
$cssPosts = [];
$page = 0;
$assertions = 0;
$check = static function (bool $ok, string $message) use (&$assertions): void {
    ++$assertions;
    if (!$ok) {
        throw new RuntimeException(sprintf('Appearance contract: %s', $message));
    }
};
foreach ($names as $name) {
    if (is_dir($root.'/'.$name)) {
        throw new RuntimeException(sprintf('Refuse to overwrite existing fixture theme %s', $name));
    }
}
$write = static function (string $path, string $text) use (&$files): void {
    wp_mkdir_p(dirname($path));
    if (false === file_put_contents($path, $text)) {
        throw new RuntimeException('Cannot write disposable fixture');
    }
    $files[] = $path;
};
ob_start();
try {
    foreach ($names as $name) {
        $dir = $root.'/'.$name;
        $extends = str_ends_with($name, 'child') ? "Extends: wpfb-appearance-base\n" : '';
        $write($dir.'/style.css', "/*\nTheme Name: NON-LIVE $name\nTemplate: wp-fasty\nVersion: 1.0.0\n$extends*/\n");
        $write(
            $dir.'/assets/css/tokens.css',
            ':root{--font-body:"WPFB Appearance Fixture",serif;'
            .'--font-display:"WPFB Appearance Fixture",serif;--radius:.5rem}.dark{--radius:.75rem}'
        );
        $fontCss = '';
        foreach ([400, 700] as $weight) {
            $fontCss .= '@font-face{font-family:"WPFB Appearance Fixture";font-weight:'.$weight
                .';font-style:normal;font-display:swap;src:url("../fonts/probe-'.$weight.'.ttf") format("truetype")}';
            $fontBytes = (string) file_get_contents(__DIR__.'/../../tests/fixtures/appearance/probe-'.$weight.'.ttf');
            $check(str_starts_with($fontBytes, "\x00\x01\x00\x00"), 'valid original TrueType file for weight '.$weight);
            $write($dir.'/assets/fonts/probe-'.$weight.'.ttf', $fontBytes);
        }
        $write($dir.'/assets/css/fonts.css', $fontCss);
    }
    wp_clean_themes_cache();
    switch_theme($names[1]);
    require_once get_template_directory().'/functions.php';
    $assets = new AssetsHooks(new Container());
    $GLOBALS['wp_styles'] = new WP_Styles();
    // Execute the real theme action, not a duplicate Builder stylesheet or mocked enqueue implementation.
    do_action('wp_enqueue_scripts');
    $styles = wp_styles();
    $check(isset($styles->registered['wpfasty-base-fonts']), 'optional base font files are enqueued');
    $check(isset($styles->registered['latte-skin-fonts']), 'active child font files are enqueued');
    $check(
        in_array('wpfasty-base-tokens', $styles->registered['latte-skin-fonts']->deps, true),
        'base tokens precede child font faces'
    );
    $check(
        in_array('latte-skin-fonts', $styles->registered['latte-skin-tokens']->deps, true),
        'child fonts precede child semantic tokens'
    );
    $check(
        in_array('latte-skin-tokens', $styles->registered['latte-skin-style']->deps, true),
        'child style follows its effective tokens'
    );
    $check(wpfasty_base_theme_dir() === $root.'/'.$names[0], 'base resolution follows current active theme');
    foreach ($names as $name) {
        $post = wp_update_custom_css_post(':root{--radius:1rem}.dark{--radius:2rem}', ['stylesheet' => $name]);
        $check(!is_wp_error($post), 'Additional CSS persists outside updateable theme files');
        $cssPosts[] = $post->ID;
    }
    $page = wp_insert_post([
        'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'NON-LIVE appearance contract',
    ]);
    $check(is_int($page) && $page > 0, 'disposable page created');
    $GLOBALS['wp_query'] = new WP_Query(['page_id' => $page]);
    $GLOBALS['wp_query']->the_post();
    $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    wp_set_current_user((int) ($admins[0] ?? 0));
    $check(current_user_can('edit_page', $page), 'fixture editor can edit only its authorized preview');
    $saved = WPFastyBuilder\Store::save($page, ['source' => 'ui8kit', 'blocks' => []], true);
    $check(!is_wp_error($saved), 'manual Builder layout saved through authoritative PHP validation');
    $theme = WPFasty\Core\Application::getInstance()->get('theme');
    $render = static function () use ($theme): string {
        $view = $theme->resolveView();

        return $theme->renderPage($view, $theme->context($view));
    };
    $_GET = [];
    $liveHtml = $render();
    $check(str_contains($liveHtml, 'wpfb-page'), 'real published Builder page uses active theme shell');
    $check(str_contains($liveHtml, 'latte-skin-fonts-css'), 'real live page includes active child font stylesheet');
    $draftBeforePreview = get_post_meta($page, WPFastyBuilder\Store::DRAFT, true);
    $front = $styles->registered;
    foreach (['light', 'dark'] as $scheme) {
        $_GET = [
            'wpfb_preview' => '1', '_wpnonce' => wp_create_nonce('wpfb_preview_'.$page), 'wpfb_scheme' => $scheme,
        ];
        $check(Front::isPreview($page), 'nonce-protected draft preview');
        $check(apply_filters('wpfasty_color_scheme', null) === $scheme, 'effective scheme is server supplied');
        $html = $render();
        $document = new WP_HTML_Tag_Processor($html);
        $document->next_tag('HTML');
        $check(
            $document->has_class('dark') === ('dark' === $scheme),
            'actual server-rendered theme root uses preview scheme'
        );
        $check(str_contains($html, 'wpfb-page'), 'real draft Builder view uses active theme shell');
        $check(
            !str_contains($html, 'id="wpfasty-color-scheme"'),
            'sandbox document does not rely on its blocked color script'
        );
        $attrs = apply_filters('language_attributes', 'lang="en-US" class="preserve-me dark"');
        $tag = new WP_HTML_Tag_Processor('<html '.$attrs.'>');
        $tag->next_tag();
        $check($tag->has_class('dark') === ('dark' === $scheme), 'server root matches requested scheme');
        $check($tag->has_class('preserve-me'), 'other language/root attributes preserved');
        ob_start();
        $assets->printColorSchemeScript();
        $check('' === ob_get_clean(), 'preview needs no color-mode script in sandbox');
        $GLOBALS['wp_styles'] = new WP_Styles();
        do_action('wp_enqueue_scripts');
        $handles = [
            'wpfasty-theme', 'wpfasty-tokens', 'wpfasty-base-fonts', 'wpfasty-base-tokens',
            'latte-skin-fonts', 'latte-skin-tokens', 'latte-skin-style',
        ];
        foreach ($handles as $handle) {
            $check(
                wp_styles()->registered[$handle]->src === $front[$handle]->src,
                'frontend/preview share theme asset '.$handle
            );
        }
        ob_start();
        wp_print_styles();
        wp_custom_css_cb();
        $head = (string) ob_get_clean();
        $check(
            strpos($head, 'latte-skin-tokens-css') < strpos($head, 'wp-custom-css'),
            'persisted overrides follow child CSS'
        );
        $check(str_contains($head, '--radius:2rem'), 'dark overrides survive outside theme files');
    }
    $check(
        $draftBeforePreview === get_post_meta($page, WPFastyBuilder\Store::DRAFT, true),
        'preview scheme leaves persisted layout data unchanged'
    );
    $_GET['wpfb_scheme'] = ['dark'];
    $check(
        'light' === apply_filters('wpfasty_color_scheme', null),
        'array and unknown mode cannot inject root attributes'
    );
    $_GET['wpfb_scheme'] = '"><script>alert(1)</script>';
    $check('light' === apply_filters('wpfasty_color_scheme', null), 'unknown mode is allowlisted rather than echoed');
    $_GET['wpfb_scheme'] = 'dark';
    wp_set_current_user(0);
    $check(
        null === apply_filters('wpfasty_color_scheme', null),
        'anonymous request cannot select authorized preview mode'
    );
    wp_set_current_user((int) $admins[0]);
    $_GET['_wpnonce'] = 'invalid';
    $check(null === apply_filters('wpfasty_color_scheme', null), 'invalid nonce cannot select preview mode');
    $_GET = ['wpfb_scheme' => 'dark'];
    $check(null === apply_filters('wpfasty_color_scheme', null), 'live page ignores preview-only query');
    ob_start();
    $assets->printColorSchemeScript();
    $check(str_contains((string) ob_get_clean(), 'wpfasty-color-scheme'), 'live browser preference behavior preserved');
    switch_theme($names[0]);
    $check('' === wpfasty_base_theme_dir(), 'switching away drops old optional base');
    $check(!str_contains(wp_get_custom_css(), 'child-only'), 'no cross-theme override import');
    $childCss = wp_update_custom_css_post(':root{--radius:3rem}/*child-only*/', ['stylesheet' => $names[1]]);
    $check(!is_wp_error($childCss), 'child override updated separately');
    $check(!str_contains(wp_get_custom_css(), 'child-only'), 'inactive child overrides do not affect another theme');
    switch_theme($names[1]);
    $check(str_contains(wp_get_custom_css(), 'child-only'), 'returning to exact child restores its stored overrides');
    $check(wpfasty_base_theme_dir() === $root.'/'.$names[0], 'returning to child restores correct base');
    switch_theme('wp-fasty');
    $GLOBALS['wp_styles'] = new WP_Styles();
    do_action('wp_enqueue_scripts');
    $check(
        !isset(wp_styles()->registered['latte-skin-fonts'])
            && !isset(wp_styles()->registered['wpfasty-base-fonts']),
        'manual parent without a child needs no child/base assets'
    );
    foreach (($GLOBALS['wp_filter']['admin_enqueue_scripts']->callbacks ?? []) as $callbacks) {
        foreach ($callbacks as $callback) {
            $function = $callback['function'];
            $check(
                !is_array($function) || !($function[0] instanceof AssetsHooks),
                'theme appearance hooks never register admin assets'
            );
        }
    }
} finally {
    $_GET = $originalGet;
    if (!$hadServerName) {
        unset($_SERVER['SERVER_NAME']);
    }
    $GLOBALS['wp_query'] = $originalQuery;
    $GLOBALS['post'] = $originalPost;
    $GLOBALS['wp_styles'] = $originalStyles;
    wp_set_current_user($originalUser);
    switch_theme($originalTheme);
    foreach ($cssPosts as $id) {
        wp_delete_post($id, true);
    }
    if ($page > 0) {
        wp_delete_post($page, true);
        foreach (['page', 'preview'] as $kind) {
            foreach (['.latte', '.html.twig'] as $extension) {
                $compiled = WPFastyBuilder\Store::viewsDir().'/builder/'.$kind.'-'.$page.$extension;
                if (is_file($compiled)) {
                    unlink($compiled);
                }
            }
        }
    }
    foreach (array_reverse($files) as $file) {
        unlink($file);
    }
    foreach ($names as $name) {
        foreach (['/assets/fonts', '/assets/css', '/assets', ''] as $tail) {
            if (is_dir($root.'/'.$name.$tail)) {
                rmdir($root.'/'.$name.$tail);
            }
        }
        delete_option('theme_mods_'.$name);
    }
    wp_clean_themes_cache();
    if (false === $originalThemeSwitched) {
        delete_option('theme_switched');
    } else {
        update_option('theme_switched', $originalThemeSwitched);
    }
    ob_end_clean();
}
echo 'PASS theme appearance: '.$assertions." real WordPress assertions (NON-LIVE; not visual browser verification)\n";
