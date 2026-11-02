<?php

declare(strict_types=1);

namespace WPFasty\Data\Archive;

use WPFasty\Core\Model\ArchiveState;
use WPFasty\Core\Port\PageLinkProviderInterface;

/** Context `archive` and `pagination`. */
final class ArchiveViewModelBuilder
{
    /** get_pagenum_link() of this page number becomes the `{0}` placeholder of the builder Pager. */
    private const PLACEHOLDER_PAGE = 987654321;

    /** @return array<string, mixed> */
    public function archive(ArchiveState $state, ?string $searchTitle): array
    {
        return [
            'title' => null !== $state->searchQuery ? (string) $searchTitle : $state->title,
            'description' => $state->description,
            'searchQuery' => $state->searchQuery,
            'type' => $state->type,
        ];
    }

    /** @return array<string, mixed>|null null on a single page (or without a main query) */
    public function pagination(ArchiveState $state, PageLinkProviderInterface $links): ?array
    {
        $max = (int) $state->maxPages;
        if ($max <= 1) {
            return null;
        }
        $paged = $state->paged;

        return [
            'current' => $paged,
            'total' => $max,
            'prevUrl' => $paged > 1 ? $links->pageLink($paged - 1) : null,
            'nextUrl' => $paged < $max ? $links->pageLink($paged + 1) : null,
            // link mode of the builder Pager (codegen-x pageUrl / firstUrl): page {0}, page 1; unescaped (the
            // builder passes them through its Safe URL check, esc_url would drop the braces)
            'url' => str_replace((string) self::PLACEHOLDER_PAGE, '{0}', $links->pageLink(self::PLACEHOLDER_PAGE, false)),
            'firstUrl' => $links->pageLink(1, false),
        ];
    }
}
