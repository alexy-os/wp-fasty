<?php

declare(strict_types=1);

namespace WPFasty\Theme;

/**
 * Resolves UI8Kit Latte primitives: UI8Kit 3 (`ui8kit/ui`, CLI install) first, then the legacy xgen line.
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

    /**
     * Latte root chain of the primitives: our kit compositions (`kit/ui`, middle layer over Box/Text with
     * presets) before the UI8Kit 3 primitives installed by `npx ui8kit@3 add --all` (`ui8kit/ui`, PSR-4
     * `UI8Kit\\Latte\\` → `ui8kit/runtime/`). Without a UI8Kit 3 install: the legacy package directory.
     *
     * @return list<string>
     */
    public static function latteRoots(?string $themeDir = null): array
    {
        $themeDir ??= get_template_directory();
        if (is_dir($themeDir . '/ui8kit/ui') && is_dir($themeDir . '/kit/ui')) {
            return [$themeDir . '/kit/ui', $themeDir . '/ui8kit/ui'];
        }
        $legacy = self::resolve($themeDir);

        return $legacy === null ? [] : [$legacy];
    }

    /**
     * Twig root chain: our kit compositions (`twig/kit/ui`) before the UI8Kit 3 Twig primitives (`twig/ui8kit/ui`,
     * `npx ui8kit@3 add --all` in `twig/`, PSR-4 `UI8Kit\\Twig\\` → `twig/ui8kit/runtime/`). Without a UI8Kit 3
     * install: the legacy codegen Twig directory.
     *
     * @return list<string>
     */
    public static function twigRoots(?string $themeDir = null): array
    {
        $themeDir ??= get_template_directory();
        if (is_dir($themeDir . '/twig/ui8kit/ui') && is_dir($themeDir . '/twig/kit/ui')) {
            return [$themeDir . '/twig/kit/ui', $themeDir . '/twig/ui8kit/ui'];
        }
        $legacy = self::resolveTwig($themeDir);

        return $legacy === null ? [] : [$legacy];
    }

    /** @return list<string> */
    public static function candidates(string $themeDir): array
    {
        return [
            $themeDir . '/ui8kit/ui',
        ];
    }

    /** Twig primitives (UI8Kit 3), or null. */
    public static function resolveTwig(?string $themeDir = null): ?string
    {
        $themeDir ??= get_template_directory();

        return is_dir($themeDir . '/twig/ui8kit/ui') ? $themeDir . '/twig/ui8kit/ui' : null;
    }
}
