<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API of the editor (namespace wpfasty-builder/v1). Every route needs a logged-in user with the capability
 * named below and the wp_rest nonce (X-WP-Nonce), which the editor receives from wp_add_inline_script.
 *   GET  /catalog?source=&collection=   edit_pages   blocks of a source (or of one collection), data sources
 *   GET  /layout/<page>                 edit_page    draft entry, issues, preview URL
 *   POST /layout/<page>                 edit_page    save the draft; publish=true also needs publish_pages
 *   POST /sources                       manage_options  import a remote source {id, url}
 */
final class Rest
{
    public const NS = 'wpfasty-builder/v1';

    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
    }

    public static function routes(): void
    {
        register_rest_route(self::NS, '/catalog', [
            'methods' => 'GET',
            'callback' => [self::class, 'catalog'],
            'permission_callback' => static fn (): bool => current_user_can('edit_pages'),
            'args' => [
                'source' => ['type' => 'string', 'default' => 'ui8kit', 'sanitize_callback' => 'sanitize_key'],
                'collection' => ['type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_key'],
            ],
        ]);
        $page = static fn (WP_REST_Request $r): bool => get_post_type((int) $r['id']) === 'page' && current_user_can(
            'edit_page',
            (int) $r['id'],
        );
        register_rest_route(self::NS, '/layout/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [self::class, 'getLayout'], 'permission_callback' => $page],
            ['methods' => 'POST', 'callback' => [self::class, 'saveLayout'], 'permission_callback' => $page],
        ]);
        register_rest_route(self::NS, '/sources', [
            'methods' => 'POST',
            'callback' => [self::class, 'importSource'],
            'permission_callback' => static fn (): bool => current_user_can('manage_options'),
        ]);
    }

    public static function catalog(WP_REST_Request $r): WP_REST_Response|WP_Error
    {
        $catalog = Sources::catalog((string) $r['source']);
        if ($catalog === null) {
            return new WP_Error('wpfb_source', __('Unknown source.', 'wpfasty-builder'), ['status' => 404]);
        }
        $col = (string) $r['collection'];
        return new WP_REST_Response([
            'source' => (string) $r['source'],
            'sources' => array_map(
                static fn ($s): array => ['id' => $s->id(), 'label' => $s->label()],
                array_values(Sources::all()),
            ),
            'collections' => $catalog->collections(),
            'dataSources' => array_values(DataSources::all()),
        ] + $catalog->forEditor($col !== '' && $catalog->collection($col) !== null ? $col : null));
    }

    public static function getLayout(WP_REST_Request $r): WP_REST_Response
    {
        $id = (int) $r['id'];
        $entry = Store::get($id, true);
        return new WP_REST_Response([
            'layout' => $entry,
            'published' => Store::has($id),
            'previewUrl' => Front::previewUrl($id),
            'permalink' => (string) get_permalink($id),
        ]);
    }

    public static function saveLayout(WP_REST_Request $r): WP_REST_Response|WP_Error
    {
        $id = (int) $r['id'];
        $publish = (bool) $r->get_param('publish');
        if ($publish && !current_user_can('publish_pages')) {
            return new WP_Error(
                'wpfb_forbidden',
                __('You cannot publish pages.', 'wpfasty-builder'),
                ['status' => 403],
            );
        }
        $body = $r->get_json_params();
        $result = Store::save($id, is_array($body['layout'] ?? null) ? $body['layout'] : [], $publish);
        if ($result instanceof WP_Error) {
            return $result;
        }
        [$entry, $issues] = $result;
        return new WP_REST_Response([
            'layout' => $entry,
            'issues' => $issues,
            'published' => Store::has($id),
            'previewUrl' => Front::previewUrl($id),
        ]);
    }

    public static function importSource(WP_REST_Request $r): WP_REST_Response|WP_Error
    {
        try {
            $stats = Sources::import(sanitize_key((string) $r->get_param('id')), (string) $r->get_param('url'));
        } catch (\Throwable $e) {
            return new WP_Error('wpfb_import', $e->getMessage(), ['status' => 400]);
        }
        return new WP_REST_Response($stats);
    }
}
