<?php

/**
 * Shared HTML normalizer of the parity gates: whitespace collapsed, equivalent entity spellings unified
 * (Latte keeps quotes literal in text nodes, Twig encodes them; both are the same DOM).
 *
 * @package Latte
 */

declare(strict_types=1);

return static function (string $html): string {
    $html = str_replace(['&#039;', '&#39;', '&apos;', '&#34;'], ['&#x27;', '&#x27;', '&#x27;', '&quot;'], $html);
    $html = (string) preg_replace_callback('/>([^<]*)</', static fn (array $m): string
        => '>' . str_replace(['&quot;', '&#x27;'], ['"', "'"], $m[1]) . '<', $html);
    $html = (string) preg_replace('/\s+/', ' ', $html);
    $html = (string) preg_replace('/>\s+</', '><', $html);
    $html = (string) preg_replace('/\s+>/', '>', $html);
    return trim($html);
};
