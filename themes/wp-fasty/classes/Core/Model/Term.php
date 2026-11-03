<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/** A term an entry is assigned to (Codex term: taxonomy_id, name, slug). */
final class Term
{
    public function __construct(
        public readonly string $taxonomy,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $url,
    ) {
    }
}
