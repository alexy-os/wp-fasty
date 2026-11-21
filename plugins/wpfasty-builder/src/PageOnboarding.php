<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/** Manual-core new-page presentation. No Agent, SDK or worker dependency. */
final class PageOnboarding
{
    public const MARKER = '_wpfb_onboarding';
    public const SHELL = '_wpfb_page_shell';

    public static function eligible(int $id): bool
    {
        return 'draft' === get_post_status($id) && '1' === Cast::string(get_post_meta($id, self::MARKER, true)) && !Store::has($id) && [] === Layout::blocks(Store::get($id, true) ?? []);
    }

    /** @return array{mode: string}|\WP_Error */
    public static function finish(\WP_REST_Request $request): array|\WP_Error
    {
        $id = Cast::int($request['id']);
        $body = Cast::map($request->get_json_params());
        $mode = $body['mode'] ?? null;
        if (!current_user_can('edit_page', $id) || !wp_verify_nonce($request->get_header('X-WP-Nonce') ?? '', 'wp_rest')) {
            return new \WP_Error('wpfb_forbidden', 'Not allowed.', ['status' => 403]);
        }
        if (!in_array($mode, ['standard', 'blank', 'sidebar'], true)) {
            return new \WP_Error('wpfb_page_shell', 'Unsupported page layout.', ['status' => 400]);
        }

        $starterId = $body['starter'] ?? '';
        $starter = is_string($starterId) && '' !== $starterId ? Starters::get(sanitize_key($starterId)) : null;
        if ('' !== $starterId && null === $starter) {
            return new \WP_Error('wpfb_starter', 'This starter is not available on this site.', ['status' => 400]);
        }

        return DraftGuard::locked($id, static function () use ($id, $mode, $starter): array|\WP_Error {
            if (!self::eligible($id) || 0 !== DraftGuard::version($id)) {
                return new \WP_Error('wpfb_onboarding_conflict', 'The page is no longer new and empty. Reload the Builder.', ['status' => 409]);
            }
            // a starter's blocks come from the plugin's own starter file (Starters), never from the request
            $result = PageOperations::replaceLocked($id, null !== $starter ? self::source($starter) : 'ui8kit', null !== $starter ? self::blocks($starter) : [], 0);
            if (is_wp_error($result)) {
                return $result;
            }
            update_post_meta($id, self::SHELL, $mode);
            delete_post_meta($id, self::MARKER);

            return ['mode' => $mode];
        });
    }

    /**
     * Agent reference port (Chat attachment of an onboarding card; modules/builder-agent Chat::turn `screenId`): the
     * composition of a starter available on this site (Starters::get), read from the plugin's own starter file, never
     * from client JSON; null when the starter is unknown, unavailable or of another source. It replaces the scan of
     * installed template collections, which was deleted with the collections (ADR-007).
     *
     * @return array{schema: string, source: string, id: string, blocks: list<array<string, mixed>>}|null
     */
    public static function reference(string $source, string $id): ?array
    {
        $starter = 1 === preg_match('/^[a-z0-9_-]{1,64}$/', $id) ? Starters::get($id) : null;
        if (null === $starter || self::source($starter) !== $source) {
            return null;
        }

        return ['schema' => 'wpfasty-screen-reference/1', 'source' => $source, 'id' => $id, 'blocks' => self::blocks($starter)];
    }

    /** @param array{layout: array<string, mixed>} $starter */
    private static function source(array $starter): string
    {
        return sanitize_key(Cast::string($starter['layout']['source'] ?? 'ui8kit'));
    }

    /**
     * The blocks of a starter as layout blocks (Layout::normalize sanitizes them again on save).
     *
     * @param array{layout: array<string, mixed>} $starter
     *
     * @return list<array<string, mixed>>
     */
    private static function blocks(array $starter): array
    {
        $out = [];
        foreach (Cast::list($starter['layout']['blocks'] ?? []) as $b) {
            $b = Cast::map($b);
            $out[] = ['id' => Cast::string($b['id'] ?? ''), 'block' => Cast::string($b['block'] ?? ''), 'variant' => Cast::string($b['variant'] ?? ''), 'props' => Cast::map($b['props'] ?? []), 'bindings' => Cast::map($b['bindings'] ?? [])];
        }

        return $out;
    }
}
