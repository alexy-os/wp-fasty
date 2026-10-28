<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WPFastyBuilder\Source\LocalSource;

/**
 * Preview cards of the bundled ui8kit source: sources/ui8kit/previews/<block>/<variant>.webp, listed by
 * previews/manifest.json (tools/builder/previews.ts; outside the pinned, digested source tree because the shots are
 * not byte-reproducible). attach() gives every variant of the editor catalog its `preview` URL (and the card size);
 * a source without previews (an imported registry) is returned as is and the editor shows a placeholder card.
 */
final class Previews
{
    /** CSS size of a card: the files are 2× (640×360) */
    public const WIDTH = 320;
    public const HEIGHT = 180;

    /** @var array<string, array<string, mixed>|null> */
    private static array $manifests = [];

    /**
     * @param list<array<string, mixed>> $blocks forEditor() blocks
     * @return list<array<string, mixed>>
     */
    public static function attach(array $blocks, ?object $source): array
    {
        $dir = self::dirOf($source);
        $m = $dir !== null ? self::manifest($dir) : null;
        if ($m === null) {
            return $blocks;
        }
        $base = plugins_url(substr($dir, strlen(Plugin::$dir) + 1), Plugin::$file) . '/';
        foreach ($blocks as $i => $b) {
            foreach ((array) ($b['variants'] ?? []) as $j => $v) {
                $e = $m[(string) ($b['id'] ?? '') . '/' . (string) ($v['id'] ?? '')] ?? null;
                if (is_array($e) && is_string($e['file'] ?? null)) {
                    $blocks[$i]['variants'][$j]['preview'] = [
                        'src' => $base . $e['file'], 'width' => self::WIDTH, 'height' => self::HEIGHT,
                    ];
                }
            }
        }
        return $blocks;
    }

    /** the previews folder of a source bundled with the plugin (sources/<id>/previews), or null */
    public static function dirOf(?object $source): ?string
    {
        if (!$source instanceof LocalSource || Plugin::$dir === '') {
            return null;
        }
        $dir = dirname(rtrim($source->templateRoot(), '/')) . '/previews';
        $root = rtrim(Plugin::$dir, '/') . '/';
        return str_starts_with($dir, $root) && is_file($dir . '/manifest.json') ? $dir : null;
    }

    /** @return array<string, mixed>|null "<block>/<variant>" => {file, bytes, …} */
    private static function manifest(string $dir): ?array
    {
        if (!array_key_exists($dir, self::$manifests)) {
            $raw = file_get_contents($dir . '/manifest.json');
            $json = is_string($raw) ? json_decode($raw, true) : null;
            self::$manifests[$dir] = is_array($json) && is_array($json['variants'] ?? null) ? $json['variants'] : null;
        }
        return self::$manifests[$dir];
    }
}
