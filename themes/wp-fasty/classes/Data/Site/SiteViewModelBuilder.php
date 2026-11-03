<?php

declare(strict_types=1);

namespace WPFasty\Data\Site;

use WPFasty\Core\Model\SiteInfo;

/** Context `site`, `shell`, `sheet`, `search`, `hero`, `error` from Core data. */
final class SiteViewModelBuilder
{
    /** @return array<string, mixed> */
    public function site(SiteInfo $site, string $year): array
    {
        return [
            'title' => $site->title,
            'url' => $site->url,
            'themeUrl' => $site->themeUrl,
            'stylesheetUrl' => $site->stylesheetUrl,
            'lang' => $site->lang,
            'description' => $site->description,
            'charset' => $site->charset,
            'year' => $year,
            'logo' => null === $site->logo ? null : [
                'url' => $site->logo->url,
                'width' => $site->logo->width,
                'height' => $site->logo->height,
                'alt' => $site->logo->alt,
            ],
        ];
    }

    /** The `wpfasty_shell` value: `app-shell`, anything else is `classic`. */
    public function shell(mixed $filtered): string
    {
        return 'app-shell' === \WPFasty\Core\Cast::string($filtered) ? 'app-shell' : 'classic';
    }

    /** @return array<string, string> */
    public function sheetIds(string $shell): array
    {
        $prefix = 'app-shell' === $shell ? 'app-shell' : 'classic';

        return [
            'trigger' => $prefix.'-mobile-sheet-trigger',
            'panel' => $prefix.'-mobile-sheet-panel',
            'title' => $prefix.'-mobile-sheet-title',
        ];
    }

    /**
     * @param array{placeholder: string, submit: string, label: string} $labels
     *
     * @return array<string, string>
     */
    public function search(SiteInfo $site, string $query, array $labels): array
    {
        return [
            'action' => $site->url,
            'query' => $query,
            'placeholder' => $labels['placeholder'],
            'submit' => $labels['submit'],
            'label' => $labels['label'],
        ];
    }

    /**
     * @param array{primaryLabel: string, secondaryLabel: string} $labels
     *
     * @return array<string, string>
     */
    public function hero(SiteInfo $site, array $labels): array
    {
        return [
            'primaryLabel' => $labels['primaryLabel'],
            'primaryUrl' => $site->blogUrl,
            'secondaryLabel' => $labels['secondaryLabel'],
            'secondaryUrl' => $site->url,
        ];
    }

    /**
     * @param array{title: string, message: string, homeLabel: string} $labels
     *
     * @return array<string, mixed>
     */
    public function error(SiteInfo $site, int $code, array $labels): array
    {
        return [
            'code' => $code,
            'title' => $labels['title'],
            'message' => $labels['message'],
            'homeUrl' => $site->url,
            'homeLabel' => $labels['homeLabel'],
        ];
    }
}
