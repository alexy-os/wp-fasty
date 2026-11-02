<?php

declare(strict_types=1);

namespace WPFasty\Data\Seo;

/** Plain text -> a meta description: one line, at most $max characters, cut at a word with an ellipsis. */
final class MetaDescriptionBuilder
{
    public function build(string $text, int $max = 160): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');
        if (false !== $space && $space > (int) ($max / 2)) {
            $cut = mb_substr($cut, 0, $space);
        }

        return rtrim($cut, ' ,.;:-–—').'…';
    }
}
