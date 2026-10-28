<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Admin;

/**
 * The Sources screen notice after an import (Admin::notice / takeNotice): kept per user on the server, shown once,
 * escaped; nothing from the URL reaches the screen (it used to print ?wpfb_msg=… — any text, on a link).
 */
#[RunTestsInSeparateProcesses]
final class AdminNoticeTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Admin.php';
        if (!function_exists('set_transient')) {
            eval('function set_transient(string $k, mixed $v, int $ttl = 0): bool { $GLOBALS["wp_test_transients"][$k] = [$v, $ttl]; return true; }
                function get_transient(string $k): mixed { return $GLOBALS["wp_test_transients"][$k][0] ?? false; }
                function delete_transient(string $k): bool { unset($GLOBALS["wp_test_transients"][$k]); return true; }
                function get_current_user_id(): int { return (int) ($GLOBALS["wp_test_user"] ?? 0); }');
        }
        $GLOBALS['wp_test_transients'] = [];
        $GLOBALS['wp_test_user'] = 3;
    }

    public function testNoticeIsPerUserShownOnceAndEscaped(): void
    {
        Admin::notice('error', 'Bad <img src=x onerror=alert(1)> registry');
        self::assertSame(300, $GLOBALS['wp_test_transients']['wpfb_sources_notice_3'][1]);
        $GLOBALS['wp_test_user'] = 4;
        self::assertSame('', Admin::takeNotice(), 'another user does not see it');
        $GLOBALS['wp_test_user'] = 3;
        self::assertSame(
            '<div class="notice notice-error"><p>Bad &lt;img src=x onerror=alert(1)&gt; registry</p></div>',
            Admin::takeNotice(),
        );
        self::assertSame('', Admin::takeNotice(), 'shown once');
        Admin::notice('<script>', 'Imported 3 blocks and 1 collections.');
        self::assertStringStartsWith('<div class="notice notice-success">', Admin::takeNotice(), 'type from an allowlist');
    }

    public function testNothingFromTheUrlReachesTheScreen(): void
    {
        $_GET['wpfb_msg'] = 'Your site is hacked, call +1 555';
        self::assertSame('', Admin::takeNotice());
        $src = (string) file_get_contents(dirname(__DIR__) . '/plugins/wpfasty-builder/src/Admin.php');
        self::assertStringNotContainsString('wpfb_msg', $src, 'the import result is not passed in the URL');
    }
}
