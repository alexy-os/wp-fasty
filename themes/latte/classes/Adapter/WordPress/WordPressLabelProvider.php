<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Port\LabelProviderInterface;

/** The theme's UI strings, text domain `latte`. */
final class WordPressLabelProvider implements LabelProviderInterface
{
    public function labels(): array
    {
        return [
            'featured' => __('Featured', 'latte'),
            'latestArticles' => __('Latest articles', 'latte'),
            'noPosts' => __('No posts found.', 'latte'),
            'previous' => __('Previous', 'latte'),
            'next' => __('Next', 'latte'),
            'skipToContent' => __('Skip to content', 'latte'),
            'openMenu' => __('Open menu', 'latte'),
            'closeMenu' => __('Close menu', 'latte'),
            'darkMode' => __('Dark mode', 'latte'),
            'primaryNav' => __('Primary', 'latte'),
            'mobileNav' => __('Mobile menu', 'latte'),
            'searchSite' => __('Site search', 'latte'),
            'searchMobile' => __('Mobile menu search', 'latte'),
            'search404' => __('Search again', 'latte'),
            'footerNav' => __('Footer', 'latte'),
            'sidebar' => __('Sidebar', 'latte'),
            'pagination' => __('Pagination', 'latte'),
            'postedOn' => __('Posted on', 'latte'),
            'byAuthor' => __('By', 'latte'),
            'categories' => __('Categories', 'latte'),
            'tags' => __('Tags', 'latte'),
            'previousPost' => __('Previous post', 'latte'),
            'nextPost' => __('Next post', 'latte'),
            'attachment' => __('Attachment', 'latte'),
            'pages' => __('Pages:', 'latte'),
        ];
    }

    public function search(): array
    {
        return [
            'placeholder' => __('Search…', 'latte'),
            'submit' => __('Search', 'latte'),
            'label' => __('Search', 'latte'),
        ];
    }

    public function hero(): array
    {
        return [
            'primaryLabel' => __('Explore', 'latte'),
            'secondaryLabel' => __('Learn more', 'latte'),
        ];
    }

    public function error(): array
    {
        return [
            'title' => __('Page not found', 'latte'),
            'message' => __('The page you are looking for could not be found.', 'latte'),
            'homeLabel' => __('Back to home', 'latte'),
        ];
    }

    public function searchResultsTitle(string $query): string
    {
        return sprintf(
            /* translators: %s: search query */
            __('Search results for “%s”', 'latte'),
            $query
        );
    }
}
