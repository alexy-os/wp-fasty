<?php

declare(strict_types=1);

/**
 * Shared WP template entry — render Latte view into layout.
 *
 * @param string $view Relative path under views/ without .latte
 */

use WPFasty\Core\Application;
use WPFasty\Theme\ThemeService;

$app = Application::getInstance();
/** @var ThemeService $theme */
$theme = $app->get(ThemeService::class);
$context = $theme->context();

echo $theme->renderPage($view, $context);
