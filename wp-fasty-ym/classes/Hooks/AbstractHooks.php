<?php

declare(strict_types=1);

namespace WPFasty\Hooks;

use WPFasty\Core\ContainerInterface;

abstract class AbstractHooks
{
    /**
     * Service container
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Constructor
     *
     * @param ContainerInterface $container Service container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
