<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Model\ArchiveState;
use WPFasty\Core\Port\ArchiveStateProviderInterface;
use WPFasty\Core\Port\PageLinkProviderInterface;

/** Conditional tags, archive title/description and paging of the main query. */
final class WordPressArchiveStateProvider implements ArchiveStateProviderInterface, PageLinkProviderInterface
{
    public function current(): ArchiveState
    {
        $searchQuery = is_search() ? (string) get_search_query() : null;
        global $wp_query;

        return new ArchiveState(
            $this->type(),
            $searchQuery,
            null !== $searchQuery ? '' : wp_strip_all_tags(get_the_archive_title()),
            null !== $searchQuery ? '' : wp_kses_post(get_the_archive_description()),
            isset($wp_query) && is_object($wp_query) ? (int) (get_object_vars($wp_query)['max_num_pages'] ?? 0) : null,
            max(1, (int) get_query_var('paged')),
        );
    }

    public function pageLink(int $page, bool $escape = true): string
    {
        return (string) get_pagenum_link($page, $escape);
    }

    private function type(): string
    {
        return match (true) {
            is_search() => 'search',
            is_category() => 'category',
            is_tag() => 'tag',
            is_author() => 'author',
            is_date() => 'date',
            is_post_type_archive() => 'post_type',
            is_tax() => 'taxonomy',
            is_home() => 'home',
            default => 'archive',
        };
    }
}
