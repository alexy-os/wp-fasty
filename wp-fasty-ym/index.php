<?php

declare(strict_types=1);

use WPFasty\Data\ContextFactory;
use WPFasty\Core\Application;

// Get the application instance
$app = Application::getInstance();
$app = Application::getInstance();

// Get the dependency container
$container = $app->getContainer();

// Get ContextFactory from the container or create it
if ($container->has(ContextFactory::class)) {
    $contextFactory = $container->get(ContextFactory::class);
} else {
    $contextFactory = new ContextFactory($container);
}

// Get the current page context
$context = $contextFactory->createPageContext();

// Output the context as a var_dump
echo '<pre>';
var_dump($context->toArray());
echo '</pre>';

