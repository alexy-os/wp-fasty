<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Model\MenuItem;
use WPFasty\Core\Port\MenuProviderInterface;

/** get_nav_menu_locations() + wp_get_nav_menu_items() as Core menu items. */
final class WordPressMenuProvider implements MenuProviderInterface
{
    public function itemsByLocation(): array
    {
        $out = [];
        foreach (get_nav_menu_locations() as $location => $menuId) {
            $items = wp_get_nav_menu_items($menuId);
            $list = [];
            foreach (is_array($items) ? $items : [] as $item) {
                if (!\is_object($item)) {
                    continue;
                }
                // WP_Post nav items carry wp_setup_nav_menu_item() fields as dynamic properties
                $f = get_object_vars($item);
                $list[] = new MenuItem(
                    \WPFasty\Core\Cast::int($f['ID'] ?? 0),
                    \WPFasty\Core\Cast::int($f['menu_item_parent'] ?? 0),
                    $f['title'] ?? null,
                    $f['url'] ?? null,
                    (bool) ($f['current'] ?? false),
                );
            }
            $out[(string) $location] = $list;
        }

        return $out;
    }
}
