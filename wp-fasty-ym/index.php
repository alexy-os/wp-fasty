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

// Determine which template to use based on WordPress conditional tags
if (is_front_page()) {
    echo $themeService->render('front-page/front-page', $context);
} elseif (is_singular()) {
    echo $themeService->render('single/single', $context);
} elseif (is_archive() || is_home()) {
    echo $themeService->render('archive/archive', $context);
} else {
    echo $themeService->render('page/page', $context);
}

// Render the footer
echo $themeService->render('footer', $context);