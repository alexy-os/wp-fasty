<?php

declare(strict_types=1);

namespace WPFasty\Core\Port;

use WPFasty\Core\Model\MenuItem;

/** Nav menus assigned to theme locations. */
interface MenuProviderInterface
{
    /**
     * Flat items (menu order) of every assigned location; [] when no location is assigned.
     *
     * @return array<string, list<MenuItem>>
     */
    public function itemsByLocation(): array;
}
