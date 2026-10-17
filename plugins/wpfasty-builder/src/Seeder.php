<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Publishes a landing from a spec:
 * { page: {title, slug}, front: bool, menu?: {location, label?}, layout: {source, collection, blocks} }.
 * Used by tools/builder/seed-landing.php (WP-CLI) and the Playground blueprints. The page is created when no page
 * has the slug; with front=true it becomes the static front page (and a "blog" page takes the posts list when the
 * site showed posts on the front). With menu, the page is appended to the nav menu assigned to that location
 * (once; nothing happens when the location has no menu).
 */
final class Seeder
{
    /**
     * @param array<string, mixed> $spec
     * @return array{page: int, version: int, blocks: int, issues: list<array<string, string>>}|\WP_Error
     */
    public static function landing(array $spec): array|\WP_Error
    {
        $page = is_array($spec['page'] ?? null) ? $spec['page'] : [];
        $slug = sanitize_title((string) ($page['slug'] ?? 'home'));
        $existing = get_page_by_path($slug);
        $id = $existing instanceof \WP_Post ? $existing->ID : wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => sanitize_text_field((string) ($page['title'] ?? 'Home')),
            'post_name' => $slug,
            'post_content' => '',
        ], true);
        if ($id instanceof \WP_Error) {
            return $id;
        }
        if (!empty($spec['front'])) {
            if ((int) get_option('page_for_posts') === 0 && get_option('show_on_front') === 'posts') {
                $blog = get_page_by_path('blog');
                $blogId = $blog instanceof \WP_Post ? $blog->ID : wp_insert_post([
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'post_title' => __('Blog', 'wpfasty-builder'),
                    'post_name' => 'blog',
                ]);
                update_option('page_for_posts', (int) $blogId);
            }
            update_option('show_on_front', 'page');
            update_option('page_on_front', (int) $id);
        }
        if (is_array($spec['menu'] ?? null)) {
            self::menu((int) $id, $spec['menu']);
        }
        $result = Store::save((int) $id, is_array($spec['layout'] ?? null) ? $spec['layout'] : [], true);
        if ($result instanceof \WP_Error) {
            return $result;
        }
        [$entry, $issues] = $result;
        return [
            'page' => (int) $id,
            'version' => (int) $entry['version'],
            'blocks' => count(Layout::blocks($entry)),
            'issues' => $issues,
        ];
    }

    /** @param array<string, mixed> $menu */
    public static function menu(int $page, array $menu): void
    {
        $locations = get_nav_menu_locations();
        $menuId = (int) ($locations[sanitize_key((string) ($menu['location'] ?? 'primary'))] ?? 0);
        if ($menuId === 0) {
            return;
        }
        $items = wp_get_nav_menu_items($menuId);
        foreach (is_array($items) ? $items : [] as $item) {
            if ($item->object === 'page' && (int) $item->object_id === $page) {
                return;
            }
        }
        wp_update_nav_menu_item($menuId, 0, [
            'menu-item-object-id' => $page,
            'menu-item-object' => 'page',
            'menu-item-type' => 'post_type',
            'menu-item-status' => 'publish',
            'menu-item-title' => sanitize_text_field((string) ($menu['label'] ?? get_the_title($page))),
        ]);
    }
}
