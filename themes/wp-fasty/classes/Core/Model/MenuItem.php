<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/** One nav menu item as the theme needs it (no WordPress types). */
final class MenuItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $parentId,
        public readonly mixed $title,
        public readonly mixed $url,
        public readonly bool $current,
    ) {
    }
}
