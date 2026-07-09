<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFasty\Theme\PrimitivesLocator;

final class PrimitivesLocatorTest extends TestCase
{
    public function testResolvesMonorepoPrimitives(): void
    {
        $dir = PrimitivesLocator::resolve(dirname(__DIR__) . '/themes/latte');
        $this->assertNotNull($dir);
        $this->assertDirectoryExists($dir);
        $this->assertStringContainsString('ui8kit-latte', $dir);
    }
}
