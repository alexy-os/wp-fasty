<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/** Title and URL of a neighbouring entry (previous / next post). */
final class PostLink
{
    public function __construct(
        public readonly string $title,
        public readonly string $url,
    ) {
    }
}
