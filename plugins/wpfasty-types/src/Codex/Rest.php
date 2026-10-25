<?php

declare(strict_types=1);

namespace WPFastyTypes\Codex;

use WP_Post;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * /wpfasty/v1/codex — the Codex HTTP form (agreed with Ops Engineer / XGenBot):
 *   GET /                                  {codex_version, profile, manifest_digest}
 *   GET /entries/{kind}?locale&page&per_page {data, pagination{page, per_page, total, total_pages}}, id asc (bytes)
 *   GET /entries/{kind}/{id}?locale        bare codex-entry/v1 bytes, Codex-Digest + ETag (also media, user)
 *   GET /taxonomies, /terms/{taxonomy_id}  same envelope; terms by codex-term/v1, `count` next to data: {counts{id: n}}
 *   GET /settings?locale&page&per_page     same envelope of SettingsV04 (one per locale)
 *   GET /settings/{id}?locale              bare SettingsV04 JCS bytes, Codex-Digest + ETag
 * Default locale = settings.language (Mapper::locale).
 * Only published + public records; anything else is 404 (never 403). Errors: {error{code, message, status}}.
 * Bodies are exact bytes: rest_pre_serve_request prints them so WordPress does not re-encode the JSON.
 */
final class Rest
{
    private const RAW = 'wpfasty_codex_raw';
    private const CT = 'application/json; charset=utf-8';

    public static function attach(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
        add_filter('rest_pre_serve_request', [self::class, 'serve'], 10, 4);
        add_action('created_term', static fn (int $id): mixed => Mapper::stampTerm($id, true));
        add_action('edited_term', static fn (int $id): mixed => Mapper::stampTerm($id, false));
    }

    public static function routes(): void
    {
        $base = Profile::REST_BASE;
        $open = ['methods' => WP_REST_Server::READABLE, 'permission_callback' => '__return_true'];
        $add = static function (string $path, callable $cb) use ($open, $base): void {
            register_rest_route(Profile::REST_NS, $base . $path, $open + ['callback' => $cb]);
        };
        $add('', [self::class, 'discovery']);
        $add('/entries/(?P<kind>[^/]+)', [self::class, 'entries']);
        $add('/entries/(?P<kind>[^/]+)/(?P<id>[^/]+)', [self::class, 'entry']);
        $add('/taxonomies', [self::class, 'taxonomies']);
        $add('/terms/(?P<taxonomy>[^/]+)', [self::class, 'terms']);
        $add('/settings', [self::class, 'settings']);
        $add('/settings/(?P<id>[^/]+)', [self::class, 'setting']);
    }

    private static function raw(string $bytes, int $status = 200, array $headers = []): WP_REST_Response
    {
        $r = new WP_REST_Response(null, $status, ['Content-Type' => self::CT] + $headers);
        $r->header('Content-Type', self::CT);
        $r->set_data([self::RAW => $bytes]);
        return $r;
    }

    private const QUERY = ['locale', 'page', 'per_page'];

    public static function error(string $code, string $message, int $status, ?array $details = null): WP_REST_Response
    {
        $e = ['code' => $code, 'message' => $message, 'status' => $status];
        if ($details) {
            $e['details'] = $details;
        }
        return self::raw(Json::jcs(['error' => $e]), $status);
    }

    /** Print the exact bytes of our responses. */
    public static function serve(bool $served, mixed $result, WP_REST_Request $request, WP_REST_Server $server): bool
    {
        $data = $result instanceof WP_REST_Response ? $result->get_data() : null;
        if ($served || !is_array($data) || !isset($data[self::RAW])) {
            return $served;
        }
        $server->send_header('Content-Type', self::CT);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON bytes of a REST response.
        echo $data[self::RAW];
        return true;
    }

    /** @param array<string, int>|null $counts terms only: `count` per term id of the page, outside the canonical bytes */
    private static function envelope(array $items, int $page, int $per, int $total, ?array $counts = null): string
    {
        $p = Json::jcs([
            'page' => $page, 'per_page' => $per, 'total' => $total,
            'total_pages' => $per > 0 ? (int) ceil($total / $per) : 0,
        ]);
        $c = $counts === null ? '' : '"counts":' . Json::jcs((object) $counts) . ',';
        return '{' . $c . '"data":[' . implode(',', $items) . '],"pagination":' . $p . '}';
    }

    /**
     * Query of every route (as fastygo/backend /codex/v1): only locale, page, per_page; anything else is 400.
     *
     * @return array{0: string, 1: int, 2: int}|WP_REST_Response locale, page, per_page
     */
    private static function query(WP_REST_Request $req): array|WP_REST_Response
    {
        $q = $req->get_query_params();
        unset($q['rest_route']); // plain permalinks route through ?rest_route=
        foreach (array_keys($q) as $k) {
            if (!in_array((string) $k, self::QUERY, true)) {
                return self::error('validation_error', 'unknown query parameter', 400, ['parameter' => (string) $k]);
            }
        }
        $locale = Mapper::locale();
        if (isset($q['locale'])) {
            $l = is_string($q['locale']) ? $q['locale'] : '';
            if (preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $l) !== 1) {
                return self::error('validation_error', 'locale is invalid', 400, ['locale' => $l]);
            }
            if ($l !== $locale) {
                return self::error('not_found', 'locale was not found', 404);
            }
        }
        $page = 1;
        $per = Profile::PER_PAGE;
        foreach (['page' => &$page, 'per_page' => &$per] as $k => &$v) {
            if (isset($q[$k])) {
                $raw = is_string($q[$k]) ? $q[$k] : '';
                if (preg_match('/^\+?0*[1-9][0-9]{0,17}$/', $raw) !== 1) {
                    return self::error('validation_error', "{$k} must be a positive integer", 400, [$k => $raw]);
                }
                // per_page above the maximum is clamped, not rejected (fastygo/backend /codex/v1)
                $v = $k === 'per_page' ? min((int) $raw, Profile::MAX_PER_PAGE) : (int) $raw;
            }
        }
        unset($v);
        return [$locale, $page, $per];
    }

    private static function site(): Site|WP_REST_Response
    {
        return Site::current() ?? self::error('not_found', 'manifest was not found', 404);
    }

    private static function publicPost(mixed $post, string $postType): bool
    {
        return $post instanceof WP_Post && $post->post_type === $postType && $post->post_status === 'publish'
            && $post->post_password === '';
    }

    public static function discovery(WP_REST_Request $req): WP_REST_Response
    {
        $site = self::site();
        if ($site instanceof WP_REST_Response) {
            return $site;
        }
        $q = self::query($req);
        if ($q instanceof WP_REST_Response) {
            return $q;
        }
        return self::raw(Json::jcs([
            'codex_version' => Profile::CODEX_VERSION,
            'profile' => Profile::ENTRY_PROFILE,
            'manifest_digest' => $site->digest,
        ]));
    }

    public static function entries(WP_REST_Request $req): WP_REST_Response
    {
        $site = self::site();
        if ($site instanceof WP_REST_Response) {
            return $site;
        }
        $q = self::query($req);
        if ($q instanceof WP_REST_Response) {
            return $q;
        }
        $kind = (string) $req['kind'];
        if (!in_array($kind, $site->postKinds(), true)) {
            return self::error('not_found', 'kind was not found', 404);
        }
        [, $page, $per] = $q;
        $ids = self::sortedIds($kind, (string) $site->wp($kind)['post_type']);
        $slice = array_slice($ids, ($page - 1) * $per, $per);
        $map = new Mapper($site);
        $items = [];
        $postType = (string) $site->wp($kind)['post_type'];
        $wpIds = array_map(static fn (string $id): int => Mapper::wpId($kind, $id), $slice);
        foreach (self::posts($wpIds, $postType) as $post) {
            $items[] = Canonical::entryBytes($map->entry($post, $kind));
        }
        return self::raw(self::envelope($items, $page, $per, count($ids)));
    }

    /**
     * Codex ids of the public posts of a kind, sorted bytewise (one ids-only query).
     *
     * @return list<string>
     */
    public static function sortedIds(string $kind, string $postType): array
    {
        $ids = get_posts([
            'post_type' => $postType, 'post_status' => 'publish', 'has_password' => false, 'fields' => 'ids',
            'posts_per_page' => -1, 'no_found_rows' => true, 'orderby' => 'ID', 'order' => 'ASC',
            'suppress_filters' => true,
        ]);
        $out = array_map(static fn ($id): string => Mapper::id($kind, (int) $id), $ids);
        sort($out, SORT_STRING);
        return $out;
    }

    /**
     * Posts in the given order with meta + term caches primed (no N+1).
     *
     * @param list<int> $ids
     * @return list<WP_Post>
     */
    public static function posts(array $ids, string $postType): array
    {
        if ($ids === []) {
            return [];
        }
        $posts = get_posts([
            'post__in' => $ids, 'post_type' => $postType, 'post_status' => 'publish', 'orderby' => 'post__in',
            'posts_per_page' => count($ids), 'no_found_rows' => true, 'ignore_sticky_posts' => true,
            'update_post_meta_cache' => true, 'update_post_term_cache' => true, 'suppress_filters' => true,
        ]);
        return array_values(array_filter($posts, static fn ($p): bool => $p instanceof WP_Post));
    }

    public static function entry(WP_REST_Request $req): WP_REST_Response
    {
        $site = self::site();
        if ($site instanceof WP_REST_Response) {
            return $site;
        }
        $kind = (string) $req['kind'];
        $id = (string) $req['id'];
        $q = self::query($req);
        if ($q instanceof WP_REST_Response) {
            return $q;
        }
        if (!in_array($kind, [...$site->postKinds(), 'media', 'user'], true) || $site->resource($kind) === null) {
            return self::error('not_found', 'kind was not found', 404);
        }
        $wpId = Mapper::wpId($kind, $id);
        $map = new Mapper($site);
        $entry = null;
        if ($kind === 'media' && $site->resource('media') !== null && $wpId > 0) {
            $att = get_post($wpId);
            $parent = $att instanceof WP_Post && $att->post_parent > 0 ? get_post($att->post_parent) : null;
            if ($att instanceof WP_Post && $att->post_type === 'attachment' && $att->post_status === 'inherit'
                && ($parent === null || ($parent instanceof WP_Post && $parent->post_status === 'publish'))
            ) {
                $entry = $map->media($att);
            }
        } elseif ($kind === 'user' && $site->resource('user') !== null && $wpId > 0) {
            $u = get_userdata($wpId);
            // only authors of public content are public
            if ($u instanceof \WP_User && count_user_posts($wpId, $site->postKinds(), true) > 0) {
                $entry = $map->user($u);
            }
        } elseif (in_array($kind, $site->postKinds(), true) && $wpId > 0) {
            $post = get_post($wpId);
            if (self::publicPost($post, (string) $site->wp($kind)['post_type'])) {
                $entry = $map->entry($post, $kind);
            }
        }
        if ($entry === null) {
            return self::error('not_found', 'content was not found', 404);
        }
        $bytes = Canonical::entryBytes($entry);
        $hex = hash('sha256', $bytes);
        return self::raw($bytes, 200, [
            'Codex-Digest' => Profile::ENTRY_PROFILE . ':sha256:' . $hex,
            'ETag' => "\"{$hex}\"",
        ]);
    }

    public static function taxonomies(WP_REST_Request $req): WP_REST_Response
    {
        $site = self::site();
        if ($site instanceof WP_REST_Response) {
            return $site;
        }
        $q = self::query($req);
        if ($q instanceof WP_REST_Response) {
            return $q;
        }
        [, $page, $per] = $q;
        $all = array_values(array_filter($site->allTaxonomies(), 'taxonomy_exists'));
        $map = new Mapper($site);
        $items = array_map(
            static fn (string $t): string => Json::jcs($map->taxonomy($t)),
            array_slice($all, ($page - 1) * $per, $per),
        );
        return self::raw(self::envelope($items, $page, $per, count($all)));
    }

    public static function terms(WP_REST_Request $req): WP_REST_Response
    {
        $site = self::site();
        if ($site instanceof WP_REST_Response) {
            return $site;
        }
        $q = self::query($req);
        if ($q instanceof WP_REST_Response) {
            return $q;
        }
        $tax = (string) $req['taxonomy'];
        if (!in_array($tax, $site->allTaxonomies(), true) || !taxonomy_exists($tax)) {
            return self::error('not_found', 'taxonomy was not found', 404);
        }
        [, $page, $per] = $q;
        $terms = get_terms(['taxonomy' => $tax, 'hide_empty' => false, 'update_term_meta_cache' => true]);
        $terms = is_array($terms)
            ? array_values(array_filter($terms, static fn ($t): bool => $t instanceof \WP_Term))
            : [];
        usort($terms, static fn ($a, $b): int => strcmp(Mapper::id($tax, $a->term_id), Mapper::id($tax, $b->term_id)));
        $map = new Mapper($site);
        $slice = array_slice($terms, ($page - 1) * $per, $per);
        $items = array_map(static fn (\WP_Term $t): string => Canonical::termBytes($map->term($t)), $slice);
        $counts = [];
        foreach ($slice as $t) {
            $counts[Mapper::id($tax, $t->term_id)] = (int) $t->count;
        }
        return self::raw(self::envelope($items, $page, $per, count($terms), $counts));
    }

    /** SettingsV04 bytes of the site (empty values dropped, as the contract adapter does). */
    private static function settingsBytes(Site $site): string
    {
        $s = (new Mapper($site))->settings();
        return Json::jcs(array_filter($s, static fn ($v): bool => $v !== '' && $v !== null));
    }

    public static function settings(WP_REST_Request $req): WP_REST_Response
    {
        $site = self::site();
        if ($site instanceof WP_REST_Response) {
            return $site;
        }
        $q = self::query($req);
        if ($q instanceof WP_REST_Response) {
            return $q;
        }
        [, $page, $per] = $q;
        // one settings record per locale; query() already answered an unknown locale with 404
        $items = array_slice([self::settingsBytes($site)], ($page - 1) * $per, $per);
        return self::raw(self::envelope($items, $page, $per, 1));
    }

    public static function setting(WP_REST_Request $req): WP_REST_Response
    {
        $site = self::site();
        if ($site instanceof WP_REST_Response) {
            return $site;
        }
        $q = self::query($req);
        if ($q instanceof WP_REST_Response) {
            return $q;
        }
        if ((string) $req['id'] !== Mapper::SETTINGS_ID) {
            return self::error('not_found', 'settings was not found', 404);
        }
        $bytes = self::settingsBytes($site);
        $hex = hash('sha256', $bytes);
        return self::raw($bytes, 200, [
            'Codex-Digest' => Profile::SETTINGS_PROFILE . ':sha256:' . $hex,
            'ETag' => "\"{$hex}\"",
        ]);
    }
}
