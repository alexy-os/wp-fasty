<?php

declare(strict_types=1);

namespace WPFastyTypes\Codex;

/**
 * Codex draft v0.4 profile constants: the one place to change when a contract decision changes
 * (@ui8kit/contract docs/canonical.md, profile codex-entry/v1; HTTP form agreed with Ops Engineer / XGenBot).
 */
final class Profile
{
    public const CODEX_VERSION = '0.4.0';
    public const ENTRY_PROFILE = 'codex-entry/v1';
    public const TERM_PROFILE = 'codex-term/v1';
    public const MANIFEST_DIGEST = 'codex-manifest/v2:sha256:';
    public const REST_NS = 'wpfasty/v1';
    public const REST_BASE = '/codex';

    /** Q8: stable record id; WordPress default `<kind>-<post ID>`. */
    public const ID_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$/';
    public const SYSTEM = 'wordpress';
    public const DEFAULT_LOCALE = 'en';
    public const MAX_SAFE = 9007199254740991;
    public const PER_PAGE = 20;
    public const MAX_PER_PAGE = 100;

    /** KEYS_V04 / REQUIRED_V04 of @ui8kit/contract src/validate-v04.ts, src/v04.ts. */
    public const ENTRY_KEYS = [
        'id', 'locale', 'kind', 'status', 'visibility', 'slug', 'title', 'content', 'excerpt', 'author_id',
        'parent_id', 'featured_media_id', 'template', 'terms', 'order', 'pinned', 'format', 'discussion', 'origin',
        'metadata', 'version', 'created_at', 'updated_at', 'published_at', 'deleted_at',
    ];
    public const ENTRY_REQUIRED = [
        'id', 'locale', 'kind', 'status', 'visibility', 'slug', 'title', 'version', 'created_at', 'updated_at',
    ];
    public const TERM_KEYS = [
        'id', 'locale', 'taxonomy_id', 'name', 'slug', 'description', 'parent_id', 'origin', 'metadata', 'version',
        'created_at', 'updated_at',
    ];
    public const TERM_REQUIRED = ['id', 'locale', 'taxonomy_id', 'name', 'slug', 'version', 'created_at', 'updated_at'];
    public const DATE_FIELDS = ['created_at', 'updated_at', 'published_at', 'deleted_at'];

    /**
     * Where a media entry says what it is attached to (proposal to XGenBot, not yet agreed): a reference field
     * `metadata.attached_to = "<kind>-<n>"` (owner kind from get_post_type(post_parent)), not parent_id.
     * The contract adapter fromWpMedia uses `metadata.post` = WP integer id; change it here only.
     */
    public const MEDIA_ATTACHED_FIELD = 'attached_to';

    /** WordPress post status → Codex status (same table as the contract WordPress adapter). */
    public const STATUS = [
        'publish' => 'published', 'future' => 'scheduled', 'draft' => 'draft', 'pending' => 'pending',
        'private' => 'published', 'trash' => 'trashed',
    ];
}
