<?php

declare(strict_types=1);

use WPFasty\Core\Container;
use WPFasty\Core\ContainerInterface;
use WPFasty\Data\ContextFactory;
use WPFasty\Hooks\AssetsHooks;
use WPFasty\Hooks\ThemeHooks;
use WPFasty\Template\LatteRenderer;
use WPFasty\Template\RendererFactory;
use WPFasty\Template\RendererInterface;
use WPFasty\Theme\PrimitivesLocator;
use WPFasty\Theme\ThemeService;

return function (Container $container): void {
    $container->singleton('hooks.theme', static fn () => new ThemeHooks($container));
    $container->addTag('hooks.theme', ContainerInterface::TAG_BOOTABLE);

    $container->singleton('hooks.assets', static fn () => new AssetsHooks($container));
    $container->addTag('hooks.assets', ContainerInterface::TAG_BOOTABLE);

    $container->singleton('template.engine', static function (): RendererInterface {
        $templateDir = get_template_directory();
        $stylesheetDir = get_stylesheet_directory();

        $roots = [$stylesheetDir . '/views', $templateDir . '/views'];
        $primitives = [
            'latte' => array_filter([PrimitivesLocator::resolve($templateDir)]),
            'twig' => array_filter([PrimitivesLocator::resolveTwig($templateDir)]),
        ];

        $engine = apply_filters('wpfasty_renderer', 'latte');
        $roots = (array) apply_filters('wpfasty_view_roots', $roots, is_string($engine) ? $engine : 'custom');
        if ($engine === 'latte') {
            // Legacy filter: receives the full Latte chain (views + primitives).
            $chain = (array) apply_filters('wpfasty_latte_roots', array_merge($roots, $primitives['latte']));
            return new LatteRenderer($chain, $templateDir . '/~cache');
        }

        return RendererFactory::create(
            $engine instanceof RendererInterface ? $engine : (string) $engine,
            $roots,
            $primitives,
            $templateDir . '/~cache'
        );
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
