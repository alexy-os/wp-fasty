<?php

declare(strict_types=1);

/**
 * Latte Skin — thin child theme (no Composer, no Application boot).
 *
 * @package Latte_Skin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Demo: enable app-shell layout (sidebar + mobile sheet).
 */
add_filter('wpfasty_shell', static fn (): string => 'app-shell');

/**
 * Demo context filter — skins may adjust presentation data only.
 *
 * @param array<string, mixed> $context
 * @return array<string, mixed>
 */
add_filter('wpfasty_context', static function (array $context): array {
    if (isset($context['site']) && is_array($context['site'])) {
        $context['site']['skin'] = 'latte-skin';
    }
    return $context;
});
