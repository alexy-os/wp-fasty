<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/** Pure host-palette adapter: registered WP colours, not frontend branding or arbitrary CSS. */
final class AdminPalette
{
    public static function css(mixed $colors): string
    {
        $accent = is_array($colors) ? ($colors[2] ?? null) : null;
        if (!is_string($accent) || !preg_match('/^#[a-fA-F0-9]{6}$/', $accent)) {
            return '';
        }
        $channels = [];
        foreach ([1, 3, 5] as $offset) {
            $channel = hexdec(substr($accent, $offset, 2)) / 255;
            $channels[] = $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
        }
        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        $onAccent = (1.05 / ($luminance + 0.05)) >= (($luminance + 0.05) / 0.05) ? '#ffffff' : '#000000';

        return ':root{--wpfb-admin-accent:'.$accent.';--wpfb-admin-on-accent:'.$onAccent.';}';
    }
}
