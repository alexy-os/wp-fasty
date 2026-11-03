<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/** The custom logo image. */
final class Logo
{
    public function __construct(
        public readonly mixed $url,
        public readonly mixed $width,
        public readonly mixed $height,
        public readonly mixed $alt,
    ) {
    }
}
