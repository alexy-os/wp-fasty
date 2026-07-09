<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFasty\Data\ContextFactory;
use WPFasty\Template\TemplateEngineInterface;
use WPFasty\Theme\ThemeService;

final class ThemeServiceTest extends TestCase
{
    private function service(): ThemeService
    {
        $engine = new class implements TemplateEngineInterface {
            public function render(string $template, array $context = []): string
            {
                return $template;
            }
        };

        return new ThemeService($engine, new ContextFactory());
    }

    protected function setUp(): void
    {
        $GLOBALS['wp_test_flags'] = [];
        $GLOBALS['wp_test_filters'] = [];
    }

    public function testResolveView404(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_404' => true];
        $this->assertSame('pages/404', $this->service()->resolveView());
    }

    public function testResolveViewSearch(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_search' => true];
        $this->assertSame('pages/search', $this->service()->resolveView());
    }

    public function testResolveViewFrontPage(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_front_page' => true, 'is_home' => false];
        $this->assertSame('pages/front-page', $this->service()->resolveView());
    }

    public function testResolveViewHomeBlog(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_home' => true];
        $this->assertSame('pages/archive', $this->service()->resolveView());
    }

    public function testResolveViewArchive(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_archive' => true];
        $this->assertSame('pages/archive', $this->service()->resolveView());
    }

    public function testResolveViewAttachment(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_attachment' => true];
        $this->assertSame('pages/attachment', $this->service()->resolveView());
    }

    public function testResolveViewPage(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_page' => true];
        $this->assertSame('pages/page', $this->service()->resolveView());
    }

    public function testResolveViewSingularPost(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_singular' => true, 'singular_type' => 'post'];
        $this->assertSame('pages/single', $this->service()->resolveView());
    }

    public function testResolveViewSingularCpt(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_singular' => true, 'singular_type' => 'book'];
        $this->assertSame('pages/single', $this->service()->resolveView());
    }

    public function testResolveViewFallbackIs404(): void
    {
        $GLOBALS['wp_test_flags'] = [];
        $this->assertSame('pages/404', $this->service()->resolveView());
    }

    public function testResolveViewCategoryArchive(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_archive' => true, 'is_category' => true];
        $this->assertSame('pages/archive', $this->service()->resolveView());
    }

    public function testResolveViewFilterOverride(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_singular' => true, 'singular_type' => 'book'];
        add_filter('wpfasty_resolve_view', static fn (string $view): string => 'pages/custom-book');
        $this->assertSame('pages/custom-book', $this->service()->resolveView());
    }
}
