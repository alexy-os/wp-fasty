<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Data\Seo\MetaDescriptionBuilder;

final class MetaDescriptionBuilderTest extends TestCase
{
    public function testShortTextIsKeptOnOneLine(): void
    {
        self::assertSame('Hello world', (new MetaDescriptionBuilder())->build("  Hello\n\t world "));
    }

    public function testLongTextIsCutAtAWord(): void
    {
        $out = (new MetaDescriptionBuilder())->build(str_repeat('word ', 60), 50);
        self::assertLessThanOrEqual(50, mb_strlen($out));
        self::assertStringEndsWith('word…', $out);
    }

    public function testMultibyte(): void
    {
        $out = (new MetaDescriptionBuilder())->build(str_repeat('слово ', 40), 30);
        self::assertSame(1, preg_match('//u', $out));
        self::assertStringEndsWith('слово…', $out);
    }
}
