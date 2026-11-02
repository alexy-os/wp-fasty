<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Core\Cast;

final class CoreCastTest extends TestCase
{
    public function testStringOfScalarsNullStringableAndOthers(): void
    {
        self::assertSame('x', Cast::string('x'));
        self::assertSame('12', Cast::string(12));
        self::assertSame('1.5', Cast::string(1.5));
        self::assertSame('1', Cast::string(true));
        self::assertSame('', Cast::string(false));
        self::assertSame('', Cast::string(null));
        self::assertSame('s', Cast::string(new class implements \Stringable {
            public function __toString(): string
            {
                return 's';
            }
        }));
        self::assertSame('', Cast::string(['a']));
        self::assertSame('', Cast::string(new \stdClass()));
    }

    public function testIntAndFloat(): void
    {
        self::assertSame(7, Cast::int(7));
        self::assertSame(12, Cast::int('12abc'));
        self::assertSame(1, Cast::int(true));
        self::assertSame(0, Cast::int(null));
        self::assertSame(0, Cast::int([5]));
        self::assertSame(2.5, Cast::float('2.5'));
        self::assertSame(0.0, Cast::float(null));
        self::assertSame(0.0, Cast::float([1]));
    }

    public function testMapListAndStrings(): void
    {
        self::assertSame(['0' => 'a', 'k' => 'b'], Cast::map(['a', 'k' => 'b']));
        self::assertSame('0', array_key_first(Cast::map(['a'])).'');
        self::assertSame([], Cast::map('x'));
        self::assertSame(['a', 'b'], Cast::list(['x' => 'a', 'y' => 'b']));
        self::assertSame([], Cast::list(null));
        self::assertSame(['1', '', 'z'], Cast::strings(['k' => 1, 'l' => [], 'm' => 'z']));
    }

    public function testPath(): void
    {
        $data = ['a' => ['b' => ['c' => 0]], 'n' => null];
        self::assertSame(0, Cast::path($data, 'a', 'b', 'c'));
        self::assertSame(['c' => 0], Cast::path($data, 'a', 'b'));
        self::assertNull(Cast::path($data, 'a', 'x'));
        self::assertNull(Cast::path($data, 'a', 'b', 'c', 'd'));
        self::assertNull(Cast::path($data, 'n', 'x'));
        self::assertNull(Cast::path('scalar', 'a'));
        self::assertSame($data, Cast::path($data));
    }
}
