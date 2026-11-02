<?php

declare(strict_types=1);

namespace WPFasty\Data;

use WPFasty\Adapter\WordPress\WordPressArchiveStateProvider;
use WPFasty\Adapter\WordPress\WordPressCommentsRenderer;
use WPFasty\Adapter\WordPress\WordPressLabelProvider;
use WPFasty\Adapter\WordPress\WordPressMenuProvider;
use WPFasty\Adapter\WordPress\WordPressSiteInfoProvider;
use WPFasty\Adapter\WordPress\WordPressWidgetAreaProvider;
use WPFasty\Core\Model\SiteInfo;
use WPFasty\Core\Port\ArchiveStateProviderInterface;
use WPFasty\Core\Port\CommentsRendererInterface;
use WPFasty\Core\Port\LabelProviderInterface;
use WPFasty\Core\Port\MenuProviderInterface;
use WPFasty\Core\Port\PageLinkProviderInterface;
use WPFasty\Core\Port\SiteInfoProviderInterface;
use WPFasty\Core\Port\WidgetAreaProviderInterface;
use WPFasty\Data\Archive\ArchiveViewModelBuilder;
use WPFasty\Data\Menu\MenuViewModelBuilder;
use WPFasty\Data\Site\SiteViewModelBuilder;

/**
 * Facade of the template context: reads the WordPress request, asks the ports (Core\Port, WordPress adapters) for
 * data and hands it to the ViewModel builders (WPFasty\Data\*). No WP calls in views.
 * Dependencies come through the constructor (configs/services.php); the defaults keep `new ContextFactory()` working.
 */
final class ContextFactory
{
    private readonly MenuProviderInterface $menuProvider;
    private readonly MenuViewModelBuilder $menuBuilder;
    private readonly SiteInfoProviderInterface $siteInfo;
    private readonly LabelProviderInterface $labels;
    private readonly WidgetAreaProviderInterface $widgets;
    private readonly SiteViewModelBuilder $siteBuilder;
    private readonly ArchiveStateProviderInterface $archiveState;
    private readonly PageLinkProviderInterface $pageLinks;
    private readonly ArchiveViewModelBuilder $archiveBuilder;
    private readonly CommentsRendererInterface $comments;

    public function __construct(
        ?MenuProviderInterface $menuProvider = null,
        ?MenuViewModelBuilder $menuBuilder = null,
        ?SiteInfoProviderInterface $siteInfo = null,
        ?LabelProviderInterface $labels = null,
        ?WidgetAreaProviderInterface $widgets = null,
        ?SiteViewModelBuilder $siteBuilder = null,
        ?ArchiveStateProviderInterface $archiveState = null,
        ?PageLinkProviderInterface $pageLinks = null,
        ?ArchiveViewModelBuilder $archiveBuilder = null,
        ?CommentsRendererInterface $comments = null,
    ) {
        $wpArchive = new WordPressArchiveStateProvider();
        $this->menuProvider = $menuProvider ?? new WordPressMenuProvider();
        $this->menuBuilder = $menuBuilder ?? new MenuViewModelBuilder();
        $this->siteInfo = $siteInfo ?? new WordPressSiteInfoProvider();
        $this->labels = $labels ?? new WordPressLabelProvider();
        $this->widgets = $widgets ?? new WordPressWidgetAreaProvider();
        $this->siteBuilder = $siteBuilder ?? new SiteViewModelBuilder();
        $this->archiveState = $archiveState ?? $wpArchive;
        $this->pageLinks = $pageLinks ?? $wpArchive;
        $this->archiveBuilder = $archiveBuilder ?? new ArchiveViewModelBuilder();
        $this->comments = $comments ?? new WordPressCommentsRenderer();
    }

    /** @return array<string, mixed> */
    public function createPageContext(?\WP_Post $post = null): array
    {
        $post = $post ?? get_post();
        $page = $post instanceof \WP_Post ? $this->post($post, true) : null;
        $site = $this->siteInfo->site();
        $context = array_merge($this->common($site), [
            'page' => $page,
            'hero' => $this->siteBuilder->hero($site, $this->labels->hero()),
            'comments' => $post instanceof \WP_Post && is_singular() ? (string) $this->comments->render($post->ID) : '',
        ]);

        if (is_front_page()) {
            $context['posts'] = $this->recentPosts(6);
        }

        return apply_filters('wpfasty_context', $context, $post);
    }

    /**
     * Public view-model for any post (used by child themes for secondary queries).
     *
     * @return array<string, mixed>
     */
    public function postData(\WP_Post $post, bool $singular = false): array
    {
        return $this->post($post, $singular);
    }

    /**
     * Batch-loads everything a list of post view-models touches (ADR-001 §4):
     * post meta, taxonomy terms, featured image posts + meta, authors. Avoids N+1.
     *
     * @param list<\WP_Post> $posts
     */
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

    /** @return array<string, mixed> */
    public function createArchiveContext(): array
    {
        $posts = [];
        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $this->prime(array_values(array_filter($wp_query->posts, static fn ($p): bool => $p instanceof \WP_Post)));
        }
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $p = get_post();
                if ($p instanceof \WP_Post) {
                    $posts[] = $this->post($p, false);
                }
            }
            wp_reset_postdata();
        }

        $state = $this->archiveState->current();
        $searchTitle = null !== $state->searchQuery ? $this->labels->searchResultsTitle($state->searchQuery) : null;

        $context = array_merge($this->common($this->siteInfo->site()), [
            'archive' => $this->archiveBuilder->archive($state, $searchTitle),
            'posts' => $posts,
            'pagination' => $this->archiveBuilder->pagination($state, $this->pageLinks),
        ]);

        return apply_filters('wpfasty_context', $context, null);
    }

    /** @return array<string, mixed> */
    public function createErrorContext(int $code = 404): array
    {
        $site = $this->siteInfo->site();
        $context = array_merge($this->common($site), [
            'error' => $this->siteBuilder->error($site, $code, $this->labels->error()),
        ]);

        return apply_filters('wpfasty_context', $context, null);
    }

    /**
     * WP returns texturized, entity-encoded strings (&#8217;, &hellip;); Latte escapes
     * on output, so decode to plain text here to avoid double-encoding.
     */
    public static function text(string $value): string
    {
        return html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @return array{
     *   site: array<string, mixed>,
     *   menu: array<string, mixed>,
     *   shell: string,
     *   sheet: array<string, string>,
     *   labels: array<string, string>,
     *   search: array<string, string>,
     *   widgets: array<string, string>
     * }
     */
    private function common(SiteInfo $site): array
    {
        $shell = $this->siteBuilder->shell(apply_filters('wpfasty_shell', 'classic'));

        return [
            'site' => $this->siteBuilder->site($site, gmdate('Y')),
            'menu' => $this->menus(),
            'shell' => $shell,
            'sheet' => $this->siteBuilder->sheetIds($shell),
            'labels' => $this->labels->labels(),
            'search' => $this->siteBuilder->search(
                $site,
                is_search() ? (string) get_search_query() : '',
                $this->labels->search(),
            ),
            'widgets' => [
                // Full-width child themes: add_theme_support('wpfasty-full-width') — no content sidebar.
                'sidebar' => function_exists('current_theme_supports') && current_theme_supports('wpfasty-full-width')
                    ? ''
                    : (string) $this->widgets->render('sidebar-1'),
                'footer' => (string) $this->widgets->render('footer-1'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function post(\WP_Post $post, bool $singular): array
    {
        $data = [
            'id' => $post->ID,
            'title' => self::text(get_the_title($post)),
            // a password-protected post shows the password form (and no excerpt) until the visitor has entered it,
            // like the_content() / get_the_excerpt() of core templates
            'content' => post_password_required($post)
                ? get_the_password_form($post)
                : apply_filters('the_content', $post->post_content),
            'slug' => $post->post_name,
            'url' => (string) get_permalink($post),
            'excerpt' => self::text(has_excerpt($post) || post_password_required($post)
                ? get_the_excerpt($post)
            : wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 40)),
            'featuredImage' => null,
            'thumbnail' => null,
            'categories' => [],
            'tags' => [],
            'author' => null,
            'postClass' => implode(' ', get_post_class('', $post)),
            'linkPages' => '',
            'prev' => null,
            'next' => null,
            'mimeType' => $post->post_mime_type,
            'isAttachment' => 'attachment' === $post->post_type,
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

        if ('attachment' === $post->post_type) {
            $alt = (string) get_post_meta($post->ID, '_wp_attachment_image_alt', true);
            $data['featuredImage'] = $this->image($post->ID, 'large', $alt) ?? $data['featuredImage'];
            $data['attachmentUrl'] = (string) wp_get_attachment_url($post->ID);
        }

        $hasCategories = is_object_in_taxonomy($post->post_type, 'category');
        foreach (($hasCategories ? get_the_category($post->ID) : []) ?: [] as $cat) {
            $data['categories'][] = [
                'name' => $cat->name,
                'url' => get_category_link($cat->term_id),
                'slug' => $cat->slug,
            ];
        }

        $tags = is_object_in_taxonomy($post->post_type, 'post_tag') ? get_the_tags($post->ID) : false;
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $data['tags'][] = [
                    'name' => $tag->name,
                    'url' => get_tag_link($tag->term_id),
                    'slug' => $tag->slug,
                ];
            }
        }

        $authorId = (int) $post->post_author;
        if ($authorId > 0) {
            $data['author'] = [
                'name' => (string) get_the_author_meta('display_name', $authorId),
                'url' => (string) get_author_posts_url($authorId),
            ];
        }

        if ($singular && 'attachment' !== $post->post_type) {
            $data['linkPages'] = wp_link_pages([
                'before' => '<nav class="post-pages mt-8 text-sm text-muted-foreground" aria-label="'
                    .esc_attr__('Pages', 'latte').'"><span class="mr-2">'
                    .esc_html__('Pages:', 'latte').'</span>',
                'after' => '</nav>',
                'echo' => false,
            ]) ?: '';

            if ('post' === $post->post_type) {
                $prev = get_adjacent_post(false, '', true);
                $next = get_adjacent_post(false, '', false);
                if ($prev instanceof \WP_Post) {
                    $data['prev'] = [
                        'title' => self::text(get_the_title($prev)),
                        'url' => (string) get_permalink($prev),
                    ];
                }
                if ($next instanceof \WP_Post) {
                    $data['next'] = [
                        'title' => self::text(get_the_title($next)),
                        'url' => (string) get_permalink($next),
                    ];
                }
            }
        }

        /** @var array<string, mixed> $data */
        $data = apply_filters('wpfasty_post_data', $data, $post, $singular);

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
            'width' => (string) $src[1],
            'height' => (string) $src[2],
            'alt' => $alt,
            'srcset' => (string) (wp_get_attachment_image_srcset($id, $size) ?: ''),
            'sizes' => (string) (wp_get_attachment_image_sizes($id, $size) ?: ''),
        ];
    }

    /** @return array<string, array{items: list<array<string, mixed>>}> */
    private function menus(): array
    {
        return $this->menuBuilder->build($this->menuProvider->itemsByLocation());
    }

    /** @return list<array<string, mixed>> */
    private function recentPosts(int $count): array
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
            $posts[] = $this->post($p, false);
        }
        wp_reset_postdata();

        return $posts;
    }
}
