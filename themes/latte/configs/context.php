<?php

/**
 * Wiring of the template context facade: WordPress adapters for the Core ports + the ViewModel builders.
 * Returns a factory; configs/services.php registers it as `data.context_factory` (tests reuse it).
 */

declare(strict_types=1);

use WPFasty\Adapter\WordPress\PostRepository;
use WPFasty\Adapter\WordPress\WordPressArchiveStateProvider;
use WPFasty\Adapter\WordPress\WordPressCommentsRenderer;
use WPFasty\Adapter\WordPress\WordPressLabelProvider;
use WPFasty\Adapter\WordPress\WordPressMenuProvider;
use WPFasty\Adapter\WordPress\WordPressRequestStateProvider;
use WPFasty\Adapter\WordPress\WordPressSiteInfoProvider;
use WPFasty\Adapter\WordPress\WordPressWidgetAreaProvider;
use WPFasty\Data\Archive\ArchiveViewModelBuilder;
use WPFasty\Data\CommonContextBuilder;
use WPFasty\Data\ContextFactory;
use WPFasty\Data\Menu\MenuViewModelBuilder;
use WPFasty\Data\Post\PostViewModelBuilder;
use WPFasty\Data\Site\SiteViewModelBuilder;

if (!defined('ABSPATH')) {
    exit;
}

return static function (): ContextFactory {
    $labels = new WordPressLabelProvider();
    $siteBuilder = new SiteViewModelBuilder();
    $archive = new WordPressArchiveStateProvider();

    return new ContextFactory(
        new WordPressSiteInfoProvider(),
        new WordPressRequestStateProvider(),
        new CommonContextBuilder(new WordPressMenuProvider(), new MenuViewModelBuilder(), $labels, new WordPressWidgetAreaProvider(), $siteBuilder),
        $labels,
        $siteBuilder,
        $archive,
        $archive,
        new ArchiveViewModelBuilder(),
        new WordPressCommentsRenderer(),
        new PostRepository(),
        new PostViewModelBuilder(),
    );
};
