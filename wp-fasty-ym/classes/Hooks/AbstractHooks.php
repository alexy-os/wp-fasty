<?php

declare(strict_types=1);

/**
 * Abstract class for hooks
 *
 * @package WPFasty\Hooks
 */

namespace WPFasty\Hooks;

use WPFasty\Core\ContainerInterface;

abstract class AbstractHooks
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    abstract public function register(): void;

    protected function addAction(string $hook, string $method, int $priority = 10, int $args = 1): void
    {
        add_action($hook, [$this, $method], $priority, $args);
    }

    protected function addFilter(string $hook, string $method, int $priority = 10, int $args = 1): void
    {
        add_filter($hook, [$this, $method], $priority, $args);
    }
}
