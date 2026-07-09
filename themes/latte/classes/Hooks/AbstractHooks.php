<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

use WPFasty\Core\BootableServiceInterface;
use WPFasty\Core\ContainerInterface;

abstract class AbstractHooks implements BootableServiceInterface
{
    public function __construct(
        protected readonly ContainerInterface $container,
    ) {
    }

    public function boot(): void
    {
        $this->register();
    }

    abstract public function register(): void;

    protected function addAction(string $hook, string $method, int $priority = 10, int $args = 1): void
    {
        add_action($hook, [$this, $method], $priority, $args);
    }
}
