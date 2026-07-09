<?php

declare(strict_types=1);

namespace WPFasty\Theme;

/**
 * Resolves UI8Kit Latte primitives directory for monorepo + vendored installs.
 */
final class PrimitivesLocator
{
    public static function resolve(?string $themeDir = null): ?string
    {
        $themeDir ??= get_template_directory();

        foreach (self::candidates($themeDir) as $dir) {
            if (is_dir($dir)) {
                return $dir;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function candidates(string $themeDir): array
    {
        return [
            dirname($themeDir, 2) . '/.workspaces/ui8kit-latte/ui',
            $themeDir . '/lib/ui8kit-latte/ui',
            $themeDir . '/vendor/ui8kit/latte-primitives/ui',
            $themeDir . '/vendor/ui8kit/ui',
        ];
    }
}
