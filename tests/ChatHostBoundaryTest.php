<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Chat;

/** Non-live boundary checks: fake host parameters only, no WP configuration or provider calls. */
final class ChatHostBoundaryTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['WPFB_WORKER_PORT'], $_SERVER['WPFB_WORKER_TOKEN'], $_SERVER['WPFB_WORKER_KEY']);
    }

    public function testPortValidationRejectsPartialOrMalformedInput(): void
    {
        foreach ([null, [], '18991extra', '<b>18991</b>', '18%00991', '018991', '1023', '65536'] as $value) {
            self::assertNull(Chat::validPort($value));
        }
        self::assertSame(1024, Chat::validPort('1024'));
        self::assertSame(65535, Chat::validPort(65535));
        $_SERVER['WPFB_WORKER_PORT'] = '23456';
        self::assertSame(23456, Chat::port());
    }

    public function testHostTokenPreservesOpaqueBytes(): void
    {
        $fake = str_repeat("fake\\opaque'", 4);
        $_SERVER['WPFB_WORKER_TOKEN'] = $fake;
        $reader = new \ReflectionMethod(Chat::class, 'hostToken');
        self::assertSame($fake, $reader->invoke(null));
    }

    public function testHostTokenRetainsTypeAndLengthBounds(): void
    {
        $reader = new \ReflectionMethod(Chat::class, 'hostToken');
        foreach ([[], 123, 'short', str_repeat('x', 4097)] as $fake) {
            $_SERVER['WPFB_WORKER_TOKEN'] = $fake;
            self::assertNull($reader->invoke(null));
        }
    }

    public function testHostManagedFlagIsExactAndNeverCoerced(): void
    {
        foreach ([' managed ', 'MANAGED', ['managed'], true, 'managed\\'] as $fake) {
            $_SERVER['WPFB_WORKER_KEY'] = $fake;
            self::assertFalse(Chat::hostManagedKey());
        }
        $_SERVER['WPFB_WORKER_KEY'] = 'managed';
        self::assertTrue(Chat::hostManagedKey());
    }
}
