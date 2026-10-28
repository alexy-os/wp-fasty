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
 *   GET  /links                         edit_pages   link-token targets of the site (Links::index): site, pages,
 *                                                    sections with the URL of each token, for the editor's link picker
 *   GET  /layout/<page>                 edit_page    draft entry, issues, preview URL
 *   POST /layout/<page>                 edit_page    save the draft; publish=true also needs publish_pages
 *   POST /pages                         edit_pages   create a draft page from the "New page" panel: {title, slug?,
 *                                                    starter?} → 201 {id, slug, url, blocks} (Pages::create; a starter
 *                                                    is only blocks, put into the Builder draft; the page template is
 *                                                    not touched)
 *   POST /sources                       manage_options  import a remote source {id, url}
 *   POST /render-block                  edit_pages (edit_page with `page`)  one block's HTML for the live preview:
 *        {source?, page?, id?, block, variant, props?, bindings?} → {id, html, issues}; same sanitizing, props and
 *        bound data as the page template (Fragment), rendered by the site engine's BlockRenderer
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
        register_rest_route(self::NS, '/links', [
            'methods' => 'GET',
            'callback' => [self::class, 'links'],
            'permission_callback' => static fn (): bool => current_user_can('edit_pages'),
        ]);
        $page = static fn (WP_REST_Request $r): bool => get_post_type((int) $r['id']) === 'page' && current_user_can(
            'edit_page',
            (int) $r['id'],
        );
        register_rest_route(self::NS, '/layout/(?P<id>\d+)', [
            ['methods' => 'GET', 'callback' => [self::class, 'getLayout'], 'permission_callback' => $page],
            ['methods' => 'POST', 'callback' => [self::class, 'saveLayout'], 'permission_callback' => $page],
        ]);
        register_rest_route(self::NS, '/render-block', [
            'methods' => 'POST',
            'callback' => [self::class, 'renderBlock'],
            'permission_callback' => static function (WP_REST_Request $r): bool {
                $page = (int) $r->get_param('page');
                return $page > 0 ? current_user_can('edit_page', $page) : current_user_can('edit_pages');
            },
        ]);
        register_rest_route(self::NS, '/pages', [
            'methods' => 'POST',
            'callback' => [self::class, 'createPage'],
            // edit_pages is the create_posts capability of the page post type
            'permission_callback' => static fn (): bool => current_user_can('edit_pages'),
            'args' => [
                'title' => ['type' => 'string', 'required' => true],
                'slug' => ['type' => 'string', 'default' => ''],
                // 'empty' = Pages::EMPTY (a literal: registering the routes loads no other class)
                'starter' => ['type' => 'string', 'default' => 'empty', 'sanitize_callback' => 'sanitize_key'],
            ],
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
        $editor = $catalog->forEditor($col !== '' && $catalog->collection($col) !== null ? $col : null);
        $editor['blocks'] = Previews::attach($editor['blocks'], Sources::get((string) $r['source']));
        $editor['collection'] = Previews::attachPages($editor['collection'], Sources::get((string) $r['source']));
        return new WP_REST_Response([
            'source' => (string) $r['source'],
            'sources' => array_map(
                static fn ($s): array => ['id' => $s->id(), 'label' => $s->label()],
                array_values(Sources::all()),
            ),
            'collections' => $catalog->collections(),
            'dataSources' => array_values(DataSources::all($catalog)),
        ] + $editor);
    }

    public static function links(): WP_REST_Response
    {
        return new WP_REST_Response(Links::index());
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

    public static function renderBlock(WP_REST_Request $r): WP_REST_Response|WP_Error
    {
        $body = $r->get_json_params();
        $body = is_array($body) ? $body : [];
        $source = sanitize_key((string) ($body['source'] ?? 'ui8kit'));
        $catalog = Sources::catalog($source);
        if ($catalog === null) {
            return new WP_Error('wpfb_source', __('Unknown source.', 'wpfasty-builder'), ['status' => 404]);
        }
        $input = [
            'id' => $body['id'] ?? null,
            'block' => $body['block'] ?? null,
            'variant' => $body['variant'] ?? null,
            'props' => is_array($body['props'] ?? null) ? $body['props'] : [],
            'bindings' => is_array($body['bindings'] ?? null) ? $body['bindings'] : [],
        ];
        $out = Fragment::render(
            $catalog,
            $source,
            $input,
            array_keys(DataSources::all()),
            [DataSources::class, 'resolve'],
            static fn (string $template, array $props): string => Front::block($source, $template, $props),
        );
        if ($out === null) {
            return new WP_Error('wpfb_block', __('Unknown block or variant.', 'wpfasty-builder'), ['status' => 400]);
        }
        return new WP_REST_Response($out);
    }

    public static function createPage(WP_REST_Request $r): WP_REST_Response|WP_Error
    {
        $out = Pages::create(
            (string) $r->get_param('title'),
            (string) $r->get_param('slug'),
            sanitize_key((string) ($r->get_param('starter') ?? Pages::EMPTY)),
        );
        return $out instanceof WP_Error ? $out : new WP_REST_Response($out, 201);
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
