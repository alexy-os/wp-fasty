<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Html\TrustedHtml;
use WPFasty\Core\Model\Author;
use WPFasty\Core\Model\Image;
use WPFasty\Core\Model\Post;
use WPFasty\Core\Model\PostLink;
use WPFasty\Core\Model\Term;

/** WP_Post / WP_Query -> Core Post DTOs, with caches primed per list. */
final class PostRepository implements WordPressPostRepositoryInterface
{
    public function find(int $id, bool $singular = false): ?Post
    {
        $post = get_post($id);

        return $post instanceof \WP_Post ? $this->fromWordPress($post, $singular) : null;
    }

    public function recent(int $count): array
    {
        $query = new \WP_Query([
            'posts_per_page' => $count,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            // meta and terms are batch-loaded by prime() below together with thumbnails and authors
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
        $list = array_values(array_filter(
            is_array($query->posts) ? $query->posts : [],
            static fn ($p): bool => $p instanceof \WP_Post,
        ));
        // one query per kind (meta, terms, featured images, authors) for the whole list instead of N+1
        $this->prime($list);
        $posts = [];
        foreach ($list as $p) {
            $posts[] = $this->fromWordPress($p);
        }
        wp_reset_postdata();

        return $posts;
    }

    public function mainQuery(): array
    {
        $posts = [];
        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $this->prime(array_values(array_filter(is_array($wp_query->posts) ? $wp_query->posts : [], static fn ($p): bool => $p instanceof \WP_Post)));
        }
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $p = get_post();
                if ($p instanceof \WP_Post) {
                    $posts[] = $this->fromWordPress($p);
                }
            }
            wp_reset_postdata();
        }

        return $posts;
    }

    public function prime(array $posts): void
    {
        $ids = [];
        $byType = [];
        $authors = [];
        foreach ($posts as $post) {
            if ($post instanceof \WP_Post) {
                $ids[] = $post->ID;
                $byType[$post->post_type][] = $post->ID;
                $authors[] = (int) $post->post_author;
            }
        }
        if ([] === $ids) {
            return;
        }
        update_meta_cache('post', $ids);
        foreach ($byType as $type => $typeIds) {
            update_object_term_cache($typeIds, $type);
        }
        $thumbs = [];
        foreach ($ids as $id) {
            $thumb = (int) get_post_meta($id, '_thumbnail_id', true);
            if ($thumb > 0) {
                $thumbs[] = $thumb;
            }
        }
        if ([] !== $thumbs) {
            _prime_post_caches(array_values(array_unique($thumbs)), false, true);
        }
        if (function_exists('cache_users')) {
            cache_users(array_values(array_unique(array_filter($authors))));
        }
    }

    public function fromWordPress(\WP_Post $post, bool $singular = false): Post
    {
        $isAttachment = 'attachment' === $post->post_type;
        // a password-protected post shows the password form (and no excerpt) until the visitor has entered it,
        // like the_content() / get_the_excerpt() of core templates
        $locked = post_password_required($post);

        $featured = null;
        $thumbnail = null;
        if (has_post_thumbnail($post)) {
            $id = (int) get_post_thumbnail_id($post);
            $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
            $featured = $this->image($id, 'large', $alt);
            $thumbnail = $this->image($id, 'medium', $alt);
        }
        if ($isAttachment) {
            $alt = (string) get_post_meta($post->ID, '_wp_attachment_image_alt', true);
            $featured = $this->image($post->ID, 'large', $alt) ?? $featured;
        }

        $categories = [];
        foreach ((is_object_in_taxonomy($post->post_type, 'category') ? get_the_category($post->ID) : []) ?: [] as $cat) {
            $categories[] = new Term('category', (string) $cat->name, (string) $cat->slug, (string) get_category_link($cat->term_id));
        }
        $tags = [];
        $wpTags = is_object_in_taxonomy($post->post_type, 'post_tag') ? get_the_tags($post->ID) : false;
        foreach (is_array($wpTags) ? $wpTags : [] as $tag) {
            $tags[] = new Term('post_tag', (string) $tag->name, (string) $tag->slug, (string) get_tag_link($tag->term_id));
        }

        $authorId = (int) $post->post_author;
        $author = $authorId > 0 ? new Author(
            $authorId,
            (string) get_the_author_meta('display_name', $authorId),
            (string) get_author_posts_url($authorId),
        ) : null;

        $linkPages = null;
        $previous = null;
        $next = null;
        if ($singular && !$isAttachment) {
            $linkPages = new TrustedHtml((string) (wp_link_pages([
                'before' => '<nav class="post-pages mt-8 text-sm text-muted-foreground" aria-label="'
                    .esc_attr__('Pages', 'latte').'"><span class="mr-2">'
                    .esc_html__('Pages:', 'latte').'</span>',
                'after' => '</nav>',
                'echo' => false,
            ]) ?: ''));
            if ('post' === $post->post_type) {
                $previous = $this->link(get_adjacent_post(false, '', true));
                $next = $this->link(get_adjacent_post(false, '', false));
            }
        }

        return new Post(
            id: $post->ID,
            kind: $post->post_type,
            slug: $post->post_name,
            title: PlainText::from(get_the_title($post)),
            content: new TrustedHtml((string) ($locked
                ? get_the_password_form($post)
                : apply_filters('the_content', $post->post_content))),
            excerpt: PlainText::from(has_excerpt($post) || $locked
                ? get_the_excerpt($post)
                : wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 40)),
            url: (string) get_permalink($post),
            dateDisplay: (string) get_the_date('', $post),
            dateIso: (string) get_the_date('c', $post),
            featuredImage: $featured,
            thumbnail: $thumbnail,
            categories: $categories,
            tags: $tags,
            author: $author,
            cssClass: implode(' ', get_post_class('', $post)),
            linkPages: $linkPages,
            previous: $previous,
            next: $next,
            mimeType: $post->post_mime_type,
            attachmentUrl: $isAttachment ? (string) wp_get_attachment_url($post->ID) : null,
            singular: $singular,
        );
    }

    private function image(int $id, string $size, string $alt): ?Image
    {
        $src = wp_get_attachment_image_src($id, $size);
        if (!$src) {
            return null;
        }

        return new Image(
            (string) $src[0],
            (string) $src[1],
            (string) $src[2],
            $alt,
            (string) (wp_get_attachment_image_srcset($id, $size) ?: ''),
            (string) (wp_get_attachment_image_sizes($id, $size) ?: ''),
        );
    }

    private function link(mixed $post): ?PostLink
    {
        return $post instanceof \WP_Post
            ? new PostLink(PlainText::from(get_the_title($post)), (string) get_permalink($post))
            : null;
    }
}
