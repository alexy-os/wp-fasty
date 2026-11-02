<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Core\Model\ArchiveState;
use WPFasty\Core\Port\PageLinkProviderInterface;
use WPFasty\Data\Archive\ArchiveViewModelBuilder;

final class ArchiveViewModelBuilderTest extends TestCase
{
    public function testSearchTitleWinsOnASearch(): void
    {
        $b = new ArchiveViewModelBuilder();
        self::assertSame(
            ['title' => 'Results', 'description' => '', 'searchQuery' => 'q', 'type' => 'search'],
            $b->archive(new ArchiveState('search', 'q', '', '', 1, 1), 'Results')
        );
        self::assertSame('Cat', $b->archive(new ArchiveState('category', null, 'Cat', '<p>d</p>', 1, 1), null)['title']);
    }

    public function testNoPaginationOnOnePageOrWithoutQuery(): void
    {
        $b = new ArchiveViewModelBuilder();
        self::assertNull($b->pagination(new ArchiveState('home', null, '', '', 1, 1), $this->links()));
        self::assertNull($b->pagination(new ArchiveState('home', null, '', '', null, 1), $this->links()));
    }

    public function testPaginationLinks(): void
    {
        $p = (new ArchiveViewModelBuilder())->pagination(new ArchiveState('home', null, '', '', 3, 2), $this->links());
        self::assertSame([
            'current' => 2,
            'total' => 3,
            'prevUrl' => '/page/1/?e',
            'nextUrl' => '/page/3/?e',
            'url' => '/page/{0}/',
            'firstUrl' => '/page/1/',
        ], $p);
    }

    private function links(): PageLinkProviderInterface
    {
        return new class implements PageLinkProviderInterface {
            public function pageLink(int $page, bool $escape = true): string
            {
                return '/page/'.$page.'/'.($escape ? '?e' : '');
            }
        };
    }
}
