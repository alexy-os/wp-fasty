<?php
/**
 * Shared view root chain helpers for all renderers.
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Template;

final class RootChain
{
    /**
     * @param array<int, mixed> $roots
     * @return list<string>
     */
    public static function normalize(array $roots): array
    {
        $roots = array_values(array_unique(array_filter(
            $roots,
            static fn (mixed $dir): bool => is_string($dir) && $dir !== '' && is_dir($dir)
        )));
        if ($roots === []) {
            throw new \RuntimeException('Renderer requires at least one views root');
        }
        return $roots;
    }

    /** @param list<string> $roots */
    public static function find(array $roots, string $file): ?string
    {
        foreach ($roots as $root) {
            $path = rtrim($root, '/\\') . '/' . ltrim($file, '/');
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    public static function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create cache directory: ' . htmlspecialchars($dir, ENT_QUOTES));
        }
    }
}
