<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFasty\Data\ContextFactory;

final class ContextFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['wp_test_flags'] = [];
        $GLOBALS['wp_test_menus'] = ['locations' => [], 'items' => []];
        $GLOBALS['wp_test_filters'] = [];
        $GLOBALS['wp_test_sidebars'] = [];
    }

    public function testErrorContextShape(): void
    {
        $ctx = (new ContextFactory())->createErrorContext();
        $this->assertArrayHasKey('site', $ctx);
        $this->assertArrayHasKey('menu', $ctx);
        $this->assertArrayHasKey('shell', $ctx);
        $this->assertArrayHasKey('sheet', $ctx);
        $this->assertArrayHasKey('search', $ctx);
        $this->assertArrayHasKey('widgets', $ctx);
        $this->assertArrayHasKey('error', $ctx);
        $this->assertSame(404, $ctx['error']['code']);
        $this->assertSame('Page not found', $ctx['error']['title']);
        $this->assertNotEmpty($ctx['error']['home_url']);
        $this->assertSame('classic-mobile-sheet-panel', $ctx['sheet']['panel']);
    }

    public function testArchiveSearchContext(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_search' => true, 'search_query' => 'latte'];
        $ctx = (new ContextFactory())->createArchiveContext();
        $this->assertStringContainsString('latte', $ctx['archive']['title']);
        $this->assertSame('latte', $ctx['archive']['search_query']);
        $this->assertSame('search', $ctx['archive']['type']);
        $this->assertArrayHasKey('labels', $ctx);
        $this->assertArrayHasKey('footer', $ctx['menu']);
    }

    public function testHierarchicalMenu(): void
    {
        $parent = (object) [
            'ID' => 10,
            'title' => 'Parent',
            'url' => '/parent',
            'menu_item_parent' => 0,
            'current' => false,
        ];
        $child = (object) [
            'ID' => 11,
            'title' => 'Child',
            'url' => '/child',
            'menu_item_parent' => 10,
            'current' => true,
        ];
        $GLOBALS['wp_test_menus'] = [
            'locations' => ['primary' => 1],
            'items' => [1 => [$parent, $child]],
        ];

        $ctx = (new ContextFactory())->createErrorContext();
        $items = $ctx['menu']['primary']['items'];
        $this->assertCount(1, $items);
        $this->assertSame('Parent', $items[0]['title']);
        $this->assertCount(1, $items[0]['children']);
        $this->assertSame('Child', $items[0]['children'][0]['title']);
        $this->assertTrue($items[0]['children'][0]['current']);
    }

    public function testCategoryArchiveType(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_archive' => true, 'is_category' => true];
        $ctx = (new ContextFactory())->createArchiveContext();
        $this->assertSame('category', $ctx['archive']['type']);
    }

    public function testWidgetsHtmlInContext(): void
    {
        $GLOBALS['wp_test_sidebars'] = [
            'sidebar-1' => '<section class="widget">Sidebar</section>',
            'footer-1' => '<section class="widget">Footer</section>',
        ];
        $ctx = (new ContextFactory())->createErrorContext();
        $this->assertStringContainsString('Sidebar', $ctx['widgets']['sidebar']);
        $this->assertStringContainsString('Footer', $ctx['widgets']['footer']);
    }
}
