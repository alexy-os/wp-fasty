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
    $content = $themeService->render('archive/archive', $context);
    
    $context['content'] = $content;
    // Render the layout with content
    $output = $themeService->render('layout/default', $context);

    echo $output;
} catch (\Throwable $e) {
    error_log('Error in archive.php: ' . $e->getMessage());
    error_log($e->getTraceAsString());
    throw $e;
}
