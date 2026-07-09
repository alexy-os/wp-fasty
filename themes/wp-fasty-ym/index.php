<?php

declare(strict_types=1);

use WPFasty\Core\Application;
use WPFasty\Theme\ThemeService;

// Get the application instance
$app = Application::getInstance();

// Get the theme service from the container
$themeService = $app->getContainer()->get(ThemeService::class);

// Get context for the current page
$context = $themeService->context();

try {
    // Determine which template to use based on WordPress conditional tags
    if (is_front_page()) {
        $content = $themeService->render('front-page/front-page', $context);
    } elseif (is_singular()) {
        $content = $themeService->render('single/single', $context);
    } elseif (is_archive() || is_home()) {
        $content = $themeService->render('archive/archive', $context);
    } else {
        $content = $themeService->render('page/page', $context);
    }

    $context['content'] = $content;
    // Render the layout with content
    $output = $themeService->render('layout/default', $context);

    echo $output;
} catch (\Throwable $e) {
    error_log('Error in page.php: ' . $e->getMessage());
    error_log($e->getTraceAsString());
    throw $e;
}