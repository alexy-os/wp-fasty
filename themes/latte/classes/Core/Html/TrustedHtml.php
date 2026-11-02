<?php

declare(strict_types=1);

namespace WPFasty\Core\Html;

/**
 * HTML that WordPress core (or a core-escaping API) already generated: widget areas, comment lists and forms,
 * wp_link_pages(). Only adapters create it; the context keeps it as a string the views print unescaped.
 */
final class TrustedHtml implements \Stringable
{
    public function __construct(private readonly string $html)
    {
    }

    public function __toString(): string
    {
        return $this->html;
    }

    public static function empty(): self
    {
        return new self('');
    }

    public function isEmpty(): bool
    {
        return '' === $this->html;
    }
}
