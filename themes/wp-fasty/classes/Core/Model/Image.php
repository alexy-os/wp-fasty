<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

/** One size of an image attachment (Codex media: file, width, height, alt). */
final class Image
{
    public function __construct(
        public readonly string $url,
        public readonly string $width,
        public readonly string $height,
        public readonly string $alt,
        public readonly string $srcset,
        public readonly string $sizes,
    ) {
    }
}
