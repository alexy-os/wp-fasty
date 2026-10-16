<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFasty\Types\Field;
use WPFasty\Types\PostType;
use WPFasty\Types\Registry;

final class TypesTest extends TestCase
{
    private function registry(): Registry
    {
        $r = new Registry([__DIR__ . '/fixtures/types']);
        $r->load();
        return $r;
    }

    public function testModuleLoadsFromDirectory(): void
    {
        $book = $this->registry()->get('book');
        self::assertInstanceOf(PostType::class, $book);
        self::assertSame(['isbn', 'price', 'pages', 'site', 'signed'], array_keys($book->fields));
        self::assertSame('_lib_isbn', $book->fields['isbn']->metaKey);
        self::assertSame('pages/book', $book->view('single'));
        self::assertSame(['genre'], array_keys($book->taxonomies()));
    }

    public function testCapabilities(): void
    {
        $r = $this->registry();
        self::assertTrue($r->supports('book', 'purchasable'));
        self::assertFalse($r->supports('book', 'orderable'));
        self::assertFalse($r->supports('post', 'purchasable'));
        self::assertSame(['price_field' => 'price'], $r->get('book')->capability('purchasable'));
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function sanitizeCases(): iterable
    {
        yield 'money comma' => ['money', '12,345', '12.35'];
        yield 'money negative' => ['money', '-5', '0'];
        yield 'money junk' => ['money', 'abc', ''];
        yield 'integer' => ['integer', '42.9', '42'];
        yield 'boolean on' => ['boolean', 'on', '1'];
        yield 'boolean off' => ['boolean', '0', ''];
        yield 'url invalid' => ['url', 'javascript-ish', ''];
        yield 'string tags' => ['string', '<b>x</b>', 'x'];
    }

    /** @dataProvider sanitizeCases */
    public function testFieldSanitize(string $type, string $raw, string $expected): void
    {
        self::assertSame($expected, Field::fromArray('f', ['type' => $type], 'p')->sanitize($raw));
    }

    public function testRejectsInvalidDefinitions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PostType::fromArray(['name' => 'Bad Name']);
    }

    public function testRejectsUnknownFieldType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Field::fromArray('x', ['type' => 'color'], 'p');
    }
}
