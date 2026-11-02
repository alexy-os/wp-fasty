<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/** The main query as an archive: what it lists and where it is in its pages. */
final class ArchiveState
{
    public function __construct(
        /** search|category|tag|author|date|post_type|taxonomy|home|archive */
        public readonly string $type,
        /** the search terms on a search, else null */
        public readonly ?string $searchQuery,
        /** archive title as plain text ('' on a search) */
        public readonly string $title,
        /** sanitized archive description HTML ('' on a search) */
        public readonly string $description,
        /** max_num_pages of the main query, null without a main query */
        public readonly ?int $maxPages,
        /** current page, 1-based */
        public readonly int $paged,
    ) {
    }
}
