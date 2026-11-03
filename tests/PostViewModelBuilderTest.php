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
use WPFasty\Data\Post\PostViewModelBuilder;

final class PostViewModelBuilderTest extends TestCase
{
    public function testTemplateContractKeysAndOrder(): void
    {
        $vm = (new PostViewModelBuilder())->build(new Post(
            7,
            'post',
            'hello',
            'Hello',
            new TrustedHtml('<p>Hi</p>'),
            'Hi',
            '/hello/',
            'Nov 2',
            '2026-11-02T10:00:00+00:00',
            new Image('/l.jpg', '1024', '768', 'Alt', '/l.jpg 1024w', '(max-width: 1024px) 100vw'),
            null,
            [new Term('category', 'News', 'news', '/c/news/')],
            [],
            new Author(1, 'Ann', '/a/ann/'),
            'post-7 post',
            new TrustedHtml('<nav>1 2</nav>'),
            new PostLink('Older', '/older/'),
            null,
        ));
        self::assertSame([
            'id', 'kind', 'title', 'content', 'slug', 'url', 'excerpt', 'featuredImage', 'thumbnail', 'categories', 'tags',
            'author', 'postClass', 'linkPages', 'prev', 'next', 'mimeType', 'isAttachment', 'date',
        ], array_keys($vm));
        self::assertInstanceOf(TrustedHtml::class, $vm['content']);
        self::assertSame('<p>Hi</p>', (string) $vm['content']);
        self::assertSame([
            'url' => '/l.jpg', 'width' => '1024', 'height' => '768', 'alt' => 'Alt',
            'srcset' => '/l.jpg 1024w', 'sizes' => '(max-width: 1024px) 100vw',
        ], $vm['featuredImage']);
        self::assertNull($vm['thumbnail']);
        self::assertSame([['name' => 'News', 'url' => '/c/news/', 'slug' => 'news']], $vm['categories']);
        self::assertSame(['name' => 'Ann', 'url' => '/a/ann/'], $vm['author']);
        self::assertSame('<nav>1 2</nav>', (string) $vm['linkPages']);
        self::assertSame(['title' => 'Older', 'url' => '/older/'], $vm['prev']);
        self::assertNull($vm['next']);
        self::assertFalse($vm['isAttachment']);
        self::assertSame(['display' => 'Nov 2', 'iso' => '2026-11-02T10:00:00+00:00'], $vm['date']);
    }

    public function testAttachmentWithoutAFileUrlGetsAnEmptyString(): void
    {
        $post = new Post(9, 'attachment', 'pic', 'Pic', TrustedHtml::empty(), '', '/pic/', '', '');
        self::assertFalse($post->singular, 'a post DTO is a list item unless built for the singular view');
        self::assertSame('', (new PostViewModelBuilder())->build($post)['attachmentUrl']);
    }

    public function testAttachmentGetsItsFileUrlLast(): void
    {
        $vm = (new PostViewModelBuilder())->build(new Post(
            9,
            'attachment',
            'pic',
            'Pic',
            TrustedHtml::empty(),
            '',
            '/pic/',
            '',
            '',
            mimeType: 'image/png',
            attachmentUrl: '/pic.png',
        ));
        self::assertTrue($vm['isAttachment']);
        self::assertInstanceOf(TrustedHtml::class, $vm['linkPages']);
        self::assertTrue($vm['linkPages']->isEmpty());
        self::assertSame('attachmentUrl', array_key_last($vm));
        self::assertSame('/pic.png', $vm['attachmentUrl']);
    }
}
