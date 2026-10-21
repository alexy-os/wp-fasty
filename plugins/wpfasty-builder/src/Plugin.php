<?php

declare(strict_types=1);

namespace WPFastyBuilder;

final class Plugin
{
    public const VERSION = '0.1.0';
    public static string $file = '';
    public static string $dir = '';

    /**
     * The site engine: the runtime of the active WP FastY theme renderer (`latte` or `twig`), chosen at site creation.
     * Filter `wpfasty_builder_engine` overrides it (tests, CLI). Null without a WP FastY renderer.
     */
    public static function engine(): ?string
    {
        $engine = function_exists('apply_filters') ? apply_filters('wpfasty_builder_engine', null) : null;
        if ($engine === null && class_exists('\WPFasty\Core\Application')) {
            try {
                $renderer = \WPFasty\Core\Application::getInstance()->get('template.engine');
                $engine = is_object($renderer) && method_exists($renderer, 'engine') ? $renderer->engine() : null;
            } catch (\Throwable) {
                $engine = null;
            }
        }
        return in_array($engine, ['latte', 'twig'], true) ? $engine : null;
    }

    /**
     * Runtimes whose bundled ui8kit source this plugin build ships (one in a release build, both in the repo).
     *
     * @return list<string>
     */
    public static function runtimes(): array
    {
        return array_values(array_filter(['latte', 'twig'], [self::class, 'ships']));
    }

    public static function ships(string $runtime): bool
    {
        return is_file(self::$dir . '/sources/ui8kit/' . $runtime . '/catalog.json');
    }

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
        add_action('wpfasty_builder_missing_block', [Sources::class, 'recordMissing'], 10, 2);
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
