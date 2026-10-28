<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Links;

require_once __DIR__ . '/fixtures/links-stubs.php';
require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Links.php';
require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Records.php';

/**
 * Link tokens of builder props are resolved at render time ({home}, {posts}, {page:slug}, {category:slug}, {archive:type}).
 */
final class LinksTest extends TestCase
{
    public function testPlainValuesPassThrough(): void
    {
        $this->assertSame('https://example.com/a', Links::resolve('https://example.com/a'));
        $this->assertSame('', Links::resolve(''));
        $this->assertSame('{unknown} {page:Bad Slug}', Links::resolve('{unknown} {page:Bad Slug}'));
    }

    public function testHomeAndPostsTokens(): void
    {
        $this->assertSame('http://example.test/', Links::resolve('{home}'));
        // No posts page configured: {posts} falls back to home.
        $this->assertSame('http://example.test/#latest', Links::resolve('{posts}#latest'));
    }

    public function testArchiveTokenAndFallbacks(): void
    {
        $this->assertSame('http://example.test/product/?sale=1', Links::resolve('{archive:product}?sale=1'));
        // Missing targets resolve to home instead of a broken link.
        $this->assertSame('http://example.test/', Links::resolve('{archive:nope}'));
        $this->assertSame('http://example.test/#team', Links::resolve('{page:about}#team'));
        $this->assertSame('http://example.test/', Links::resolve('{category:news}'));
    }

    public function testWalkResolvesNestedStringsOnly(): void
    {
        $props = [
            'title' => 'Hello {home}',
            'count' => 3,
            'action' => ['label' => 'All', 'href' => '{archive:product}'],
            'items' => [['href' => '{home}'], ['href' => '/static']],
        ];
        $this->assertSame([
            'title' => 'Hello http://example.test/',
            'count' => 3,
            'action' => ['label' => 'All', 'href' => 'http://example.test/product/'],
            'items' => [['href' => 'http://example.test/'], ['href' => '/static']],
        ], Links::walk($props));
    }
}
