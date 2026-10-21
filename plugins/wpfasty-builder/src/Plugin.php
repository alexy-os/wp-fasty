<?php

declare(strict_types=1);

namespace WPFastyBuilder;

final class Plugin
{
    public const VERSION = '0.1.0';
    public static string $file = '';
    public static string $dir = '';

    public static function boot(string $file): void
    {
        self::$file = $file;
        self::$dir = dirname($file);
        Front::register();
        Rest::register();
        if (is_admin()) {
            Admin::register();
        }
        add_action('init', static function (): void {
            load_plugin_textdomain('wpfasty-builder', false, dirname(plugin_basename(Plugin::$file)) . '/languages');
        });
        add_action('before_delete_post', static function (int $id): void {
            foreach ([true, false] as $preview) {
                foreach (Compiler::ENGINES as $ext) {
                    $file = Store::viewsDir() . '/' . Store::view($id, $preview) . $ext;
                    if (is_file($file)) {
                        wp_delete_file($file);
                    }
                }
            }
        });
    }
}
