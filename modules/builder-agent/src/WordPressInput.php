<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Audited WordPress request boundary, using ModulY's documented publisher-reviewed handler marker.
 *
 * @moduly-request-handler
 * No general environment/secret reader: fixed host names and query names only, raw form data remains
 * server-side and is validated after each caller's nonce/capability check. Never exposed as an Agent tool.
 */
final class WordPressInput
{
    public static function server(string $name): mixed
    {
        if (!in_array($name, ['WPFB_WORKER_KEY', 'WPFB_WORKER_PORT', 'WPFB_WORKER_TOKEN', 'REQUEST_METHOD'], true)) {
            return null;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Fixed FastCGI host parameters, not form input; caller validates exact flag, port digits/range or opaque token length without transforming it.
        $value = $_SERVER[$name] ?? null;

        return $value;
    }

    public static function query(string $name): string
    {
        if (!in_array($name, ['post', 'page', 'kind'], true) || !isset($_GET[$name]) || !is_string($_GET[$name])) {
            return '';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only query selector; callers enforce capability and numeric/closed-enum validation, not HTML output.
        $value = wp_unslash($_GET[$name]);

        return $value;
    }

    /** @return array<string, mixed> */
    public static function form(string $nonceAction): array
    {
        check_admin_referer($nonceAction);
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only called after the settings/policy nonce+capability check; strict typed JSON/settings validation follows, lossy text sanitization would corrupt policy JSON.
        $value = Cast::map(wp_unslash($_POST));

        return $value;
    }
}
