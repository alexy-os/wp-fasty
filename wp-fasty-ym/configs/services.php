<?php

declare(strict_types=1);

/**
 * Services configuration for the application
 *
 * This file registers all services used by the application.
 */

use WPFasty\Core\Container;
use WPFasty\Core\ContainerInterface;
use WPFasty\Templates\FullPageTemplate;
use WPFasty\Hooks\PageTemplateHooks;
use WPFasty\Hooks\AssetsHooks;
use WPFasty\Tools\HtmlToEditor;

return function (Container $container): void {
    // Register templates
    $container->bind('templates.fullpage', function ($container) {
        return new FullPageTemplate();
    }, FullPageTemplate::class);

    // Register hooks (as singletons)
    $container->singleton('hooks.page_template', function ($container) {
        return new PageTemplateHooks($container);
    }, PageTemplateHooks::class);
    $container->addTag('hooks.page_template', ContainerInterface::TAG_BOOTABLE);
    
    $container->singleton('hooks.assets', function ($container) {
        return new AssetsHooks($container);
    }, AssetsHooks::class);
    $container->addTag('hooks.assets', ContainerInterface::TAG_BOOTABLE);
    
    // Register tools
    $container->singleton('tools.html_editor', function ($container) {
        return new HtmlToEditor($container);
    }, HtmlToEditor::class);
    $container->addTag('tools.html_editor', ContainerInterface::TAG_BOOTABLE);
    
    // You can add more services here
    
    // Example of a service with dependencies from the container
    // $container->singleton('some.service', function ($container) {
    //     return new SomeService(
    //         $container->get('other.dependency'),
    //         $container->get('another.dependency')
    //     );
    // }, SomeService::class);
    // $container->addTag('some.service', ContainerInterface::TAG_BOOTABLE);
};
