<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Hooks\ThemeHooks;

/**
 * Core markup filters that keep theme renders free of html-validate / axe findings.
 */
final class MarkupHooksTest extends TestCase
{
    private static function hooks(): ThemeHooks
    {
        return (new \ReflectionClass(ThemeHooks::class))->newInstanceWithoutConstructor();
    }

    public function testSearchBlockGetsUniqueLandmarkLabel(): void
    {
        $hooks = self::hooks();
        $out = $hooks->labelSearchBlock('<form role="search" method="get" action="/"><input name="s"></form>');
        self::assertStringContainsString('<form role="search" aria-label="Sidebar search" method="get"', $out);
        self::assertSame($out, $hooks->labelSearchBlock($out));
    }

    public function testArchiveLinksAreDoubleQuoted(): void
    {
        self::assertSame(
            "\t<li><a href=\"https://example.test/2026/10/\">October 2026</a></li>\n",
            self::hooks()->quoteHref("\t<li><a href='https://example.test/2026/10/'>October 2026</a></li>\n")
        );
    }

    public function testImageOnlyAttachmentLinkUntitledIsUnchangedButQuoted(): void
    {
        $out = self::hooks()->attachmentLink("<a href='https://example.test/a.jpg'><img src=\"a.jpg\" alt=\"\"></a>", 0);
        self::assertStringStartsWith('<a ', $out);
        self::assertStringNotContainsString("href='", $out);
    }
}
