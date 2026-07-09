<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFasty\Data\ContextFactory;

final class ContextFactoryTest extends TestCase
{
    public function testErrorContextShape(): void
    {
        $ctx = (new ContextFactory())->createErrorContext();
        $this->assertArrayHasKey('site', $ctx);
        $this->assertArrayHasKey('menu', $ctx);
        $this->assertArrayHasKey('shell', $ctx);
        $this->assertArrayHasKey('error', $ctx);
        $this->assertSame(404, $ctx['error']['code']);
        $this->assertSame('Page not found', $ctx['error']['title']);
        $this->assertNotEmpty($ctx['error']['home_url']);
    }

    public function testArchiveSearchContext(): void
    {
        $GLOBALS['wp_test_flags'] = ['is_search' => true, 'search_query' => 'latte'];
        $ctx = (new ContextFactory())->createArchiveContext();
        $this->assertStringContainsString('latte', $ctx['archive']['title']);
        $this->assertSame('latte', $ctx['archive']['search_query']);
        $this->assertArrayHasKey('labels', $ctx);
    }
}
