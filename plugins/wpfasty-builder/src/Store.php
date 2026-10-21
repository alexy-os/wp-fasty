<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Where layouts live: the draft and the published entry in post meta (JSON), the compiled templates in
 * uploads/wpfasty-builder/views/builder/{page,preview}-<id>.latte or .html.twig — the site engine's runtime only
 * (Plugin::engine()). A missing template is compiled on demand, so a database copy (or a WordPress Playground import)
 * renders without a save.
 */
final class Store
{
    public const DRAFT = '_wpfb_layout_draft';
    public const PUBLISHED = '_wpfb_layout';

    public static function dir(): string
    {
        $up = wp_upload_dir(null, false);
        return rtrim((string) $up['basedir'], '/') . '/wpfasty-builder';
    }

    public static function viewsDir(): string
    {
        return self::dir() . '/views';
    }

    /** @return array<string, mixed>|null */
    public static function get(int $pageId, bool $draft): ?array
    {
        $raw = (string) get_post_meta($pageId, $draft ? self::DRAFT : self::PUBLISHED, true);
        if ($raw === '' && $draft) {
            $raw = (string) get_post_meta($pageId, self::PUBLISHED, true);
        }
        $entry = $raw !== '' ? json_decode($raw, true) : null;
        return is_array($entry) ? $entry : null;
    }

    /** @param array<string, mixed> $entry */
    public static function put(int $pageId, array $entry, bool $draft): void
    {
        $json = (string) wp_json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        update_post_meta($pageId, $draft ? self::DRAFT : self::PUBLISHED, wp_slash($json));
    }

    public static function has(int $pageId): bool
    {
        return get_post_meta($pageId, self::PUBLISHED, true) !== '';
    }

    public static function view(int $pageId, bool $preview): string
    {
        return 'builder/' . ($preview ? 'preview' : 'page') . '-' . $pageId;
    }

    /**
     * Writes the template of a layout when it is missing or stale; returns the view name, or null.
     *
     * @param array<string, mixed> $entry
     */
    public static function compile(int $pageId, array $entry, bool $preview): ?string
    {
        $view = self::view($pageId, $preview);
        $catalog = null;
        $engine = Plugin::engine();
        if ($engine === null) {
            return null;
        }
        // The site engine only (ADR-001: isolated runtimes; the engine is chosen at site creation).
        foreach ([$engine => Compiler::ENGINES[$engine]] as $engine => $ext) {
            $file = self::viewsDir() . '/' . $view . $ext;
            $marker = Compiler::marker($entry, $engine);
            if (is_file($file) && str_starts_with((string) file_get_contents($file, false, null, 0, 64), $marker)) {
                continue;
            }
            $catalog ??= Sources::catalog((string) ($entry['metadata']['source']['value'] ?? ''));
            if ($catalog === null) {
                return null;
            }
            $tpl = Compiler::compile($entry, $catalog, $engine);
            if (!is_dir(dirname($file)) && !wp_mkdir_p(dirname($file))) {
                return null;
            }
            file_put_contents($file, $tpl);
        }
        return $view;
    }

    /**
     * Validates and saves a layout payload as the draft; with $publish also as the published layout. The caller
     * checks capabilities (edit_page, publish_pages).
     *
     * @param array<string, mixed> $layout
     * @return array{0: array<string, mixed>, 1: list<array{code: string, path: string, message: string}>}|\WP_Error
     */
    public static function save(int $id, array $layout, bool $publish): array|\WP_Error
    {
        $sourceId = sanitize_key((string) ($layout['source'] ?? ($layout['metadata']['source']['value'] ?? 'ui8kit')));
        $catalog = Sources::catalog($sourceId);
        if ($catalog === null) {
            return new \WP_Error('wpfb_source', __('Unknown source.', 'wpfasty-builder'), ['status' => 400]);
        }
        $prev = self::get($id, true);
        $now = gmdate('Y-m-d\\TH:i:s\\Z');
        $post = get_post($id);
        [$entry, $issues] = Layout::normalize($layout, $sourceId, $catalog, array_keys(DataSources::all()), [
            'id' => 'layout-' . $id,
            'locale' => self::locale(),
            'slug' => $post !== null && $post->post_name !== '' ? $post->post_name : 'page-' . $id,
            'title' => $post !== null ? $post->post_title : '',
            'status' => $publish ? 'published' : 'draft',
            'version' => (int) ($prev['version'] ?? 0) + 1,
            'created_at' => (string) ($prev['created_at'] ?? $now),
            'updated_at' => $now,
            'page_id' => $id,
        ]);
        $fatal = array_values(array_filter(
            $issues,
            static fn (array $i): bool => str_starts_with($i['code'], 'core.'),
        ));
        if ($fatal !== []) {
            return new \WP_Error(
                'wpfb_invalid',
                __('The layout is not a valid entry.', 'wpfasty-builder'),
                ['status' => 400, 'issues' => $fatal],
            );
        }
        self::put($id, $entry, true);
        self::compile($id, $entry, true);
        if ($publish) {
            self::put($id, $entry, false);
            self::compile($id, $entry, false);
        }
        return [$entry, $issues];
    }

    public static function locale(): string
    {
        $l = strtolower(str_replace('_', '-', (string) get_locale()));
        return preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})*$/', $l) === 1 ? $l : 'en';
    }
}
