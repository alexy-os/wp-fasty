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
            'featured' => __('Featured', 'wp-fasty'),
            'latestArticles' => __('Latest articles', 'wp-fasty'),
            'noPosts' => __('No posts found.', 'wp-fasty'),
            'previous' => __('Previous', 'wp-fasty'),
            'next' => __('Next', 'wp-fasty'),
            'skipToContent' => __('Skip to content', 'wp-fasty'),
            'openMenu' => __('Open menu', 'wp-fasty'),
            'closeMenu' => __('Close menu', 'wp-fasty'),
            'darkMode' => __('Dark mode', 'wp-fasty'),
            'primaryNav' => __('Primary', 'wp-fasty'),
            'mobileNav' => __('Mobile menu', 'wp-fasty'),
            'searchSite' => __('Site search', 'wp-fasty'),
            'searchMobile' => __('Mobile menu search', 'wp-fasty'),
            'search404' => __('Search again', 'wp-fasty'),
            'footerNav' => __('Footer', 'wp-fasty'),
            'sidebar' => __('Sidebar', 'wp-fasty'),
            'pagination' => __('Pagination', 'wp-fasty'),
            'postedOn' => __('Posted on', 'wp-fasty'),
            'byAuthor' => __('By', 'wp-fasty'),
            'categories' => __('Categories', 'wp-fasty'),
            'tags' => __('Tags', 'wp-fasty'),
            'previousPost' => __('Previous post', 'wp-fasty'),
            'nextPost' => __('Next post', 'wp-fasty'),
            'attachment' => __('Attachment', 'wp-fasty'),
            'pages' => __('Pages:', 'wp-fasty'),
        ];
    }

    public function search(): array
    {
        return [
            'placeholder' => __('Search…', 'wp-fasty'),
            'submit' => __('Search', 'wp-fasty'),
            'label' => __('Search', 'wp-fasty'),
        ];
    }

    public function hero(): array
    {
        return [
            'primaryLabel' => __('Explore', 'wp-fasty'),
            'secondaryLabel' => __('Learn more', 'wp-fasty'),
        ];
    }

    public function error(): array
    {
        return [
            'title' => __('Page not found', 'wp-fasty'),
            'message' => __('The page you are looking for could not be found.', 'wp-fasty'),
            'homeLabel' => __('Back to home', 'wp-fasty'),
        ];
    }

    public function searchResultsTitle(string $query): string
    {
        return sprintf(
            /* translators: %s: search query */
            __('Search results for “%s”', 'wp-fasty'),
            $query
        );
    }
}
