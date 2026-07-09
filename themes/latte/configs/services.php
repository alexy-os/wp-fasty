<?php

declare(strict_types=1);

use WPFasty\Core\Container;
use WPFasty\Core\ContainerInterface;
use WPFasty\Data\ContextFactory;
use WPFasty\Hooks\AssetsHooks;
use WPFasty\Hooks\ThemeHooks;
use WPFasty\Template\LatteEngine;
use WPFasty\Theme\PrimitivesLocator;
use WPFasty\Theme\ThemeService;

return function (Container $container): void {
    $container->singleton('hooks.theme', static fn () => new ThemeHooks($container));
    $container->addTag('hooks.theme', ContainerInterface::TAG_BOOTABLE);

    $container->singleton('hooks.assets', static fn () => new AssetsHooks($container));
    $container->addTag('hooks.assets', ContainerInterface::TAG_BOOTABLE);

    $container->singleton('template.engine', static function (): LatteEngine {
        $templateDir = get_template_directory();
        $stylesheetDir = get_stylesheet_directory();
        $primitives = PrimitivesLocator::resolve($templateDir);

        $roots = array_values(array_filter([
            $stylesheetDir . '/views',
            $templateDir . '/views',
            $primitives,
        ]));

        /** @var list<string> $roots */
        $roots = apply_filters('wpfasty_latte_roots', $roots);

        return new LatteEngine($roots, $templateDir . '/~cache');
    });

    $container->singleton('data.context_factory', static fn () => new ContextFactory());

    $container->singleton('theme', static function (Container $c): ThemeService {
        return new ThemeService(
            $c->get('template.engine'),
            $c->get('data.context_factory'),
        );
    });
    $container->bind(ThemeService::class, static fn (Container $c) => $c->get('theme'));
};
