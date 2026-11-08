<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WPFastyBuilder\Source\SourceInterface;

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

        return DraftGuard::locked($id, static function () use ($id, $mode): array|\WP_Error {
            if (!self::eligible($id) || 0 !== DraftGuard::version($id)) {
                return new \WP_Error('wpfb_onboarding_conflict', 'The page is no longer new and empty. Reload the Builder.', ['status' => 409]);
            }
            $result = PageOperations::replaceLocked($id, 'ui8kit', [], 0);
            if (is_wp_error($result)) {
                return $result;
            }
            update_post_meta($id, self::SHELL, $mode);
            delete_post_meta($id, self::MARKER);

            return ['mode' => $mode];
        });
    }

    /** Resolve a reference only from this installed runtime's bounded screen index, never client JSON.
     * @return array<string, mixed>|null
     */
    public static function reference(string $source, string $id): ?array
    {
        $catalog = Sources::catalog($source);
        if (null === $catalog || strlen($id) > 160) {
            return null;
        }
        foreach (self::screens($catalog, Sources::get($source)) as $screen) {
            if ($id === $screen['id']) {
                return ['schema' => 'wpfasty-screen-reference/1', 'source' => $source, 'id' => $id, 'blocks' => $screen['blocks']];
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public static function screens(Catalog $catalog, ?SourceInterface $source): array
    {
        $pages = [];
        foreach ($catalog->collections() as $collection) {
            if ('template' !== ($collection['type'] ?? '')) {
                continue;
            }
            $full = Previews::attachPages($catalog->collection(Cast::string($collection['id'])), $source);
            foreach (Cast::list($full['pages'] ?? []) as $page) {
                if (!is_array($page) || !isset($page['preview']) || [] === ($page['blocks'] ?? [])) {
                    continue;
                }
                $page = Cast::map($page);
                $valid = true;
                foreach (Cast::list($page['blocks']) as $block) {
                    if (null === $catalog->variant(Cast::string(Cast::path($block, 'block')), Cast::string(Cast::path($block, 'variant')))) {
                        $valid = false;
                    }
                }
                if ($valid) {
                    $page['id'] = Cast::string($collection['id']).':'.Cast::string($page['id']);
                    $pages[] = $page;
                }
                if (12 === count($pages)) {
                    return $pages;
                }
            }
        }

        return $pages;
    }
}
