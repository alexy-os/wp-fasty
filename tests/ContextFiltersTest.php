<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Adapter\WordPress\ContextFilters;

final class ContextFiltersTest extends TestCase
{
    public function testFilterResultIsUsed(): void
    {
        add_filter('wpfasty_context', static fn (array $c): array => $c + ['extra' => 1]);

        $this->assertSame(['a' => 1, 'extra' => 1], (new ContextFilters())->context(['a' => 1], null));
    }

    public function testNonArrayFilterResultKeepsTheContext(): void
    {
        add_filter('wpfasty_context', static fn (): string => 'broken');

        $this->assertSame(['a' => 1], (new ContextFilters())->context(['a' => 1], null));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['wp_test_filters']['wpfasty_context']);
    }
}
