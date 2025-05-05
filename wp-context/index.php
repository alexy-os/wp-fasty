<?php

declare(strict_types=1);

use WPFasty\Core\Application;
use WPFasty\Data\ContextFactory;

// Get the application instance
$app = Application::getInstance();

// Get the dependency container
$container = $app->getContainer();

// Get ContextFactory from the container or create it
if ($container->has(ContextFactory::class)) {
    $contextFactory = $container->get(ContextFactory::class);
} else {
    $contextFactory = new ContextFactory($container);
}

if (is_singular()) {
  $context = $contextFactory->createPageContext();
} elseif (is_archive() || is_home()) {
  $context = $contextFactory->createArchiveContext();
} else {
  // Default context
  $context = $contextFactory->createPageContext();
}

// Output the context
echo '<pre>';
print_r($context);
echo '</pre>';