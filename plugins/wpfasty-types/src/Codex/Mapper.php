<?php

declare(strict_types=1);

namespace WPFastyTypes\Codex;

use WP_Post;
use WP_Term;
use WP_User;

/**
 * WordPress → Codex v0.4 records (entry / term / taxonomy / settings). Platform facts stay here: meta keys come
 * from codex.delivery.json, value types and privacy from codex.manifest.json (never from the `_` key prefix).
 * Reads only primed caches for a list (meta + term caches of the query), so a page is O(1) queries.
 */
final class Mapper
{
    /** Term timestamps: term meta written on create/edit (clock = filter wpfasty_codex_now). */
    public const TERM_CREATED = '_wpfasty_codex_created';
    public const TERM_UPDATED = '_wpfasty_codex_updated';
    public const EPOCH = '1970-01-01T00:00:00Z';
    public const SETTINGS_ID = 'site';

    public function __construct(private readonly Site $site)
    {
    }

    /**
     * Default locale = settings.language (get_locale as BCP 47, "en-US" when WordPress has none); "en" without settings
     * (outside WordPress). Decided with XGenBot.
     */
    public static function locale(): string
    {
        return self::language() ?? Profile::DEFAULT_LOCALE;
    }

    /** settings.language; null when there are no settings (no WordPress loaded). */
    public static function language(): ?string
    {
        if (!function_exists('get_locale')) {
            return null;
        }
        $l = str_replace('_', '-', (string) get_locale());
        return preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $l) === 1 ? $l : Profile::SETTINGS_LANGUAGE;
    }

    public static function id(string $kind, int $wpId): string
    {
        return $kind . '-' . $wpId;
    }

    /** WordPress id from a Codex id of a kind; 0 when it is not `<kind>-<n>`. */
    public static function wpId(string $kind, string $id): int
    {
        return preg_match('/^' . preg_quote($kind, '/') . '-([1-9][0-9]{0,18})$/', $id, $m) === 1 ? (int) $m[1] : 0;
    }

    private static function gmt(string $mysql): string
    {
        return str_replace(' ', 'T', $mysql) . 'Z';
    }

    /** @return array<string, mixed> */
    public function entry(WP_Post $post, string $kind): array
    {
        $fields = $this->site->fields($kind);
        $status = Profile::STATUS[$post->post_status] ?? 'draft';
        $modified = self::gmt($post->post_modified_gmt);
        $dated = $post->post_date_gmt !== '0000-00-00 00:00:00' ? self::gmt($post->post_date_gmt) : $modified;
        $e = [
            'id' => self::id($kind, $post->ID),
            'locale' => self::locale(),
            'kind' => $kind,
            'status' => $status,
            'visibility' => $post->post_status === 'private' || $post->post_password !== '' ? 'private' : 'public',
            'slug' => $post->post_name !== '' ? $post->post_name : self::id($kind, $post->ID),
            'title' => (string) $post->post_title,
            'content' => (string) $post->post_content,
            'excerpt' => (string) $post->post_excerpt,
        ];
        if (isset($fields['author_id']) && (int) $post->post_author > 0) {
            $e['author_id'] = self::id('user', (int) $post->post_author);
        }
        if ((int) $post->post_parent > 0) {
            $e['parent_id'] = self::id($kind, (int) $post->post_parent);
        }
        $thumb = isset($fields['featured_media_id']) ? (int) get_post_meta($post->ID, '_thumbnail_id', true) : 0;
        if ($thumb > 0) {
            $e['featured_media_id'] = self::id('media', $thumb);
        }
        $tpl = (string) get_post_meta($post->ID, '_wp_page_template', true);
        if ($tpl !== '' && $tpl !== 'default') {
            $e['template'] = $tpl;
        }
        $terms = [];
        foreach ($this->site->taxonomies($kind) as $tax) {
            $list = get_the_terms($post, $tax);
            foreach (is_array($list) ? $list : [] as $t) {
                $terms[] = ['taxonomy' => $tax, 'term_id' => self::id($tax, (int) $t->term_id)];
            }
        }
        $e['terms'] = $terms;
        if (isset($fields['order'])) {
            $e['order'] = (int) $post->menu_order;
        }
        if ($post->post_type === 'post') {
            $e['pinned'] = is_sticky($post->ID);
            $e['format'] = (string) (get_post_format($post) ?: 'standard');
        }
        if (post_type_supports($post->post_type, 'comments')) {
            $e['discussion'] = [
                'comments' => $post->comment_status === 'open',
                'pings' => $post->ping_status === 'open',
            ];
        }
        $e['origin'] = [['system' => Profile::SYSTEM, 'id' => (string) $post->ID]];
        $e['metadata'] = $this->metadata($post->ID, $kind, $fields);
        $e['version'] = 1;
        $e['created_at'] = strcmp($dated, $modified) > 0 ? $modified : $dated;
        $e['updated_at'] = $modified;
        if ($status === 'published' || $status === 'scheduled') {
            $e['published_at'] = $dated;
        }
        if ($status === 'trashed') {
            $e['deleted_at'] = $modified;
        }
        return $e;
    }

    /**
     * Typed metadata of the manifest fields that WordPress stores as meta (delivery `meta`). Unset / empty meta is
     * "no value": the key is left out (nullable). Sensitive fields are marked private.
     *
     * @param array<string, array<string, mixed>> $fields
     * @return array<string, array{value: mixed, private?: bool}>
     */
    private function metadata(int $postId, string $kind, array $fields): array
    {
        $md = [];
        foreach ((array) ($this->site->wp($kind)['meta'] ?? []) as $id => $key) {
            $f = $fields[(string) $id] ?? null;
            if ($f === null) {
                continue;
            }
            $raw = get_post_meta($postId, (string) $key, true);
            $value = self::typed($f, $raw);
            if ($value === null) {
                continue;
            }
            $md[(string) $id] = !empty($f['sensitive']) ? ['value' => $value, 'private' => true] : ['value' => $value];
        }
        ksort($md, SORT_STRING);
        return $md;
    }

    /** Stored meta → Codex value of a manifest field; null = no value. */
    public static function typed(array $field, mixed $raw): mixed
    {
        if ($raw === '' || $raw === null || $raw === false && ($field['type'] ?? '') !== 'boolean') {
            return null;
        }
        $rules = array_map(static fn ($r): string => (string) ($r['name'] ?? ''), (array) ($field['rules'] ?? []));
        $int = in_array('fastygo.codex/integer', $rules, true) || in_array('fastygo.codex/money', $rules, true);
        return match ((string) ($field['type'] ?? 'string')) {
            'number' => is_numeric($raw)
                ? ($int ? (int) round((float) $raw) : (floor((float) $raw) === (float) $raw && abs((float) $raw) < 1e15
                    ? (int) $raw : (float) $raw))
                : null,
            'boolean' => in_array($raw, [true, 1, '1', 'true', 'on', 'yes'], true),
            default => is_scalar($raw) ? (string) $raw : null,
        };
    }

    /**
     * Media entry of an attachment, as @ui8kit/contract fromWpMedia (0851d9d): title, description → content,
     * metadata file / mime / size / width / height / alt / caption + the attachment reference (Profile).
     * Status inherit → published/public, private → published/private.
     *
     * @return array<string, mixed>
     */
    public function media(WP_Post $att): array
    {
        $meta = wp_get_attachment_metadata($att->ID);
        $meta = is_array($meta) ? $meta : [];
        $f = [
            'file' => (string) wp_get_attachment_url($att->ID),
            'mime' => (string) $att->post_mime_type,
            'size' => isset($meta['filesize']) ? (int) $meta['filesize'] : null,
            'width' => isset($meta['width']) ? (int) $meta['width'] : null,
            'height' => isset($meta['height']) ? (int) $meta['height'] : null,
            'alt' => (string) get_post_meta($att->ID, '_wp_attachment_image_alt', true),
            'caption' => (string) $att->post_excerpt,
        ];
        // contract 3a58400: attached_to = "<kind>-<n>" of the owner; post 0 / unknown kind -> no field
        if ((int) $att->post_parent > 0) {
            $type = get_post_type((int) $att->post_parent);
            $owner = is_string($type) ? $this->site->kindOf($type) : null;
            if ($owner !== null) {
                $f[Profile::MEDIA_ATTACHED_FIELD] = self::id($owner, (int) $att->post_parent);
            }
        }
        $md = [];
        foreach ($f as $k => $v) {
            if ($v !== null && $v !== '') {
                $md[$k] = ['value' => $v];
            }
        }
        $md = self::withRestMeta($md, 'post', 'attachment', $att->ID, "media {$att->ID}");
        $modified = self::gmt($att->post_modified_gmt);
        $dated = $att->post_date_gmt !== '0000-00-00 00:00:00' ? self::gmt($att->post_date_gmt) : $modified;
        $status = $att->post_status === 'inherit' ? 'published' : (Profile::STATUS[$att->post_status] ?? 'draft');
        $e = [
            'id' => self::id('media', $att->ID), 'locale' => self::locale(), 'kind' => 'media', 'status' => $status,
            'visibility' => $att->post_status === 'private' ? 'private' : 'public',
            'slug' => $att->post_name !== '' ? $att->post_name : self::id('media', $att->ID),
            'title' => (string) $att->post_title, 'content' => (string) $att->post_content,
        ];
        if ((int) $att->post_author > 0) {
            $e['author_id'] = self::id('user', (int) $att->post_author);
        }
        $e['discussion'] = ['comments' => $att->comment_status === 'open', 'pings' => $att->ping_status === 'open'];
        $e += [
            'origin' => [['system' => Profile::SYSTEM, 'id' => (string) $att->ID]], 'metadata' => $md, 'version' => 1,
            'created_at' => strcmp($dated, $modified) > 0 ? $modified : $dated, 'updated_at' => $modified,
        ];
        if ($status === 'published' || $status === 'scheduled') {
            $e['published_at'] = $dated;
        }
        return $e;
    }

    /**
     * User entry, as fromWpUser (0851d9d): display name → title, slug, description → content, url → metadata.url;
     * login and roles only private. Personal data (email, names, nickname), password, capabilities, avatars: never.
     *
     * @return array<string, mixed>
     */
    public function user(WP_User $u): array
    {
        $at = $u->user_registered !== '' && $u->user_registered !== '0000-00-00 00:00:00'
            ? self::gmt($u->user_registered) : self::EPOCH;
        $md = [];
        if ((string) $u->user_url !== '') {
            $md['url'] = ['value' => (string) $u->user_url];
        }
        $md['login'] = ['value' => (string) $u->user_login, 'private' => true];
        $md['roles'] = ['value' => array_values((array) $u->roles), 'private' => true];
        $md = self::withRestMeta($md, 'user', '', $u->ID, "user {$u->ID}");
        return [
            'id' => self::id('user', $u->ID), 'locale' => self::locale(), 'kind' => 'user', 'status' => 'published',
            'visibility' => 'public', 'slug' => (string) $u->user_nicename !== '' ? (string) $u->user_nicename
                : self::id('user', $u->ID),
            'title' => (string) $u->display_name, 'content' => (string) get_user_meta($u->ID, 'description', true),
            'origin' => [['system' => Profile::SYSTEM, 'id' => (string) $u->ID]], 'metadata' => $md,
            'version' => 1, 'created_at' => $at, 'updated_at' => $at,
        ];
    }

    /**
     * Adds the REST-registered meta of an object (show_in_rest) to adapter fields; a meta key equal to an adapter
     * field is an error, never an overwrite. `_` keys are private (contract metaIn).
     *
     * @param array<string, array<string, mixed>> $md
     * @return array<string, array<string, mixed>>
     */
    private static function withRestMeta(array $md, string $type, string $sub, int $id, string $what): array
    {
        foreach (get_registered_meta_keys($type, $sub) as $key => $args) {
            $key = (string) $key;
            if (empty($args['show_in_rest']) || in_array($key, Profile::DROPPED_META, true)) {
                continue;
            }
            if (isset($md[$key])) {
                throw new \LogicException("wordpress: meta key \"{$key}\" of {$what} collides with an adapter field");
            }
            $v = get_metadata($type, $id, $key, !empty($args['single']));
            if ($v === '' || $v === null || $v === []) {
                continue;
            }
            $md[$key] = str_starts_with($key, '_') ? ['value' => $v, 'private' => true] : ['value' => $v];
        }
        ksort($md, SORT_STRING);
        return $md;
    }

    /**
     * codex-term/v1 record. `count` is not part of the canonical term (nor of any digest) and the REST list does not
     * serve it; $withCount adds it as metadata.count for the builder's own record view.
     *
     * @return array<string, mixed>
     */
    public function term(WP_Term $t, bool $withCount = false): array
    {
        $created = (string) get_term_meta($t->term_id, self::TERM_CREATED, true);
        $updated = (string) get_term_meta($t->term_id, self::TERM_UPDATED, true);
        $created = $created !== '' ? $created : self::EPOCH;
        return [
            'id' => self::id($t->taxonomy, $t->term_id),
            'locale' => self::locale(),
            'taxonomy_id' => $t->taxonomy,
            'name' => html_entity_decode((string) $t->name, ENT_QUOTES, 'UTF-8'),
            'slug' => (string) $t->slug,
            'description' => (string) $t->description,
            'parent_id' => $t->parent > 0 ? self::id($t->taxonomy, (int) $t->parent) : '',
            'origin' => [['system' => Profile::SYSTEM, 'id' => (string) $t->term_id]],
            ...($withCount ? ['metadata' => ['count' => ['value' => (int) $t->count]]] : []),
            'version' => 1,
            'created_at' => $created,
            'updated_at' => $updated !== '' ? $updated : $created,
        ];
    }

    /** Taxonomy definition (TaxonomyDefinitionV04). */
    public function taxonomy(string $tax): array
    {
        $obj = get_taxonomy($tax);
        $kinds = [];
        foreach ($this->site->postKinds() as $kind) {
            if (in_array($tax, $this->site->taxonomies($kind), true)) {
                $kinds[] = $kind;
            }
        }
        return [
            'id' => $tax,
            'locale' => self::locale(),
            'label' => $obj ? html_entity_decode((string) $obj->labels->name, ENT_QUOTES, 'UTF-8') : $tax,
            'mode' => $obj && $obj->hierarchical ? 'hierarchical' : 'flat',
            'assigned_to_kinds' => $kinds,
            'public' => $obj ? (bool) $obj->public : false,
            'version' => 1,
        ];
    }

    /** Site settings (SettingsV04), as the contract adapter fromWpSettings maps them. */
    public function settings(): array
    {
        $s = [
            'id' => self::SETTINGS_ID, 'locale' => self::locale(), 'title' => (string) get_option('blogname'),
            'description' => (string) get_option('blogdescription'),
            'language' => self::language() ?? Profile::SETTINGS_LANGUAGE,
            'timezone' => (string) get_option('timezone_string'),
        ];
        if (get_option('show_on_front') === 'page' && (int) get_option('page_on_front') > 0) {
            $s['front_page_id'] = self::id('page', (int) get_option('page_on_front'));
        }
        if ((int) get_option('page_for_posts') > 0) {
            $s['posts_page_id'] = self::id('page', (int) get_option('page_for_posts'));
        }
        $s['posts_per_page'] = max(1, (int) get_option('posts_per_page'));
        $s['comments_open'] = get_option('default_comment_status') === 'open';
        $cur = strtoupper((string) get_option($this->site->currencyOption())); // registered default applies
        if (preg_match('/^[A-Z]{3}$/', $cur) === 1) {
            $s['currency'] = $cur;
        }
        $s['origin'] = [['system' => Profile::SYSTEM, 'id' => 'settings']];
        $s['version'] = 1;
        $s['created_at'] = self::EPOCH;
        $s['updated_at'] = self::EPOCH;
        return $s;
    }

    /** created/updated term meta, clock filterable (demo seeding pins it for golden files). */
    public static function stampTerm(int $termId, bool $created): void
    {
        $now = gmdate('Y-m-d\TH:i:s\Z', (int) apply_filters('wpfasty_codex_now', time()));
        if ($created) {
            update_term_meta($termId, self::TERM_CREATED, $now);
        }
        update_term_meta($termId, self::TERM_UPDATED, $now);
    }
}
