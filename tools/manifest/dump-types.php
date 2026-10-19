<?php
/**
 * Prints the declarative post-type modules (types/*.php) of one theme as JSON, without WordPress:
 * __() returns the msgid, callables (columns, context) are reduced to `true`.
 * Used by tools/manifest/validate.ts to prove a manifest matches the real child theme.
 *   php tools/manifest/dump-types.php themes/latte-shop
 *
 * @package Latte
 */

declare(strict_types=1);

const ABSPATH = __DIR__ . '/';

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

$dir = rtrim($argv[1] ?? '', '/') . '/types';
$out = [];
foreach (glob($dir . '/*.php') ?: [] as $file) {
    $def = require $file;
    if (is_array($def)) {
        array_walk_recursive($def, static function (mixed &$v): void {
            if ($v instanceof Closure) {
                $v = true;
            }
        });
        $out[] = $def;
    }
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
