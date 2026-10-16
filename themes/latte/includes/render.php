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

// Engine-agnostic document start (kept in PHP so every layout/engine shares it).
echo "<!DOCTYPE html>\n";

// Templates auto-escape all output; the rendered string is final HTML.
echo $theme->renderPage($view, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
