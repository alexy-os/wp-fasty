<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/** The author of an entry (Codex user: title = display name). */
final class Author
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $url,
    ) {
    }
}
