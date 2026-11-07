<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFastyBuilder\AdminPalette;
use WPFastyBuilder\ChatTelemetry;

/** NON-LIVE: pure DTO/palette contracts, no configured WordPress or provider. */
final class ChatTelemetryTest extends TestCase
{
    public function testUsageUnknownIsNotZeroAndOnlyActualFiniteIntegerCountsSurvive(): void
    {
        $usage = ['inputTokens' => 100, 'outputTokens' => 20, 'cacheReadTokens' => 5, 'cacheWriteTokens' => 3, 'totalTokens' => 128];
        self::assertSame($usage, ChatTelemetry::usage($usage + ['secret' => 'FIXTURE_ONLY']));
        foreach ([null, [], $usage + ['reasoningTokens' => -1], array_replace($usage, ['inputTokens' => '100']), array_replace($usage, ['totalTokens' => INF])] as $bad) {
            self::assertNull(ChatTelemetry::usage($bad));
        }
        self::assertSame(0, ChatTelemetry::usage(array_fill_keys(array_keys($usage), 0))['totalTokens']);
    }

    public function testProgressDropsArbitraryPayloadAndMismatchedRequestAndCompletedTool(): void
    {
        $value = ['requestId' => 'fixture-request', 'state' => 'pending', 'sequence' => 2, 'tool' => ['name' => 'catalog_lookup', 'status' => 'running', 'args' => 'PRIVATE_FIXTURE'], 'thinking' => 'PRIVATE_FIXTURE'];
        $safe = ChatTelemetry::progress($value, 'fixture-request');
        self::assertSame(['name' => 'catalog_lookup', 'status' => 'running'], $safe['tool']);
        self::assertStringNotContainsString('PRIVATE_FIXTURE', json_encode($safe));
        self::assertSame('unknown', ChatTelemetry::progress($value, 'another-request')['state']);
        self::assertNull(ChatTelemetry::progress(array_replace($value, ['state' => 'done']), 'fixture-request')['tool']);
        $value['tool']['name'] = 'arbitrary_secret_name';
        self::assertNull(ChatTelemetry::progress($value, 'fixture-request')['tool']);
    }

    public function testAdminSchemeCssAllowsOnlyRegisteredHexAndSelectsReadableForeground(): void
    {
        self::assertSame('', AdminPalette::css(['', '', '#fff;}body{display:none']));
        self::assertSame('', AdminPalette::css(null));
        self::assertStringContainsString('--wpfb-admin-accent:#2271b1', AdminPalette::css(['', '', '#2271b1']));
        self::assertStringContainsString('--wpfb-admin-on-accent:#000000', AdminPalette::css(['', '', '#ffffff']));
        self::assertStringContainsString('--wpfb-admin-on-accent:#ffffff', AdminPalette::css(['', '', '#000000']));
    }
}
