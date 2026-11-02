<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

/**
 * WP returns texturized, entity-encoded strings (&#8217;, &hellip;); Latte/Twig escape on output, so decode to plain
 * text here to avoid double-encoding.
 */
final class PlainText
{
    public static function from(string $value): string
    {
        return html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
