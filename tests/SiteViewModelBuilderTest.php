<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Core\Html\TrustedHtml;
use WPFasty\Core\Model\Logo;
use WPFasty\Core\Model\SiteInfo;
use WPFasty\Data\Site\SiteViewModelBuilder;

final class SiteViewModelBuilderTest extends TestCase
{
    public function testSiteWithLogo(): void
    {
        $site = (new SiteViewModelBuilder())->site($this->site(new Logo('/l.png', 10, 20, 'Logo')), '2026');
        self::assertSame('Blog', $site['title']);
        self::assertSame('2026', $site['year']);
        self::assertSame(['url' => '/l.png', 'width' => 10, 'height' => 20, 'alt' => 'Logo'], $site['logo']);
        self::assertNull((new SiteViewModelBuilder())->site($this->site(), '2026')['logo']);
    }

    public function testShellAndSheetIds(): void
    {
        $b = new SiteViewModelBuilder();
        self::assertSame('app-shell', $b->shell('app-shell'));
        self::assertSame('classic', $b->shell('other'));
        self::assertSame([
            'trigger' => 'app-shell-mobile-sheet-trigger',
            'panel' => 'app-shell-mobile-sheet-panel',
            'title' => 'app-shell-mobile-sheet-title',
        ], $b->sheetIds('app-shell'));
        self::assertSame('classic-mobile-sheet-title', $b->sheetIds('classic')['title']);
    }

    public function testHeroLinks(): void
    {
        self::assertSame(
            ['primaryLabel' => 'P', 'primaryUrl' => '/blog/', 'secondaryLabel' => 'S', 'secondaryUrl' => '/'],
            (new SiteViewModelBuilder())->hero($this->site(), ['primaryLabel' => 'P', 'secondaryLabel' => 'S']),
        );
    }

    public function testHeroSearchAndErrorUseSiteUrls(): void
    {
        $b = new SiteViewModelBuilder();
        $s = $this->site();
        self::assertSame('/blog/', $b->hero($s, ['primaryLabel' => 'P', 'secondaryLabel' => 'S'])['primaryUrl']);
        self::assertSame('/', $b->search($s, 'q', ['placeholder' => 'x', 'submit' => 'y', 'label' => 'z'])['action']);
        self::assertSame(404, $b->error($s, 404, ['title' => 't', 'message' => 'm', 'homeLabel' => 'h'])['code']);
    }

    public function testTrustedHtmlIsAString(): void
    {
        self::assertSame('<b>x</b>', (string) new TrustedHtml('<b>x</b>'));
        self::assertTrue(TrustedHtml::empty()->isEmpty());
    }

    private function site(?Logo $logo = null): SiteInfo
    {
        return new SiteInfo('Blog', '/', '/t', '/s', 'en', 'D', 'UTF-8', $logo, '/blog/');
    }
}
