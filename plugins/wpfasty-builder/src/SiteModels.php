<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WPFastyBuilder\Codex\Sources as CodexSources;
use WPFastyTypes\Codex\Site;

/**
 * Site Codex models used by Records for the link/token index. No binding query, mapping or resolution engine (ADR-007).
 *
 * @phpstan-import-type SourceModel from CodexSources
 */
final class SiteModels
{
    /** @var array<string, SourceModel>|null */
    private static ?array $models = null;

    public static function site(): ?Site
    {
        return class_exists(Site::class) ? Site::current() : null;
    }

    /** @return array<string, SourceModel> Codex source models (Codex\Sources::fromManifest) */
    public static function models(): array
    {
        if (null === self::$models) {
            $site = self::site();
            self::$models = null !== $site ? CodexSources::fromManifest($site->manifest, $site->delivery) : [];
        }

        return self::$models;
    }
}
