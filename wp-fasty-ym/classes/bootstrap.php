<?php

declare(strict_types=1);

use WPFasty\Core\Application;

// Get the application instance
$app = Application::getInstance();

// Bootstrap the application
$app->bootstrap();

// Return the application instance
return $app; 