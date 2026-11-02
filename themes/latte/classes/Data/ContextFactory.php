<?php

declare(strict_types=1);

namespace WPFasty\Data;

use WPFasty\Adapter\WordPress\PlainText;
use WPFasty\Adapter\WordPress\WordPressPostRepositoryInterface;
use WPFasty\Core\Model\Post;
use WPFasty\Core\Port\ArchiveStateProviderInterface;
use WPFasty\Core\Port\CommentsRendererInterface;
use WPFasty\Core\Port\LabelProviderInterface;
use WPFasty\Core\Port\PageLinkProviderInterface;
use WPFasty\Core\Port\RequestStateProviderInterface;
use WPFasty\Core\Port\SiteInfoProviderInterface;
use WPFasty\Data\Archive\ArchiveViewModelBuilder;
use WPFasty\Data\Post\PostViewModelBuilder;
use WPFasty\Data\Site\SiteViewModelBuilder;

/**
 * Facade of the template context: asks the ports (Core\Port, WordPress adapters) for data, hands it to the ViewModel
 * builders (WPFasty\Data\*) and applies the `wpfasty_context` / `wpfasty_post_data` filters. Wired in services.php.
 */
final class ContextFactory
{
    public function __construct(
        private readonly SiteInfoProviderInterface $siteInfo,
        private readonly RequestStateProviderInterface $request,
        private readonly CommonContextBuilder $common,
        private readonly LabelProviderInterface $labels,
        private readonly SiteViewModelBuilder $siteBuilder,
        private readonly ArchiveStateProviderInterface $archiveState,
        private readonly PageLinkProviderInterface $pageLinks,
        private readonly ArchiveViewModelBuilder $archiveBuilder,
        private readonly CommentsRendererInterface $comments,
        private readonly WordPressPostRepositoryInterface $posts,
        private readonly PostViewModelBuilder $postBuilder,
    ) {
    }

    /** @return array<string, mixed> */
    public function createPageContext(?\WP_Post $post = null): array
    {
        $post ??= get_post();
        $page = $post instanceof \WP_Post ? $this->postData($post, true) : null;
        $site = $this->siteInfo->site();
        $request = $this->request->current();
        $context = array_merge($this->common->build($site, $request), [
            'page' => $page,
            'hero' => $this->siteBuilder->hero($site, $this->labels->hero()),
            'comments' => $post instanceof \WP_Post && $request->singular ? (string) $this->comments->render($post->ID) : '',
        ]);
        if ($request->frontPage) {
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
        $context = array_merge($this->common->build($this->siteInfo->site(), $this->request->current()), [
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
        $context = array_merge($this->common->build($site, $this->request->current()), [
            'error' => $this->siteBuilder->error($site, $code, $this->labels->error()),
        ]);

        return apply_filters('wpfasty_context', $context, null);
    }

    /** @deprecated since the PostRepository split; use WPFasty\Adapter\WordPress\PlainText::from() */
    public static function text(string $value): string
    {
        return PlainText::from($value);
    }

    /**
     * One post view-model after the `wpfasty_post_data` filter (receives the Core Post DTO).
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
}
