<?php
/**
 * View helpers shared by both renderers, so Latte and Twig views have the same vocabulary:
 * filters esc_url, kses, safe_url; functions t, icon (+ the WordPress hook tags / functions).
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Template;

final class ViewHelpers
{
    /** filter names both engines register (RendererTest keeps them in sync) */
    public const FILTERS = ['esc_url', 'kses', 'safe_url'];

    /** function names both engines register */
    public const FUNCTIONS = [
        'icon', 't', 'wp_head', 'wp_footer', 'wp_body_open', 'language_attributes', 'body_class', 'do_action',
        'apply_filters',
    ];

    public static function escUrl(mixed $v): string
    {
        return function_exists('esc_url') ? esc_url((string) $v) : htmlspecialchars((string) $v, ENT_QUOTES);
    }

    public static function kses(mixed $v): string
    {
        return function_exists('wp_kses_post') ? wp_kses_post((string) $v) : strip_tags((string) $v);
    }

    /**
     * Same contract as Latte's automatic URL check in href/src/action: only http(s)/ftp, mailto/tel/sms,
     * relative and fragment URLs pass; anything else (javascript:, data:…) becomes ''.
     */
    public static function safeUrl(mixed $url): string
    {
        $url = (string) $url;
        return preg_match('~^(?:(?:https?|ftp)://[^@]+(?:/.*)?|(?:mailto|tel|sms):.+|[/?#].*|[^:]+)$~Di', $url) === 1
            ? $url
            : '';
    }

    public static function t(string $text, string $domain = 'latte'): string
    {
        return function_exists('__') ? __($text, $domain) : $text; // phpcs:ignore WordPress.WP.I18n
    }
}
