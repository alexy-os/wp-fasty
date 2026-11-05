<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFastyTypes\Codex\Mapper;
use WPFastyTypes\Codex\Rest;
use WPFastyTypes\Codex\Site;

require_once dirname(__DIR__).'/plugins/wpfasty-types/src/Codex/autoload.php';

/**
 * GET /wpfasty/v1/codex/settings is intentionally open; the payload is clipped to Mapper::SETTINGS_PUBLIC_KEYS.
 */
final class CodexSettingsPublicTest extends TestCase
{
    /** Keys that must never appear on the public settings allowlist. */
    private const FORBIDDEN = [
        'admin_email', 'auth_key', 'secure_auth_key', 'logged_in_key', 'nonce_key',
        'secret', 'password', 'api_key', 'private_key', 'users_can_register',
        'default_role', 'mailserver_login', 'mailserver_pass', 'smtp_pass',
    ];

    public function testAllowlistHasNoSecretOrAdminKeys(): void
    {
        foreach (Mapper::SETTINGS_PUBLIC_KEYS as $key) {
            self::assertNotContains($key, self::FORBIDDEN, "$key must not be public");
            self::assertDoesNotMatchRegularExpression('/(secret|password|token|key|salt|private)/i', $key);
        }
        self::assertContains('title', Mapper::SETTINGS_PUBLIC_KEYS);
        self::assertContains('contact', Mapper::SETTINGS_PUBLIC_KEYS);
        self::assertSame(
            Mapper::SETTINGS_PUBLIC_KEYS,
            array_values(array_unique(Mapper::SETTINGS_PUBLIC_KEYS)),
            'allowlist must not duplicate keys',
        );
    }

    public function testSettingsPayloadIsClippedToAllowlist(): void
    {
        $GLOBALS['wp_test_options'] = [
            'blogname' => 'Demo Shop',
            'blogdescription' => 'Public tagline',
            'timezone_string' => 'UTC',
            'show_on_front' => 'posts',
            'page_on_front' => 0,
            'page_for_posts' => 0,
            'posts_per_page' => 10,
            'default_comment_status' => 'open',
            'admin_email' => 'should-not-leak@example.com',
            'wpfasty_currency' => 'USD',
        ];
        $site = Site::fromDir(dirname(__DIR__).'/themes/latte-shop/types');
        self::assertNotNull($site);
        $out = (new Mapper($site))->settings();
        unset($GLOBALS['wp_test_options']);

        self::assertSame([], array_diff(array_keys($out), Mapper::SETTINGS_PUBLIC_KEYS));
        self::assertArrayNotHasKey('admin_email', $out);
        self::assertSame('Demo Shop', $out['title']);
        self::assertSame('Public tagline', $out['description']);
        self::assertTrue($out['comments_open']);
        self::assertSame('USD', $out['currency'] ?? null);
    }

    public function testIntersectDropsUnknownKeys(): void
    {
        $leaky = [
            'title' => 'ok',
            'admin_email' => 'nope@example.com',
            'auth_key' => 'secret',
            'contact' => ['email' => 'shop@example.com'],
        ];
        $clipped = array_intersect_key($leaky, array_flip(Mapper::SETTINGS_PUBLIC_KEYS));
        self::assertSame(['title' => 'ok', 'contact' => ['email' => 'shop@example.com']], $clipped);
    }

    public function testSettingsRoutesStayIntentionallyOpen(): void
    {
        $src = (string) file_get_contents(dirname(__DIR__).'/plugins/wpfasty-types/src/Codex/Rest.php');
        self::assertStringContainsString("permission_callback' => '__return_true'", $src);
        self::assertStringContainsString('SETTINGS_PUBLIC_KEYS', $src);
        self::assertStringContainsString("'/settings'", $src);
        // builder REST must keep refusing anonymous — regression pointer
        $builder = (string) file_get_contents(dirname(__DIR__).'/plugins/wpfasty-builder/src/Rest.php');
        self::assertStringNotContainsString("'__return_true'", $builder);
    }
}
