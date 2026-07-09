<?php

declare(strict_types=1);

namespace WPFasty\Data;

/**
 * Builds typed plain-array context for Latte — no WP calls in views.
 */
final class ContextFactory
{
    /** @return array<string, mixed> */
    public function createPageContext(?\WP_Post $post = null): array
    {
        $post = $post ?? get_post();
        $context = array_merge($this->common(), [
            'page' => $post instanceof \WP_Post ? $this->post($post) : null,
        ]);

        if (is_front_page()) {
            $context['posts'] = $this->recentPosts(6);
        }

        return apply_filters('wpfasty_context', $context, $post);
    }

    /** @return array<string, mixed> */
    public function createArchiveContext(): array
    {
        $posts = [];
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $p = get_post();
                if ($p instanceof \WP_Post) {
                    $posts[] = $this->post($p);
                }
            }
            wp_reset_postdata();
        }

        $context = array_merge($this->common(), [
            'archive' => [
                'title' => wp_strip_all_tags(get_the_archive_title()),
                'description' => get_the_archive_description(),
            ],
            'posts' => $posts,
            'pagination' => $this->pagination(),
        ]);

        return apply_filters('wpfasty_context', $context, null);
    }

    /** @return array{site: array<string, mixed>, menu: array<string, mixed>} */
    private function common(): array
    {
        return [
            'site' => [
                'title' => get_bloginfo('name'),
                'url' => home_url('/'),
                'theme_url' => get_template_directory_uri(),
                'lang' => get_bloginfo('language'),
                'description' => get_bloginfo('description'),
                'charset' => get_bloginfo('charset'),
                'year' => gmdate('Y'),
            ],
            'menu' => $this->menus(),
        ];
    }

    /** @return array<string, mixed> */
    private function post(\WP_Post $post): array
    {
        $data = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'content' => apply_filters('the_content', $post->post_content),
            'slug' => $post->post_name,
            'url' => (string) get_permalink($post),
            'excerpt' => has_excerpt($post)
                ? get_the_excerpt($post)
                : wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 40),
            'featuredImage' => null,
            'thumbnail' => null,
            'categories' => [],
            'date' => [
                'display' => get_the_date('', $post),
                'iso' => get_the_date('c', $post),
            ],
        ];

        if (has_post_thumbnail($post)) {
            $id = (int) get_post_thumbnail_id($post);
            $full = wp_get_attachment_image_src($id, 'large');
            $thumb = wp_get_attachment_image_src($id, 'medium');
            $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
            if ($full) {
                $data['featuredImage'] = [
                    'url' => $full[0],
                    'width' => $full[1],
                    'height' => $full[2],
                    'alt' => $alt,
                ];
            }
            if ($thumb) {
                $data['thumbnail'] = [
                    'url' => $thumb[0],
                    'width' => $thumb[1],
                    'height' => $thumb[2],
                    'alt' => $alt,
                ];
            }
        }

        foreach (get_the_category($post->ID) ?: [] as $cat) {
            $data['categories'][] = [
                'name' => $cat->name,
                'url' => get_category_link($cat->term_id),
                'slug' => $cat->slug,
            ];
        }

        return $data;
    }

    /** @return array<string, array{items: list<array<string, mixed>>}> */
    private function menus(): array
    {
        $out = ['primary' => ['items' => []]];
        $locations = get_nav_menu_locations();
        if ($locations === []) {
            return $out;
        }

        foreach ($locations as $location => $menuId) {
            $items = wp_get_nav_menu_items($menuId);
            $list = [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $list[] = [
                        'title' => $item->title,
                        'url' => $item->url,
                        'id' => (int) $item->ID,
                        'current' => (bool) $item->current,
                    ];
                }
            }
            $out[$location] = ['items' => $list];
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function recentPosts(int $count): array
    {
        $query = new \WP_Query([
            'posts_per_page' => $count,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
        ]);
        $posts = [];
        foreach ($query->posts as $p) {
            if ($p instanceof \WP_Post) {
                $posts[] = $this->post($p);
            }
        }
        wp_reset_postdata();
        return $posts;
    }

    /** @return array<string, mixed>|null */
    private function pagination(): ?array
    {
        global $wp_query;
        $max = (int) $wp_query->max_num_pages;
        if ($max <= 1) {
            return null;
        }
        $paged = max(1, (int) get_query_var('paged'));
        return [
            'current' => $paged,
            'total' => $max,
            'prev_url' => $paged > 1 ? get_pagenum_link($paged - 1) : null,
            'next_url' => $paged < $max ? get_pagenum_link($paged + 1) : null,
        ];
    }
}
