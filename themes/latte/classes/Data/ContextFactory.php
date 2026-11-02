<?php

declare(strict_types=1);

namespace WPFasty\Data;

use WPFasty\Adapter\WordPress\PlainText;
use WPFasty\Adapter\WordPress\PostRepository;
use WPFasty\Adapter\WordPress\WordPressArchiveStateProvider;
use WPFasty\Adapter\WordPress\WordPressCommentsRenderer;
use WPFasty\Adapter\WordPress\WordPressLabelProvider;
use WPFasty\Adapter\WordPress\WordPressMenuProvider;
use WPFasty\Adapter\WordPress\WordPressPostRepositoryInterface;
use WPFasty\Adapter\WordPress\WordPressSiteInfoProvider;
use WPFasty\Adapter\WordPress\WordPressWidgetAreaProvider;
use WPFasty\Core\Model\Post;
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
use WPFasty\Data\Post\PostViewModelBuilder;
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
    private readonly WordPressPostRepositoryInterface $posts;
    private readonly PostViewModelBuilder $postBuilder;

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
        ?WordPressPostRepositoryInterface $posts = null,
        ?PostViewModelBuilder $postBuilder = null,
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
        $this->posts = $posts ?? new PostRepository();
        $this->postBuilder = $postBuilder ?? new PostViewModelBuilder();
    }

    /** @return array<string, mixed> */
    public function createPageContext(?\WP_Post $post = null): array
    {
        $post = $post ?? get_post();
        $page = $post instanceof \WP_Post ? $this->postData($post, true) : null;
        $site = $this->siteInfo->site();
        $context = array_merge($this->common($site), [
            'page' => $page,
            'hero' => $this->siteBuilder->hero($site, $this->labels->hero()),
            'comments' => $post instanceof \WP_Post && is_singular() ? (string) $this->comments->render($post->ID) : '',
        ]);

        if (is_front_page()) {
            $context['posts'] = $this->viewModels($this->posts->recent(6));
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
        return $this->viewModel($this->posts->fromWordPress($post, $singular));
    }

    /**
     * Batch-loads everything a list of post view-models touches (PostRepository::prime). Avoids N+1.
     *
     * @param list<\WP_Post> $posts
     */
    public function prime(array $posts): void
    {
        $this->posts->prime($posts);
    }

    /** @return array<string, mixed> */
    public function createArchiveContext(): array
    {
        $posts = $this->viewModels($this->posts->mainQuery());

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
     * Plain text of a WordPress string (entities decoded, tags stripped).
     *
     * @deprecated since the PostRepository split; use WPFasty\Adapter\WordPress\PlainText::from()
     */
    public static function text(string $value): string
    {
        return PlainText::from($value);
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

    /**
     * The view-model of one post after the `wpfasty_post_data` filter (receives the Core Post DTO).
     *
     * @return array<string, mixed>
     */
    private function viewModel(Post $post): array
    {
        /** @var array<string, mixed> $data */
        $data = apply_filters('wpfasty_post_data', $this->postBuilder->build($post), $post, $post->singular);

        return $data;
    }

    /**
     * @param list<Post> $posts
     *
     * @return list<array<string, mixed>>
     */
    private function viewModels(array $posts): array
    {
        return array_map($this->viewModel(...), $posts);
    }

    /** @return array<string, array{items: list<array<string, mixed>>}> */
    private function menus(): array
    {
        return $this->menuBuilder->build($this->menuProvider->itemsByLocation());
    }
}
