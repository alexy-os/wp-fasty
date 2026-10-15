<?php

declare(strict_types=1);

namespace WPFasty\Template;

/**
 * Lucide icon renderer (SVGs copied by tools/icons.ts into assets/icons).
 * Child themes may add icons via the `wpfasty_icon_dirs` filter.
 */
final class Icons
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function svg(string $name, string $class = 'size-4', string $label = ''): string
    {
        $name = strtolower((string) preg_replace('/[^a-z0-9-]/i', '', $name));
        if ($name === '') {
            return '';
        }

        if (!array_key_exists($name, self::$cache)) {
            self::$cache[$name] = '';
            foreach (self::dirs() as $dir) {
                $file = rtrim($dir, '/') . '/' . $name . '.svg';
                if (is_file($file)) {
                    self::$cache[$name] = trim((string) file_get_contents($file));
                    break;
                }
            }
        }

        $svg = self::$cache[$name];
        if ($svg === '') {
            return '';
        }

        $a11y = $label !== ''
            ? ' role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES) . '"'
            : ' aria-hidden="true" focusable="false"';
        $attrs = ' class="' . htmlspecialchars(trim('lucide lucide-' . $name . ' ' . $class), ENT_QUOTES) . '"' . $a11y;

        return (string) preg_replace('/^<svg\b/', '<svg' . $attrs, $svg, 1);
    }

    /** @return list<string> */
    private static function dirs(): array
    {
        $dirs = [dirname(__DIR__, 2) . '/assets/icons'];
        if (function_exists('apply_filters')) {
            /** @var list<string> $dirs */
            $dirs = apply_filters('wpfasty_icon_dirs', $dirs);
        }
        return $dirs;
    }
}
