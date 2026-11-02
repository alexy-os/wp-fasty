<?php

declare(strict_types=1);

namespace WPFasty\Core\Port;

/** URL of a page of the current listing (get_pagenum_link()). */
interface PageLinkProviderInterface
{
    public function pageLink(int $page, bool $escape = true): string;
}
