<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

use WPFasty\Core\ContainerInterface;

abstract class AbstractHooks
{
    public function __construct(
        protected readonly ContainerInterface $container,
    ) {
    }

    abstract public function register(): void;

    protected function addAction(string $hook, string $method, int $priority = 10, int $args = 1): void
    {
        add_action($hook, [$this, $method], $priority, $args);
    }
}
