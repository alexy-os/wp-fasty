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
error_log('Front page context: ' . json_encode($context));

// Add content to context
try {
    error_log('Rendering front-page template...');
    $content = $themeService->render('front-page/front-page', $context);
    error_log('Front page content rendered successfully');
    
    $context['content'] = $content;
    error_log('Content added to context');
    
    error_log('Rendering layout template...');
    // Render the layout with content
    $output = $themeService->render('layout/default', $context);
    error_log('Layout rendered successfully');
    
    echo $output;
} catch (\Throwable $e) {
    error_log('Error in front-page.php: ' . $e->getMessage());
    error_log($e->getTraceAsString());
    throw $e;
}