<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;
use WPFasty\Core\Model\MenuItem;
use WPFasty\Data\Menu\MenuViewModelBuilder;

final class MenuViewModelBuilderTest extends TestCase
{
    public function testPrimaryAndFooterAlwaysPresent(): void
    {
        self::assertSame(
            ['primary' => ['items' => []], 'footer' => ['items' => []]],
            (new MenuViewModelBuilder())->build([])
        );
    }

    public function testTreeKeepsOrderAndNesting(): void
    {
        $menu = (new MenuViewModelBuilder())->build(['social' => [
            new MenuItem(1, 0, 'A', '/a', false),
            new MenuItem(2, 1, 'A1', '/a1', true),
            new MenuItem(3, 0, 'B', '/b', false),
        ]]);
        self::assertSame([], $menu['primary']['items']);
        self::assertSame(['A', 'B'], array_column($menu['social']['items'], 'title'));
        self::assertSame([
            ['title' => 'A1', 'url' => '/a1', 'id' => 2, 'current' => true, 'children' => []],
        ], $menu['social']['items'][0]['children']);
    }
}
