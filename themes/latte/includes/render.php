<?php

declare(strict_types=1);

/**
 * Shared WP template entry — resolve view, build context, render into layout.
 *
 * Optional `$view` override is supported for rare explicit entry points;
 * default path uses ThemeService::resolveView().
 */

use WPFasty\Core\Application;
use WPFasty\Theme\ThemeService;

$app = Application::getInstance();
/** @var ThemeService $theme */
$theme = $app->get(ThemeService::class);
$view = isset($view) && is_string($view) && $view !== ''
    ? $view
    : $theme->resolveView();
$context = $theme->context($view);

echo $theme->renderPage($view, $context);
