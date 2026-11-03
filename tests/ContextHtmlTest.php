<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Core\Html\ContextHtml;
use WPFasty\Core\Html\TrustedHtml;

final class ContextHtmlTest extends TestCase
{
    public function testTrustMarksOnlyTheHtmlFields(): void
    {
        $ctx = ContextHtml::trust([
            'page' => ['content' => '<p>a</p>', 'linkPages' => '', 'title' => '<b>'],
            'posts' => [['content' => '<p>b</p>', 'excerpt' => '<i>'], 'not-a-post'],
            'comments' => '<ol></ol>',
            'widgets' => ['sidebar' => '<aside>', 'footer' => '', 'other' => '<x>'],
            'archive' => ['description' => '<p>d</p>'],
        ]);
        self::assertInstanceOf(TrustedHtml::class, $ctx['page']['content']);
        self::assertSame('<p>a</p>', (string) $ctx['page']['content']);
        self::assertInstanceOf(TrustedHtml::class, $ctx['page']['linkPages']);
        self::assertSame('<b>', $ctx['page']['title']);
        self::assertInstanceOf(TrustedHtml::class, $ctx['posts'][0]['content']);
        self::assertSame('<i>', $ctx['posts'][0]['excerpt']);
        self::assertSame('not-a-post', $ctx['posts'][1]);
        self::assertInstanceOf(TrustedHtml::class, $ctx['comments']);
        self::assertInstanceOf(TrustedHtml::class, $ctx['widgets']['sidebar']);
        self::assertInstanceOf(TrustedHtml::class, $ctx['widgets']['footer']);
        self::assertSame('<x>', $ctx['widgets']['other']);
        self::assertSame('<p>d</p>', $ctx['archive']['description']);
    }

    public function testTrustLeavesMissingAndNonStringValues(): void
    {
        $t = new TrustedHtml('<p>');
        self::assertSame([], ContextHtml::trust([]));
        self::assertSame(['page' => null, 'widgets' => 'x', 'posts' => 'y'], ContextHtml::trust(['page' => null, 'widgets' => 'x', 'posts' => 'y']));
        self::assertSame(['comments' => $t, 'page' => ['content' => $t]], ContextHtml::trust(['comments' => $t, 'page' => ['content' => $t]]));
        self::assertSame(['comments' => null], ContextHtml::trust(['comments' => null]));
        self::assertSame(['widgets' => []], ContextHtml::trust(['widgets' => []]));
    }

    public function testUnwrapAtAnyDepthAndEmptyBecomesEmptyString(): void
    {
        $raw = static fn (string $h): \ArrayObject => new \ArrayObject([$h]);
        $out = ContextHtml::unwrap([
            'a' => new TrustedHtml('<p>'),
            'b' => ['c' => ['d' => new TrustedHtml('<i>')]],
            'e' => TrustedHtml::empty(),
            'f' => '<s>',
            7 => 1,
        ], $raw);
        self::assertEquals(new \ArrayObject(['<p>']), $out['a']);
        self::assertEquals(new \ArrayObject(['<i>']), $out['b']['c']['d']);
        self::assertSame('', $out['e']);
        self::assertSame('<s>', $out['f']);
        self::assertSame(1, $out[7]);
    }

    public function testJsonGivesThePlainString(): void
    {
        self::assertSame('{"c":"<p>a<\/p>"}', json_encode(['c' => new TrustedHtml('<p>a</p>')]));
    }
}
