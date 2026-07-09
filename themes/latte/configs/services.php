<?php

declare(strict_types=1);

use WPFasty\Core\Container;
use WPFasty\Core\ContainerInterface;
use WPFasty\Data\ContextFactory;
use WPFasty\Hooks\AssetsHooks;
use WPFasty\Hooks\ThemeHooks;
use WPFasty\Template\LatteEngine;
use WPFasty\Theme\ThemeService;

return function (Container $container): void {
    $container->singleton('hooks.theme', static fn () => new ThemeHooks($container));
    $container->addTag('hooks.theme', ContainerInterface::TAG_BOOTABLE);

    $container->singleton('hooks.assets', static fn () => new AssetsHooks($container));
    $container->addTag('hooks.assets', ContainerInterface::TAG_BOOTABLE);

    $container->singleton('template.engine', static function (): LatteEngine {
        $themeDir = get_template_directory();
        $candidates = [
            dirname($themeDir, 2) . '/.workspaces/ui8kit-latte/ui',
            $themeDir . '/vendor/ui8kit/latte-primitives/ui',
            $themeDir . '/vendor/ui8kit/ui',
        ];
        $primitives = null;
        foreach ($candidates as $dir) {
            if (is_dir($dir)) {
                $primitives = $dir;
                break;
            }
        }
        return new LatteEngine($themeDir . '/views', $themeDir . '/~cache', $primitives);
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
