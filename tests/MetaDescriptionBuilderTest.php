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

    public function testTheLimitIsInCharactersAndInclusive(): void
    {
        $b = new MetaDescriptionBuilder();
        self::assertSame(str_repeat('я', 160), $b->build(str_repeat('я', 160)));
        self::assertSame(str_repeat('a', 159).'…', $b->build(str_repeat('a', 161)));
        self::assertSame(160, mb_strlen($b->build(str_repeat('a', 161))));
    }

    public function testCutAtTheLastSpaceOfTheSecondHalf(): void
    {
        $b = new MetaDescriptionBuilder();
        self::assertSame('aaaa bbbb…', $b->build('aaaa bbbb cccc dddd', 12));
        self::assertSame('яяяя bbbb…', $b->build('яяяя bbbb cccc dddd', 12));
        // a space in the first half (here exactly at max/2) is not a cut point: the word is cut instead
        self::assertSame('aaaaa bbb…', $b->build('aaaaa bbbbbbbbbb', 10));
        self::assertSame('a bbbbbbbbbbbbbbbbb…', $b->build('a bbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', 20));
    }

    public function testTrailingPunctuationIsDroppedBeforeTheEllipsis(): void
    {
        self::assertSame('aaaa bbbb…', (new MetaDescriptionBuilder())->build('aaaa bbbb, cccc dddd', 13));
    }

    public function testMultibyte(): void
    {
        $out = (new MetaDescriptionBuilder())->build(str_repeat('слово ', 40), 30);
        self::assertSame(1, preg_match('//u', $out));
        self::assertStringEndsWith('слово…', $out);
    }
}
