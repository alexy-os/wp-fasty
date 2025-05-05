<?php

declare(strict_types=1);

/**
 * Interface for bootable services
 *
 * @package WPFasty\Core
 */

namespace WPFasty\Core;

/**
 * Interface for services that need initialization during application boot
 */
interface BootableServiceInterface
{
    /**
     * Boot the service
     */
    public function boot(): void;
}
