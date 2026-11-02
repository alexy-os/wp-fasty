<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/** What the current request is (conditional tags and theme switches the context depends on). */
final class RequestState
{
    public function __construct(
        /** the search terms on a search, else '' */
        public readonly string $searchQuery,
        public readonly bool $frontPage,
        public readonly bool $singular,
        /** the `wpfasty_shell` filter value (unnormalized) */
        public readonly mixed $shell,
        /** add_theme_support('wpfasty-full-width'): no content sidebar */
        public readonly bool $fullWidth,
        /** current year (footer) */
        public readonly string $year,
    ) {
    }
}
