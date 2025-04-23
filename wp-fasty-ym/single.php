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

// Render the header
echo $themeService->render('header', $context);

// Render the content
echo $themeService->render('single/single', $context);

// Render the footer
echo $themeService->render('footer', $context); 

echo '<pre>';
print_r($context);
echo '</pre>';