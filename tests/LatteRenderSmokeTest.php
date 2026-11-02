<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use Latte\Engine;
use PHPUnit\Framework\TestCase;
use WPFasty\Template\MultiRootLoader;
use WPFasty\Template\WordPressExtension;

final class LatteRenderSmokeTest extends TestCase
{
    private string $cacheDir;

    public function testFrontPageRenders(): void
    {
        $latte = $this->engine();
        $ctx = $this->baseContext() + ['page' => null, 'posts' => []];
        $html = $latte->renderToString('pages/front-page.latte', $ctx);
        $layout = $latte->renderToString('layout/default.latte', $ctx + ['content' => $html]);
        $this->assertStringContainsString('Latte', $layout);
        $this->assertStringContainsString('skip-link', $layout);
        $this->assertStringContainsString('Explore', $layout);
    }

    public function testSingleRenders(): void
    {
        $latte = $this->engine();
        $ctx = $this->baseContext() + [
            'page' => [
                'id' => 1,
                'title' => 'Hello',
                'content' => '<p>Hi</p>',
                'slug' => 'hello',
                'url' => 'http://example.test/hello',
                'excerpt' => 'Hi',
                'featuredImage' => null,
                'thumbnail' => null,
                'categories' => [['name' => 'News', 'url' => '/news', 'slug' => 'news']],
                'tags' => [],
                'author' => ['name' => 'Ada', 'url' => '/author/ada'],
                'postClass' => 'post post-1',
                'linkPages' => '',
                'prev' => null,
                'next' => null,
                'date' => ['display' => 'Jul 9, 2026', 'iso' => '2026-07-09'],
            ],
        ];
        $html = $latte->renderToString('pages/single.latte', $ctx);
        $this->assertStringContainsString('Hello', $html);
        $this->assertStringContainsString('Ada', $html);
        $this->assertStringContainsString('News', $html);
    }

    public function testLayoutHasOrgHooksAndSearch(): void
    {
        $latte = $this->engine();
        $html = $latte->renderToString('layout/default.latte', $this->baseContext() + ['content' => '<p>x</p>']);
        $this->assertStringContainsString('<!--wp_body_open-->', $html);
        $this->assertStringContainsString('lang="en-US"', $html);
        $this->assertStringContainsString('name="s"', $html);
        $this->assertStringContainsString('role="search"', $html);
    }

    public function testClassicLayoutRendersSidebarWidgets(): void
    {
        $latte = $this->engine();
        $ctx = $this->baseContext();
        $ctx['widgets']['sidebar'] = '<section class="widget">Sidebar widget</section>';
        $html = $latte->renderToString('layout/default.latte', $ctx + ['content' => '<p>x</p>']);
        $this->assertStringContainsString('Sidebar widget', $html);
        $this->assertStringContainsString('aria-label="Sidebar"', $html);
    }

    public function testAttachmentRenders(): void
    {
        $latte = $this->engine();
        $ctx = $this->baseContext() + [
            'page' => [
                'id' => 9,
                'title' => 'Photo',
                'content' => '',
                'slug' => 'photo',
                'url' => 'http://example.test/photo',
                'excerpt' => '',
                'featuredImage' => [
                    'url' => 'http://example.test/photo.jpg',
                    'alt' => 'Photo',
                    'width' => '800',
                    'height' => '600',
                    'srcset' => '',
                    'sizes' => '',
                ],
                'thumbnail' => null,
                'categories' => [],
                'tags' => [],
                'author' => null,
                'postClass' => 'attachment',
                'linkPages' => '',
                'prev' => null,
                'next' => null,
                'attachmentUrl' => 'http://example.test/photo.jpg',
                'isAttachment' => true,
                'date' => ['display' => '', 'iso' => ''],
            ],
        ];
        $html = $latte->renderToString('pages/attachment.latte', $ctx);
        $this->assertStringContainsString('Photo', $html);
        $this->assertStringContainsString('photo.jpg', $html);
    }

    public function test404Renders(): void
    {
        $latte = $this->engine();
        $ctx = $this->baseContext() + [
            'error' => [
                'code' => 404,
                'title' => 'Page not found',
                'message' => 'Missing',
                'homeUrl' => 'http://example.test/',
                'homeLabel' => 'Back to home',
            ],
        ];
        $html = $latte->renderToString('pages/404.latte', $ctx);
        $this->assertStringContainsString('Page not found', $html);
        $this->assertStringContainsString('Back to home', $html);
    }

    public function testChildViewRootWins(): void
    {
        $theme = dirname(__DIR__).'/themes/latte';
        $skin = dirname(__DIR__).'/themes/latte-skin';
        $roots = [
            $skin.'/views',
            $theme.'/views',
            ...\WPFasty\Theme\PrimitivesLocator::latteRoots(dirname(__DIR__).'/themes/latte'),
        ];
        $latte = new Engine();
        $latte->setTempDirectory($this->cacheDir);
        $latte->setStrictTypes(true);
        $latte->setLoader(new MultiRootLoader($roots));
        $latte->addExtension(new WordPressExtension());

        $html = $latte->renderToString('blocks/hero-centered.latte', $this->baseContext());
        $this->assertStringContainsString('latte-skin-hero', $html);
        $this->assertStringContainsString('text-primary', $html);
    }

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir().'/latte-smoke-'.uniqid('', true);
        mkdir($this->cacheDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->cacheDir);
    }

    private function engine(): Engine
    {
        $theme = dirname(__DIR__).'/themes/latte';
        $roots = [
            $theme.'/views',
            ...\WPFasty\Theme\PrimitivesLocator::latteRoots(dirname(__DIR__).'/themes/latte'),
        ];
        $latte = new Engine();
        $latte->setTempDirectory($this->cacheDir);
        $latte->setStrictTypes(true);
        $latte->setLoader(new MultiRootLoader($roots));
        $latte->addExtension(new WordPressExtension());

        return $latte;
    }

    /** @return array<string, mixed> */
    private function baseContext(): array
    {
        return [
            'site' => [
                'title' => 'Latte',
                'url' => 'http://example.test/',
                'themeUrl' => 'http://example.test/theme',
                'stylesheetUrl' => 'http://example.test/theme',
                'lang' => 'en-US',
                'description' => 'Smoke',
                'charset' => 'UTF-8',
                'year' => '2026',
                'logo' => null,
            ],
            'menu' => [
                'primary' => ['items' => []],
                'footer' => ['items' => []],
            ],
            'shell' => 'classic',
            'sheet' => [
                'trigger' => 'classic-mobile-sheet-trigger',
                'panel' => 'classic-mobile-sheet-panel',
                'title' => 'classic-mobile-sheet-title',
            ],
            'search' => [
                'action' => 'http://example.test/',
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
                'featured' => 'Featured',
                'latestArticles' => 'Latest articles',
                'noPosts' => 'No posts found.',
                'previous' => 'Previous',
                'next' => 'Next',
                'skipToContent' => 'Skip to content',
                'openMenu' => 'Open menu',
                'closeMenu' => 'Close menu',
                'darkMode' => 'Dark mode',
                'primaryNav' => 'Primary',
                'footerNav' => 'Footer',
                'sidebar' => 'Sidebar',
                'pagination' => 'Pagination',
                'postedOn' => 'Posted on',
                'byAuthor' => 'By',
                'categories' => 'Categories',
                'tags' => 'Tags',
                'previousPost' => 'Previous post',
                'nextPost' => 'Next post',
                'attachment' => 'Attachment',
                'pages' => 'Pages:',
            ],
            'hero' => [
                'primaryLabel' => 'Explore',
                'primaryUrl' => 'http://example.test/blog',
                'secondaryLabel' => 'Learn more',
                'secondaryUrl' => 'http://example.test/',
            ],
        ];
    }
}
