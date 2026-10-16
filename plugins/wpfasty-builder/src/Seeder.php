<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Publishes a landing from a spec: { page: {title, slug}, front: bool, layout: {source, collection, blocks} }.
 * Used by tools/builder/seed-landing.php (WP-CLI) and the Playground blueprints. The page is created when no page
 * has the slug; with front=true it becomes the static front page (and a "blog" page takes the posts list when the
 * site showed posts on the front).
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
}
