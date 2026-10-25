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
        yield 'money negative' => ['money', '-5', '-5'];
        yield 'money junk' => ['money', 'abc', ''];
        yield 'integer' => ['integer', '42.9', '42'];
        yield 'boolean on' => ['boolean', 'on', '1'];
        yield 'boolean off' => ['boolean', '0', ''];
        yield 'url invalid' => ['url', 'javascript-ish', ''];
        yield 'string tags' => ['string', '<b>x</b>', 'x'];
        yield 'date ok' => ['date', '2026-10-19', '2026-10-19'];
        yield 'date iso datetime' => ['date', '2026-10-19T08:00:00Z', '2026-10-19'];
        yield 'date impossible' => ['date', '2026-02-30', ''];
        yield 'date other format' => ['date', '19.10.2026', ''];
        yield 'date junk' => ['date', 'soon', ''];
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

    public function testTypedStorage(): void
    {
        $money = Field::fromArray('price', ['type' => 'money'], 'shop');
        self::assertSame('integer', $money->metaType());
        self::assertSame(1250, $money->fromInput('12.50'));
        self::assertSame(1050, $money->fromInput('10,5'));
        self::assertNull($money->fromInput(''));
        self::assertSame(4500, $money->toStorage(4500));
        self::assertSame(4500, $money->toStorage('4500'));
        // Q9: money may be negative; a non-numeric value is "no value", never 0
        self::assertSame(-1250, $money->toStorage(-1250));
        self::assertSame(-1250, $money->fromInput('-12.50'));
        self::assertNull($money->toStorage(''));
        self::assertNull($money->toStorage('abc'));
        self::assertNull($money->toStorage(null));
        self::assertSame('45.00', $money->toInput(4500));
        self::assertSame('45.00', $money->toInput('4500'));
        self::assertSame('', $money->toInput(''));

        $int = Field::fromArray('stock', ['type' => 'integer'], 'shop');
        self::assertSame('integer', $int->metaType());
        self::assertSame(12, $int->fromInput('12'));
        self::assertNull($int->fromInput(''));
        self::assertNull($int->toStorage(''));
        self::assertSame(0, $int->toStorage(-3));

        $num = Field::fromArray('w', ['type' => 'number'], 'p');
        self::assertSame('number', $num->metaType());
        self::assertSame(1.5, $num->fromInput('1,5'));

        $bool = Field::fromArray('b', ['type' => 'boolean'], 'p');
        self::assertSame('boolean', $bool->metaType());
        self::assertTrue($bool->fromInput('on'));
        self::assertFalse($bool->fromInput(''));
        self::assertTrue($bool->toStorage(true));

        $url = Field::fromArray('url', ['type' => 'url'], 'p');
        self::assertSame('string', $url->metaType());
    }

    public function testDateFieldStorageAndInput(): void
    {
        $date = Field::fromArray('launch', ['type' => 'date'], 'p');
        self::assertSame('_p_launch', $date->metaKey);
        self::assertSame('string', $date->metaType());
        self::assertSame('date', $date->inputType());
        self::assertSame('2026-10-19', $date->fromInput(' 2026-10-19 '));
        self::assertNull($date->fromInput('2026-13-01'));
        self::assertSame('2026-10-19', $date->toStorage('2026-10-19T23:59:00+03:00'));
        self::assertSame('', $date->toStorage('tomorrow'));
        self::assertSame('2026-10-19', $date->toInput('2026-10-19'));
        self::assertSame(1, preg_match('/' . Field::DATE_PATTERN . '/', '2026-10-19'));
    }
}
