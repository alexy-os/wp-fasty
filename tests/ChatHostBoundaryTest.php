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

    public function testManagedMarkerWithoutHostTokenFailsClosedWithoutDbFallback(): void
    {
        $db = ['worker_token' => str_repeat('d', 40), 'cursor_key' => 'fake-db-cursor-key'];
        $auth = Chat::credentials($db, true, null, true);
        self::assertSame('secret_unavailable', $auth['error'] ?? null);
        self::assertStringContainsString('Vault locked / secret unavailable', $auth['message']);
        self::assertStringNotContainsString('fake-db-cursor-key', json_encode($auth, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString($db['worker_token'], json_encode($auth, JSON_THROW_ON_ERROR));

        $host = str_repeat('h', 40);
        self::assertSame(['token' => $host, 'cursor' => null], Chat::credentials($db, true, $host, true));
    }

    public function testLegacyFallbackOnlyWithoutMarker(): void
    {
        $db = ['worker_token' => str_repeat('d', 40), 'cursor_key' => 'fake-db-cursor-key'];
        self::assertSame(['token' => $db['worker_token'], 'cursor' => 'fake-db-cursor-key'], Chat::credentials($db, false, null, true));
        self::assertSame(['token' => $db['worker_token'], 'cursor' => null], Chat::credentials($db, false, null, false));
        self::assertSame('chat_unconfigured', Chat::credentials([], false, null, false)['error'] ?? null);
        self::assertSame('chat_unconfigured', Chat::credentials(['worker_token' => str_repeat('d', 40)], false, null, true)['error'] ?? null);
    }

    public function testManagedSettingsNeverAcceptOrSaveKeys(): void
    {
        $post = ['cursor_key' => 'fake-new-key', 'worker_token' => str_repeat('n', 40), 'worker_port' => '23456', 'jev_key' => 'fake-jev'];
        $saved = Chat::mergeSettings(['cursor_key' => 'old'], $post, true);
        self::assertSame('old', $saved['cursor_key']);
        self::assertArrayNotHasKey('worker_token', $saved);
        self::assertArrayNotHasKey('worker_port', $saved);
        self::assertSame('fake-jev', $saved['jev_key']);
        $cleared = Chat::mergeSettings(['cursor_key' => 'old', 'worker_token' => 'old'], ['clear_cursor_key' => '1', 'clear_worker_token' => '1'], true);
        self::assertArrayNotHasKey('cursor_key', $cleared);
        self::assertArrayNotHasKey('worker_token', $cleared);
        $manual = Chat::mergeSettings([], $post, false);
        self::assertSame('fake-new-key', $manual['cursor_key']);
        self::assertSame(23456, $manual['worker_port']);
    }

    public function testManagedUiShowsAvailabilityWithoutValuesOrInputs(): void
    {
        $stored = ['cursor_key' => 'fake-stored-cursor-key', 'worker_token' => str_repeat('s', 40)];
        $_SERVER['WPFB_WORKER_TOKEN'] = str_repeat('h', 40);
        foreach ([true, false] as $present) {
            $html = Chat::credentialsHtml($stored, true, $present);
            self::assertStringContainsString('Managed by host', $html);
            self::assertStringContainsString($present ? 'available' : 'unavailable (vault locked)', $html);
            self::assertStringNotContainsString('name="cursor_key"', $html);
            self::assertStringNotContainsString('name="worker_token"', $html);
            self::assertStringNotContainsString('name="worker_port"', $html);
            self::assertStringNotContainsString('fake-stored-cursor-key', $html);
            self::assertStringNotContainsString(str_repeat('s', 40), $html);
            self::assertStringNotContainsString(str_repeat('h', 40), $html);
            self::assertStringContainsString('name="clear_cursor_key"', $html);
        }
        $manual = Chat::credentialsHtml([], false, false);
        self::assertStringContainsString('name="cursor_key"', $manual);
        self::assertStringNotContainsString('Managed by host', $manual);
    }
}
