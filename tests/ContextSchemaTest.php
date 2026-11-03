<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Core\Html\TrustedHtml;
use WPFasty\Core\Model\Author;
use WPFasty\Core\Model\Image;
use WPFasty\Core\Model\Post;
use WPFasty\Core\Model\PostLink;
use WPFasty\Core\Model\Term;
use WPFasty\Data\ContextFactory;
use WPFasty\Data\Post\PostViewModelBuilder;

require_once \dirname(__DIR__).'/tools/qa/context-schema.php';

/**
 * The template context against schema/theme-context.schema.json (the view contract; post fields named after the
 * Codex v0.4 entry). Live WordPress contexts are validated in CI by the HTML snapshot job (tools/qa/snapshots.sh).
 */
final class ContextSchemaTest extends TestCase
{
    public function testPostViewModelMatchesTheSchema(): void
    {
        $b = new PostViewModelBuilder();
        $full = new Post(
            id: 7,
            kind: 'post',
            slug: 'hello',
            title: 'Hello',
            content: new TrustedHtml('<p>Hi</p>'),
            excerpt: 'Hi',
            url: '/hello/',
            dateDisplay: 'Nov 2',
            dateIso: '2026-11-02T10:00:00+00:00',
            featuredImage: new Image('/l.jpg', '1024', '768', 'Alt', '', ''),
            categories: [new Term('category', 'News', 'news', '/c/news/')],
            author: new Author(1, 'Ann', '/a/ann/'),
            linkPages: new TrustedHtml(''),
            previous: new PostLink('Older', '/older/'),
        );
        self::assertSame([], wpfasty_context_schema_errors($b->build($full), 'post'));
        $attachment = new Post(9, 'attachment', 'pic', 'Pic', TrustedHtml::empty(), '', '/pic/', '', '', attachmentUrl: '/p.png');
        self::assertSame([], wpfasty_context_schema_errors($b->build($attachment), 'post'));
    }

    /** schema/examples/*.json: one context per page type (view/v1), each valid against its root def. */
    public function testExamplesPerPageTypeAreValid(): void
    {
        $files = glob(\dirname(__DIR__).'/schema/examples/*.json') ?: [];
        self::assertGreaterThanOrEqual(19, \count($files));
        foreach ($files as $file) {
            $doc = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($doc);
            self::assertContains($doc['kind'] ?? null, ['page', 'archive', 'error'], basename($file));
            self::assertSame([], wpfasty_context_schema_errors($doc['context'], $doc['kind'].'Context'), basename($file));
        }
    }

    public function testSchemaRejectsABrokenPost(): void
    {
        $vm = (new PostViewModelBuilder())->build(new Post(1, 'post', 's', 'T', TrustedHtml::empty(), '', '/', '', ''));
        unset($vm['url']);
        $vm['title'] = 42;
        $vm['kind'] = 'Not A Kind';
        self::assertNotSame([], wpfasty_context_schema_errors($vm, 'post'));
    }

    public function testFactoryContextsMatchTheSchema(): void
    {
        $GLOBALS['wp_test_menus'] = [
            'locations' => ['primary' => 1],
            'items' => [1 => [
                (object) ['ID' => 10, 'title' => 'Parent', 'url' => '/p', 'menu_item_parent' => 0, 'current' => false],
                (object) ['ID' => 11, 'title' => 'Child', 'url' => '/c', 'menu_item_parent' => 10, 'current' => true],
            ]],
        ];
        $factory = self::contextFactory();
        self::assertSame([], wpfasty_context_schema_errors($factory->createErrorContext(), 'errorContext'));
        self::assertSame([], wpfasty_context_schema_errors($factory->createArchiveContext(), 'archiveContext'));
        unset($GLOBALS['wp_test_menus']);
    }

    /** Frozen live contexts of the Latte/Twig parity suite: every post they carry has the contract keys. */
    public function testParityFixturePostsMatchTheSchema(): void
    {
        $checked = 0;
        foreach (glob(\dirname(__DIR__).'/tests/fixtures/parity/*/*.json') ?: [] as $file) {
            $context = json_decode((string) file_get_contents($file), true)['context'] ?? [];
            $posts = [...(\is_array($context['posts'] ?? null) ? $context['posts'] : [])];
            if (\is_array($context['page'] ?? null)) {
                $posts[] = $context['page'];
            }
            foreach ($posts as $i => $post) {
                if (!\is_array($post) || !isset($post['postClass'], $post['id'])) {
                    continue; // partial block fixtures (escaping cases) and module lists reshaped by `context` callables
                }
                self::assertSame([], wpfasty_context_schema_errors($post, 'post'), basename($file).' #'.$i);
                ++$checked;
            }
        }
        self::assertGreaterThan(0, $checked);
    }

    private static function contextFactory(): ContextFactory
    {
        return (require \dirname(__DIR__).'/themes/latte/configs/context.php')();
    }
}
