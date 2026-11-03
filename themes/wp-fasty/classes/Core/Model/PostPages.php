<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/**
 * Pages of a multi-page entry (`<!--nextpage-->`) on its single view: view/v1 entry `pages` (current, total, links).
 * Only built when the entry has two or more pages.
 */
final class PostPages
{
    /**
     * @param list<string> $urls URL of every page, index 0 = page 1
     */
    public function __construct(
        public readonly int $current,
        public readonly array $urls,
    ) {
        if (count($urls) < 2 || $current < 1 || $current > count($urls)) {
            throw new \InvalidArgumentException('PostPages needs two or more pages and a current page within them.');
        }
    }

    public function total(): int
    {
        return count($this->urls);
    }
}
