<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Creates a page from the Builder (POST wpfasty-builder/v1/pages): a draft page with the title and slug of the "New
 * page" panel; with a starter its blocks become the page's Builder draft (Store::save, not published). The WordPress
 * page template is left alone (no `page_template`): a starter is only blocks. The caller checks edit_pages.
 */
final class Pages
{
    /** "empty": no blocks */
    public const EMPTY = 'empty';

    /**
     * @param (callable(int, array<string, mixed>, bool): (array<int, mixed>|\WP_Error))|null $save Store::save
     * @return array{id: int, slug: string, url: string, blocks: int}|\WP_Error
     */
    public static function create(string $title, string $slug, string $starter, ?callable $save = null): array|\WP_Error
    {
        $title = trim(sanitize_text_field($title));
        if ($title === '') {
            return new \WP_Error('wpfb_title', __('Enter a title for the page.', 'wpfasty-builder'), ['status' => 400]);
        }
        $layout = null;
        if ($starter !== '' && $starter !== self::EMPTY) {
            $s = Starters::get($starter);
            if ($s === null) {
                return new \WP_Error(
                    'wpfb_starter',
                    __('This starter set is not available on this site.', 'wpfasty-builder'),
                    ['status' => 400],
                );
            }
            $layout = $s['layout'];
        }
        $slug = sanitize_title($slug !== '' ? $slug : $title);
        // a draft keeps any slug until it is published; reserve a free one now, so the URL shown is the URL it gets
        if ($slug !== '') {
            $slug = wp_unique_post_slug($slug, 0, 'publish', 'page', 0);
        }
        $id = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_title' => $title,
            'post_name' => $slug,
            'post_content' => '',
        ], true);
        if ($id instanceof \WP_Error) {
            return $id;
        }
        $id = (int) $id;
        $blocks = 0;
        if ($layout !== null) {
            $result = ($save ?? [Store::class, 'save'])($id, $layout, false);
            if ($result instanceof \WP_Error) {
                wp_delete_post($id, true);
                return $result;
            }
            $blocks = count(Layout::blocks($result[0]));
        }
        return ['id' => $id, 'slug' => $slug, 'url' => Admin::url($id), 'blocks' => $blocks];
    }
}
