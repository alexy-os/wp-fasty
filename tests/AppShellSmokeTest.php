<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use Latte\Engine;
use PHPUnit\Framework\TestCase;
use WPFasty\Template\MultiRootLoader;
use WPFasty\Template\WordPressExtension;

final class AppShellSmokeTest extends TestCase
{
    public function testAppShellRendersSidebarAndSheet(): void
    {
        $theme = dirname(__DIR__).'/themes/latte';
        $cache = sys_get_temp_dir().'/latte-app-shell-'.uniqid('', true);
        mkdir($cache, 0755, true);

        $latte = new Engine();
        $latte->setTempDirectory($cache);
        $latte->setStrictTypes(true);
        $latte->setLoader(new MultiRootLoader([
            $theme.'/views',
            ...\WPFasty\Theme\PrimitivesLocator::latteRoots(dirname(__DIR__).'/themes/latte'),
        ]));
        $latte->addExtension(new WordPressExtension());

        $ctx = [
            'site' => [
                'title' => 'Latte',
                'url' => '/',
                'themeUrl' => '/',
                'stylesheetUrl' => '/',
                'lang' => 'en',
                'description' => '',
                'charset' => 'UTF-8',
                'year' => '2026',
                'logo' => null,
            ],
            'menu' => [
                'primary' => [
                    'items' => [
                        [
                            'title' => 'Home',
                            'url' => '/',
                            'id' => 1,
                            'current' => true,
                            'children' => [],
                        ],
                    ],
                ],
                'footer' => ['items' => []],
            ],
            'shell' => 'app-shell',
            'sheet' => [
                'trigger' => 'app-shell-mobile-sheet-trigger',
                'panel' => 'app-shell-mobile-sheet-panel',
                'title' => 'app-shell-mobile-sheet-title',
            ],
            'search' => [
                'action' => '/',
                'query' => '',
                'placeholder' => 'Search…',
                'submit' => 'Search',
                'label' => 'Search',
            ],
            'widgets' => [
                'sidebar' => '',
                'footer' => '',
            ],
            'labels' => [
                'skipToContent' => 'Skip to content',
                'openMenu' => 'Open menu',
                'closeMenu' => 'Close menu',
                'darkMode' => 'Dark mode',
                'primaryNav' => 'Primary',
                'footerNav' => 'Footer',
                'sidebar' => 'Sidebar',
            ],
            'content' => '<p>Hi</p>',
        ];

        $html = $latte->renderToString('layout/default.latte', $ctx);
        $this->assertStringContainsString('app-shell-mobile-sheet-panel', $html);
        $this->assertStringContainsString('aria-label="Sidebar"', $html);
        $this->assertStringContainsString('behavior="ui8kit"', $html);
        $this->assertStringContainsString('data-state="closed"', $html);
        $this->assertStringContainsString('hidden', $html);
        $this->assertStringContainsString('<!--wp_body_open-->', $html);
        $this->assertStringContainsString('Open menu', $html);
    }
}
