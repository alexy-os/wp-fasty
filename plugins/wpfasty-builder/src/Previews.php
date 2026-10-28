<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WPFastyBuilder\Source\LocalSource;

/**
 * Preview cards of the bundled ui8kit source: sources/ui8kit/previews/<block>/<variant>.webp (light) and
 * <block>/<variant>.dark.webp (dark mode), listed by previews/manifest.json (tools/builder/previews.ts; outside the
 * pinned, digested source tree because the shots are not byte-reproducible). attach() gives every variant of the
 * editor catalog its `preview`: the light URL (`src`), the dark one (`dark`, when shot; the editor falls back to `src`)
 * and the card size; a source without previews (an imported registry) is returned as is and the editor shows a
 * placeholder card.
 *
 * Template pages (collections of type template) have a tall page shot each:
 * sources/ui8kit/page-previews/<collection>/<page>.webp and .dark.webp, listed by page-previews/manifest.json
 * (tools/builder/page-previews.ts). attachPages() gives every page of the editor's collection its `preview`
 * {src, dark?, width, height} (file pixels: 640 wide, half the 1280 px desktop shot), shown in the
 * side panel the editor opens on hover / focus of a template page button.
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
                    $preview = ['src' => $base . $e['file']];
                    if (is_string($e['dark']['file'] ?? null) && $e['dark']['file'] !== '') {
                        $preview['dark'] = $base . $e['dark']['file'];
                    }
                    $blocks[$i]['variants'][$j]['preview'] = $preview + [
                        'width' => self::WIDTH, 'height' => self::HEIGHT,
                    ];
                }
            }
        }
        return $blocks;
    }

    /**
     * @param array<string, mixed>|null $collection forEditor() collection
     * @return array<string, mixed>|null
     */
    public static function attachPages(?array $collection, ?object $source): ?array
    {
        $dir = self::dirOf($source, 'page-previews');
        $m = $dir !== null && $collection !== null ? self::manifest($dir, 'pages') : null;
        if ($m === null || !is_array($collection['pages'] ?? null)) {
            return $collection;
        }
        $base = plugins_url(substr($dir, strlen(Plugin::$dir) + 1), Plugin::$file) . '/';
        foreach ($collection['pages'] as $i => $p) {
            $e = $m[(string) ($collection['id'] ?? '') . '/' . (string) ($p['id'] ?? '')] ?? null;
            if (!is_array($e) || !is_string($e['file'] ?? null) || $e['file'] === '') {
                continue;
            }
            $preview = ['src' => $base . $e['file']];
            if (is_string($e['dark']['file'] ?? null) && $e['dark']['file'] !== '') {
                $preview['dark'] = $base . $e['dark']['file'];
            }
            $collection['pages'][$i]['preview'] = $preview + [
                'width' => (int) ($e['width'] ?? 0), 'height' => (int) ($e['height'] ?? 0),
            ];
        }
        return $collection;
    }

    /** a folder of a source bundled with the plugin (sources/<id>/previews or …/page-previews) with a manifest, or null */
    public static function dirOf(?object $source, string $name = 'previews'): ?string
    {
        if (!$source instanceof LocalSource || Plugin::$dir === '') {
            return null;
        }
        $dir = dirname(rtrim($source->templateRoot(), '/')) . '/' . $name;
        $root = rtrim(Plugin::$dir, '/') . '/';
        return str_starts_with($dir, $root) && is_file($dir . '/manifest.json') ? $dir : null;
    }

    /** @return array<string, mixed>|null "<block>/<variant>" (or "<collection>/<page>") => {file, bytes, …, dark: {…}} */
    private static function manifest(string $dir, string $key = 'variants'): ?array
    {
        if (!array_key_exists($dir, self::$manifests)) {
            $raw = file_get_contents($dir . '/manifest.json');
            $json = is_string($raw) ? json_decode($raw, true) : null;
            self::$manifests[$dir] = is_array($json) && is_array($json[$key] ?? null) ? $json[$key] : null;
        }
        return self::$manifests[$dir];
    }
}
