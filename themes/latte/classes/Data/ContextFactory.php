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
            'hero' => $this->hero(),
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

        $searchQuery = is_search() ? (string) get_search_query() : null;
        $title = $searchQuery !== null
            ? sprintf(
                /* translators: %s: search query */
                __('Search results for “%s”', 'latte'),
                $searchQuery
            )
            : wp_strip_all_tags(get_the_archive_title());

        $context = array_merge($this->common(), [
            'archive' => [
                'title' => $title,
                'description' => $searchQuery !== null ? '' : get_the_archive_description(),
                'search_query' => $searchQuery,
            ],
            'posts' => $posts,
            'pagination' => $this->pagination(),
            'labels' => $this->labels(),
        ]);

        return apply_filters('wpfasty_context', $context, null);
    }

    /** @return array<string, mixed> */
    public function createErrorContext(int $code = 404): array
    {
        $context = array_merge($this->common(), [
            'error' => [
                'code' => $code,
                'title' => __('Page not found', 'latte'),
                'message' => __('The page you are looking for could not be found.', 'latte'),
                'home_url' => home_url('/'),
                'home_label' => __('Back to home', 'latte'),
            ],
        ]);

        return apply_filters('wpfasty_context', $context, null);
    }

    /**
     * @return array{
     *   site: array<string, mixed>,
     *   menu: array<string, mixed>,
     *   shell: string,
     *   labels: array<string, string>
     * }
     */
    private function common(): array
    {
        $shell = (string) apply_filters('wpfasty_shell', 'classic');

        return [
            'site' => [
                'title' => get_bloginfo('name'),
                'url' => home_url('/'),
                'theme_url' => get_template_directory_uri(),
                'stylesheet_url' => get_stylesheet_directory_uri(),
                'lang' => get_bloginfo('language'),
                'description' => get_bloginfo('description'),
                'charset' => get_bloginfo('charset'),
                'year' => gmdate('Y'),
                'logo' => $this->logo(),
            ],
            'menu' => $this->menus(),
            'shell' => $shell === 'app-shell' ? 'app-shell' : 'classic',
            'labels' => $this->labels(),
        ];
    }

    /** @return array<string, string> */
    private function labels(): array
    {
        return [
            'featured' => __('Featured', 'latte'),
            'latest_articles' => __('Latest articles', 'latte'),
            'no_posts' => __('No posts found.', 'latte'),
            'previous' => __('Previous', 'latte'),
            'next' => __('Next', 'latte'),
            'skip_to_content' => __('Skip to content', 'latte'),
            'open_menu' => __('Open menu', 'latte'),
            'close_menu' => __('Close menu', 'latte'),
            'primary_nav' => __('Primary', 'latte'),
        ];
    }

    /** @return array<string, string> */
    private function hero(): array
    {
        $postsPageId = (int) get_option('page_for_posts');
        $blogUrl = $postsPageId > 0 ? (string) get_permalink($postsPageId) : home_url('/');

        return [
            'primary_label' => __('Explore', 'latte'),
            'primary_url' => $blogUrl,
            'secondary_label' => __('Learn more', 'latte'),
            'secondary_url' => home_url('/'),
        ];
    }

    /** @return array<string, mixed>|null */
    private function logo(): ?array
    {
        if (!function_exists('has_custom_logo') || !has_custom_logo()) {
            return null;
        }
        $id = (int) get_theme_mod('custom_logo');
        if ($id <= 0) {
            return null;
        }
        $src = wp_get_attachment_image_src($id, 'full');
        if (!$src) {
            return null;
        }
        return [
            'url' => $src[0],
            'width' => $src[1],
            'height' => $src[2],
            'alt' => (string) get_post_meta($id, '_wp_attachment_image_alt', true),
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
            $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
            $data['featuredImage'] = $this->image($id, 'large', $alt);
            $data['thumbnail'] = $this->image($id, 'medium', $alt);
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

    /** @return array<string, mixed>|null */
    private function image(int $id, string $size, string $alt): ?array
    {
        $src = wp_get_attachment_image_src($id, $size);
        if (!$src) {
            return null;
        }

        return [
            'url' => $src[0],
            'width' => $src[1],
            'height' => $src[2],
            'alt' => $alt,
            'srcset' => (string) (wp_get_attachment_image_srcset($id, $size) ?: ''),
            'sizes' => (string) (wp_get_attachment_image_sizes($id, $size) ?: ''),
        ];
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
        if (!isset($wp_query) || !is_object($wp_query)) {
            return null;
        }
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
