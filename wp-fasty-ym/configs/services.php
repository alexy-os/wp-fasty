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
use WPFasty\Data\ContextFactory;
use WPFasty\Theme\ThemeService;
use WPFasty\Template\TemplateEngineInterface;
use WPFasty\Template\LatteEngine;
use WPFasty\Hooks\ThemeHooks;
use WPFasty\Hooks\ActivationHooks;
use WPFasty\Tools\ContextSchemaAdmin;
use WPFasty\Data\ContextSchemaGenerator;

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

    // Register theme hooks
    $container->singleton('hooks.theme', function ($container) {
        return new ThemeHooks($container);
    }, ThemeHooks::class);
    $container->addTag('hooks.theme', ContainerInterface::TAG_BOOTABLE);
    
    // Register activation hooks
    $container->singleton('hooks.activation', function ($container) {
        return new ActivationHooks($container);
    }, ActivationHooks::class);
    $container->addTag('hooks.activation', ContainerInterface::TAG_BOOTABLE);
    
    $container->singleton('hooks.assets', function ($container) {
        return new AssetsHooks($container);
    }, AssetsHooks::class);
    $container->addTag('hooks.assets', ContainerInterface::TAG_BOOTABLE);
    
    // Register tools
    $container->singleton('tools.html_editor', function ($container) {
        return new HtmlToEditor($container);
    }, HtmlToEditor::class);
    $container->addTag('tools.html_editor', ContainerInterface::TAG_BOOTABLE);

    // Register admin interfaces
    $container->singleton('tools.context_schema_admin', function ($container) {
        return new ContextSchemaAdmin($container);
    }, ContextSchemaAdmin::class);
    $container->addTag('tools.context_schema_admin', ContainerInterface::TAG_BOOTABLE);
    
    // Register template engine
    $container->singleton('template.engine', function ($container) {
        $themeDir = get_template_directory();
        $viewsDir = $themeDir . '/views';
        $cacheDir = $themeDir . '/~cache';
        
        return new LatteEngine($viewsDir, $cacheDir);
    }, TemplateEngineInterface::class);
    
    // Register context factory
    $container->singleton('data.context_factory', function ($container) {
        return new ContextFactory($container);
    }, ContextFactory::class);
    
    // Register context schema generator
    $container->singleton('data.context_schema_generator', function ($container) {
        return new ContextSchemaGenerator($container);
    }, ContextSchemaGenerator::class);
    $container->addTag('data.context_schema_generator', ContainerInterface::TAG_BOOTABLE);
    
    // Register theme service
    $container->singleton('theme', function ($container) {
        return new ThemeService(
            $container->get('template.engine'),
            $container->get('data.context_factory')
        );
    }, ThemeService::class);
    $container->bind(ThemeService::class, function ($container) {
        return $container->get('theme');
    });
    
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
