<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyTypes\Codex\Canonical;
use WPFastyTypes\Codex\Mapper;
use WPFastyTypes\Codex\Site;

/**
 * The PHP Mapper over the LIVE /wp/v2 samples of ui8kit/contract (fixtures/wordpress at a70c243, copied to
 * tests/fixtures/codex/wordpress): each context=edit item is loaded into WordPress objects (title.raw → post_title,
 * description.raw → post_content, caption.raw → post_excerpt: WordPress stores raw), mapped, and its canonical bytes must
 * equal the contract adapter's (expected.json, tools/codex/wp-samples-expected.ts). context=view has no raw / edit-only
 * fields: an export from it is refused. Separate processes: the WordPress stubs here must not leak into other tests.
 */
#[RunTestsInSeparateProcesses]
final class CodexWpSamplesTest extends TestCase
{
    private const DIR = __DIR__ . '/fixtures/codex/wordpress';

    /** @return list<array<string, mixed>> */
    private static function sample(string $name): array
    {
        return json_decode((string) file_get_contents(self::DIR . "/{$name}.json"), true);
    }

    /** @param array<string, mixed> $item */
    private static function raw(array $item, string $field): string
    {
        if (!is_array($item[$field] ?? null) || !array_key_exists('raw', $item[$field])) {
            throw new InvalidArgumentException("{$field}.raw missing: Codex export needs context=edit");
        }
        return (string) $item[$field]['raw'];
    }

    private static function mysql(string $iso): string
    {
        return substr(str_replace('T', ' ', $iso), 0, 19);
    }

    /** /wp/v2/media item (context=edit) → the WP_Post + attachment data WordPress holds for it. */
    private static function attachment(array $m): WP_Post
    {
        $d = (array) ($m['media_details'] ?? []);
        $GLOBALS['wpfasty_wp']['att'][$m['id']] = [
            'url' => $m['source_url'], 'alt' => $m['alt_text'] ?? '',
            'meta' => array_intersect_key($d, ['width' => 1, 'height' => 1, 'filesize' => 1]),
        ];
        return new WP_Post([
            'ID' => $m['id'], 'post_type' => 'attachment', 'post_status' => $m['status'], 'post_name' => $m['slug'],
            'post_title' => self::raw($m, 'title'), 'post_content' => self::raw($m, 'description'),
            'post_excerpt' => self::raw($m, 'caption'), 'post_mime_type' => $m['mime_type'],
            'post_parent' => (int) ($m['post'] ?? 0), 'post_author' => (int) ($m['author'] ?? 0),
            'post_date_gmt' => self::mysql($m['date_gmt']), 'post_modified_gmt' => self::mysql($m['modified_gmt']),
            'comment_status' => $m['comment_status'], 'ping_status' => $m['ping_status'],
        ]);
    }

    /** /wp/v2/users item (context=edit: username, roles, registered_date) → WP_User. */
    private static function user(array $u): WP_User
    {
        foreach (['username', 'roles', 'registered_date'] as $k) {
            if (!array_key_exists($k, $u)) {
                throw new InvalidArgumentException("{$k} missing: Codex export needs context=edit");
            }
        }
        $GLOBALS['wpfasty_wp']['user_meta'][$u['id']]['description'] = $u['description'];
        foreach ((array) ($u['meta'] ?? []) as $k => $v) {
            $GLOBALS['wpfasty_wp']['registered']['user'][$k] = ['show_in_rest' => true, 'single' => true];
            $GLOBALS['wpfasty_wp']['meta']['user'][$u['id']][$k] = $v;
        }
        return new WP_User([
            'ID' => $u['id'], 'user_login' => $u['username'], 'user_nicename' => $u['slug'], 'user_url' => $u['url'],
            'user_registered' => self::mysql($u['registered_date']), 'display_name' => $u['name'],
            'roles' => $u['roles'],
        ]);
    }

    private static function mapper(): Mapper
    {
        require_once dirname(__DIR__) . '/plugins/wpfasty-types/src/Codex/autoload.php';
        require_once __DIR__ . '/fixtures/wp-objects-stubs.php';
        $GLOBALS['wpfasty_wp']['posts'][10] = 'product'; // owner of media 11 (linen-apron)
        return new Mapper(Site::fromDir(dirname(__DIR__) . '/themes/latte-shop/types'));
    }

    /** @return array<string, list<string>> */
    private static function expected(): array
    {
        return json_decode((string) file_get_contents(self::DIR . '/expected.json'), true);
    }

    public function testMediaEditMatchesContractAdapter(): void
    {
        $map = self::mapper();
        $got = array_map(static fn (array $m): string => Canonical::entryBytes($map->media(self::attachment($m))), self::sample('media.edit'));
        self::assertSame(self::expected()['media'], $got);
    }

    public function testUsersEditMatchesContractAdapter(): void
    {
        $map = self::mapper();
        $got = array_map(static fn (array $u): string => Canonical::entryBytes($map->user(self::user($u))), self::sample('users.edit'));
        self::assertSame(self::expected()['users'], $got);
    }

    public function testMediaViewIsRefused(): void
    {
        self::mapper();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('title.raw missing: Codex export needs context=edit');
        self::attachment(self::sample('media.view')[0]);
    }

    public function testUsersViewIsRefused(): void
    {
        self::mapper();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('username missing: Codex export needs context=edit');
        self::user(self::sample('users.view')[0]);
    }

    /** persisted_preferences (editor UI state) is dropped even when it has a value; other REST meta is kept. */
    public function testPersistedPreferencesNeverInMetadata(): void
    {
        $map = self::mapper();
        $u = self::sample('users.edit')[0];
        $u['meta'] = ['persisted_preferences' => ['core' => ['fixedToolbar' => true]], 'demo_badge' => 'gold'];
        $e = $map->user(self::user($u));
        self::assertArrayNotHasKey('persisted_preferences', $e['metadata']);
        self::assertSame(['value' => 'gold'], $e['metadata']['demo_badge']);
        self::assertStringNotContainsString('persisted_preferences', Canonical::entryBytes($e));
    }
}
