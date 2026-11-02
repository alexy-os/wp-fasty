<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/** Site identity and theme URLs (get_bloginfo(), home_url(), theme directory URIs). */
final class SiteInfo
{
    public function __construct(
        public readonly mixed $title,
        public readonly string $url,
        public readonly string $themeUrl,
        public readonly string $stylesheetUrl,
        public readonly mixed $lang,
        public readonly mixed $description,
        public readonly mixed $charset,
        public readonly ?Logo $logo,
        /** posts page permalink, else the home URL */
        public readonly string $blogUrl,
    ) {
    }
}
