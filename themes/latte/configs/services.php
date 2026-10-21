<?php

declare(strict_types=1);

use WPFasty\Core\Container;
use WPFasty\Core\ContainerInterface;
use WPFasty\Data\ContextFactory;
use WPFasty\Hooks\AssetsHooks;
use WPFasty\Hooks\ThemeHooks;
use WPFasty\Template\EngineUnavailable;
use WPFasty\Template\Engines;
use WPFasty\Template\LatteRenderer;
use WPFasty\Template\RendererFactory;
use WPFasty\Template\RendererInterface;
use WPFasty\Theme\PrimitivesLocator;
use WPFasty\Theme\ThemeService;
use WPFasty\Types\Registry;

return function (Container $container): void {
    $container->singleton('hooks.theme', static fn () => new ThemeHooks($container));
    $container->addTag('hooks.theme', ContainerInterface::TAG_BOOTABLE);

    $container->singleton('hooks.assets', static fn () => new AssetsHooks($container));
    $container->addTag('hooks.assets', ContainerInterface::TAG_BOOTABLE);

    $container->singleton('template.engine', static function (): RendererInterface {
        $templateDir = get_template_directory();
        $stylesheetDir = get_stylesheet_directory();

        $roots = [$stylesheetDir . '/views', $templateDir . '/views'];
        $engine = Engines::requested();
        // Only the requested engine's primitives are located (a build never loads the other runtime).
        $primitives = [
            'latte' => $engine === 'latte' ? array_filter([PrimitivesLocator::resolve($templateDir)]) : [],
            'twig' => $engine === 'twig' ? array_filter([PrimitivesLocator::resolveTwig($templateDir)]) : [],
        ];
        $roots = (array) apply_filters('wpfasty_view_roots', $roots, is_string($engine) ? $engine : 'custom');
        if ($engine === 'latte' && Engines::available('latte') && $primitives['latte'] !== []) {
            // Legacy filter: receives the full Latte chain (views + primitives).
            $chain = (array) apply_filters('wpfasty_latte_roots', array_merge($roots, $primitives['latte']));
            return new LatteRenderer($chain, $templateDir . '/~cache');
        }

        try {
            return RendererFactory::create(
                $engine instanceof RendererInterface ? $engine : (string) $engine,
                $roots,
                $primitives,
                $templateDir . '/~cache'
            );
        } catch (EngineUnavailable $e) {
            // Front end: a clear error page, never the other engine. wp-admin shows ThemeHooks::primitivesNotice().
            if (!is_admin() && !(defined('WP_CLI') && WP_CLI)) {
                $msg = $e->getMessage();
                wp_die(esc_html($msg), esc_html__('Theme engine unavailable', 'latte'), ['response' => 500]);
            }
            throw $e;
        }
    });

    // Declarative post-type modules: child `types/*.php` first, then parent `types/*.php`.
    $container->singleton('types.registry', static fn () => new Registry(array_values(array_unique([
        get_stylesheet_directory() . '/types',
        get_template_directory() . '/types',
    ]))));
    $container->addTag('types.registry', ContainerInterface::TAG_BOOTABLE);

    $container->singleton('data.context_factory', static fn () => new ContextFactory());

    $container->singleton('theme', static function (Container $c): ThemeService {
        return new ThemeService(
            $c->get('template.engine'),
            $c->get('data.context_factory'),
        );
    });
    $container->bind(ThemeService::class, static fn (Container $c) => $c->get('theme'));
};
