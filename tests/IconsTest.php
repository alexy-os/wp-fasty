<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFasty\Template\Icons;

final class IconsTest extends TestCase
{
    public function testRendersLucideSvgWithClassAndAriaHidden(): void
    {
        $svg = Icons::svg('shopping-cart', 'size-6');
        $this->assertStringStartsWith('<svg class="lucide lucide-shopping-cart size-6" aria-hidden="true"', $svg);
    }

    public function testLabelMakesIconAccessibleAndIsEscaped(): void
    {
        $svg = Icons::svg('x', 'size-4', 'Close "x"');
        $this->assertStringContainsString('role="img" aria-label="Close &quot;x&quot;"', $svg);
    }

    public function testUnknownOrHostileNameReturnsEmpty(): void
    {
        $this->assertSame('', Icons::svg('../../wp-config'));
        $this->assertSame('', Icons::svg('does-not-exist'));
    }
}
