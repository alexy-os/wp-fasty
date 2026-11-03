<?php

declare(strict_types=1);

namespace WPFasty\Core\Html;

/**
 * HTML that WordPress core (or a core-escaping API) already generated: the_content, wp_link_pages(), widget areas,
 * comment lists and forms. Only adapters create it. The template context carries it as this object (ContextHtml
 * lists the fields); the renderers print it raw and escape every other value. JSON gives the plain string.
 */
final class TrustedHtml implements \Stringable, \JsonSerializable
{
    public function __construct(private readonly string $html)
    {
    }

    public function __toString(): string
    {
        return $this->html;
    }

    public function jsonSerialize(): string
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
