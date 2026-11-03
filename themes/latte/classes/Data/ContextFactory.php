<?php

declare(strict_types=1);

namespace WPFasty\Data;

use WPFasty\Adapter\WordPress\ContextFilters;
use WPFasty\Adapter\WordPress\WordPressPostRepositoryInterface;
use WPFasty\Core\Html\TrustedHtml;
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
 * builders (WPFasty\Data\*) and runs the result through ContextFilters. Wired in configs/context.php.
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
        private readonly ContextFilters $filters,
    ) {
    }

    /** @return array<string, mixed> */
    public function createPageContext(?\WP_Post $post = null): array
    {
        $post ??= get_post();
        $page = $post instanceof \WP_Post ? $this->postData($post, true) : null; // before the menus/widgets queries
        $site = $this->siteInfo->site();
        $request = $this->request->current();

        return $this->filters->context(array_merge($this->common->build($site, $request), [
            'page' => $page,
            'hero' => $this->siteBuilder->hero($site, $this->labels->hero()),
            'comments' => $post instanceof \WP_Post && $request->singular ? $this->comments->render($post->ID) : TrustedHtml::empty(),
        ], $request->frontPage ? ['posts' => $this->viewModels($this->posts->recent(6))] : []), $post);
    }

    /** @return array<string, mixed> the public view-model of any post (child themes, secondary queries) */
    public function postData(\WP_Post $post, bool $singular = false): array
    {
        return $this->viewModels([$this->posts->fromWordPress($post, $singular)])[0];
    }

    /** @param list<\WP_Post> $posts batch-loads what their view-models touch (PostRepository::prime), no N+1 */
    public function prime(array $posts): void
    {
        $this->posts->prime($posts);
    }

    /** @return array<string, mixed> */
    public function createArchiveContext(): array
    {
        $state = $this->archiveState->current();
        $searchTitle = null !== $state->searchQuery ? $this->labels->searchResultsTitle($state->searchQuery) : null;

        return $this->filters->context(array_merge($this->common->build($this->siteInfo->site(), $this->request->current()), [
            'archive' => $this->archiveBuilder->archive($state, $searchTitle),
            'posts' => $this->viewModels($this->posts->mainQuery()),
            'pagination' => $this->archiveBuilder->pagination($state, $this->pageLinks),
        ]), null);
    }

    /** @return array<string, mixed> */
    public function createErrorContext(int $code = 404): array
    {
        $site = $this->siteInfo->site();

        return $this->filters->context(array_merge($this->common->build($site, $this->request->current()), [
            'error' => $this->siteBuilder->error($site, $code, $this->labels->error()),
        ]), null);
    }

    /**
     * @param list<Post> $posts
     *
     * @return list<array<string, mixed>>
     */
    private function viewModels(array $posts): array
    {
        return array_map(fn (Post $p): array => $this->filters->postData($this->postBuilder->build($p), $p), $posts);
    }
}
