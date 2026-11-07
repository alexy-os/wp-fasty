<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/** Stable manual Builder page port. Does not know Chat, ModulY, credentials or Node. */
final class PageOperations
{
    public const API = 1;

    public static function compatible(int $api): bool
    {
        return self::API === $api;
    }

    /** Authoritative installed catalog and saved draft; no policy or Agent-specific fields.
     * @return array<string, mixed>|\WP_Error
     */
    public static function snapshot(int $page): array|\WP_Error
    {
        if (!current_user_can('edit_post', $page) || 'page' !== get_post_type($page)) {
            return new \WP_Error('wpfb_forbidden', 'Not allowed.', ['status' => 403]);
        }
        $entry = Store::get($page, true) ?? [];
        $source = Cast::string(Cast::path($entry, 'metadata', 'source', 'value') ?? 'ui8kit');
        $catalog = Sources::catalog($source);
        if (null === $catalog) {
            return new \WP_Error('wpfb_source', 'Unknown catalog source.', ['status' => 400]);
        }
        $defs = [];
        foreach (($catalog->forEditor(null)['blocks'] ?? []) as $block) {
            $defs[] = ['id' => $block['id'], 'title' => $block['title'] ?? '', 'docs' => $block['docs'] ?? '', 'props' => $block['props'] ?? [], 'defaults' => $block['defaults'] ?? [], 'data' => $block['data'] ?? [], 'variants' => array_map(static fn (mixed $variant): array => ['id' => Cast::path($variant, 'id'), 'template' => Cast::path($variant, 'template'), 'set' => Cast::path($variant, 'set') ?? []], Cast::list($block['variants'] ?? []))];
        }
        if ([] === $defs) {
            return new \WP_Error('wpfb_catalog_unavailable', 'Catalog definition unavailable.', ['status' => 503]);
        }

        return ['source' => $source, 'target' => $catalog->runtime(), 'version' => Cast::int($entry['version'] ?? 0), 'records' => $catalog->meta()['records'] ?? [], 'catalog' => $defs, 'blocks' => Cast::path($entry, 'metadata', 'blocks', 'value') ?? []];
    }

    /** Caller must hold DraftGuard::locked for revision check + write + its own idempotency record.
     * @param array<mixed> $blocks
     *
     * @return array{0: array<string, mixed>, 1: list<array{code: string, path: string, message: string}>}|\WP_Error
     */
    public static function replaceLocked(int $page, string $source, array $blocks, int $expected): array|\WP_Error
    {
        if (!current_user_can('edit_post', $page) || 'page' !== get_post_type($page)) {
            return new \WP_Error('wpfb_forbidden', 'Not allowed.', ['status' => 403]);
        }
        if (DraftGuard::version($page) !== $expected) {
            return new \WP_Error('wpfb_revision_conflict', 'Draft changed. Reload before saving.', ['status' => 409]);
        }
        $catalog = Sources::catalog($source);
        if (null === $catalog || count($blocks) > 100) {
            return new \WP_Error('wpfb_layout_schema', 'Invalid layout.', ['status' => 400]);
        }
        $ids = [];
        foreach ($blocks as $block) {
            if (!is_array($block) || !is_string($block['id'] ?? null) || !preg_match(Catalog::IDENT, $block['id']) || isset($ids[$block['id']]) || !is_array($block['props'] ?? null) || !is_array($block['bindings'] ?? null)) {
                return new \WP_Error('wpfb_layout_schema', 'Invalid structural block.', ['status' => 400]);
            }
            $ids[$block['id']] = true;
            if (null === $catalog->variant(Cast::string($block['block'] ?? ''), Cast::string($block['variant'] ?? ''))) {
                return new \WP_Error('wpfb_layout_invalid_catalog', 'Layout references unknown catalog block/variant.', ['status' => 400]);
            }
        }
        $previous = Store::get($page, true) ?? [];
        $layout = ['source' => $source, 'blocks' => $blocks, 'collection' => Cast::path($previous, 'metadata', 'collection', 'value') ?? ''];
        $post = get_post($page);
        if (!$post instanceof \WP_Post) {
            return new \WP_Error('wpfb_layout_missing', 'Page no longer exists.', ['status' => 404]);
        }
        $base = $previous + ['id' => 'layout-'.$page, 'locale' => 'en', 'slug' => $post->post_name ?: 'page-'.$page, 'title' => $post->post_title, 'status' => 'draft', 'version' => 1, 'created_at' => gmdate('c'), 'updated_at' => gmdate('c')];
        $base['page_id'] = $page;
        [, $issues] = Layout::normalize($layout, $source, $catalog, array_keys(DataSources::all()), $base);
        if ([] !== $issues) {
            return new \WP_Error('wpfb_layout_schema', 'Layout failed Builder schema validation.', ['status' => 400, 'issues' => $issues]);
        }
        $saved = Store::save($page, $layout, false);
        if (is_wp_error($saved)) {
            return $saved;
        }

        return $saved;
    }
}
