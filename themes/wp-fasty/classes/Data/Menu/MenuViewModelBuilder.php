<?php

declare(strict_types=1);

namespace WPFasty\Data\Menu;

use WPFasty\Core\Model\MenuItem;

/** Context `menu`: the item tree of each location; `primary` and `footer` are always present. */
final class MenuViewModelBuilder
{
    /**
     * @param array<string, list<MenuItem>> $itemsByLocation
     *
     * @return array<string, array{items: list<array<string, mixed>>}>
     */
    public function build(array $itemsByLocation): array
    {
        $out = [
            'primary' => ['items' => []],
            'footer' => ['items' => []],
        ];
        foreach ($itemsByLocation as $location => $items) {
            $out[$location] = ['items' => $this->tree($items)];
        }

        return $out;
    }

    /**
     * @param list<MenuItem> $items
     *
     * @return list<array<string, mixed>>
     */
    public function tree(array $items): array
    {
        $byParent = [];
        foreach ($items as $item) {
            $byParent[$item->parentId][] = $item;
        }

        $build = static function (int $parentId) use (&$build, $byParent): array {
            $branch = [];
            foreach ($byParent[$parentId] ?? [] as $item) {
                $branch[] = [
                    'title' => $item->title,
                    'url' => $item->url,
                    'id' => $item->id,
                    'current' => $item->current,
                    'children' => $build($item->id),
                ];
            }

            return $branch;
        };

        return $build(0);
    }
}
