<?php
namespace WPFasty\Hooks;

abstract class AbstractHooks {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    abstract public function register(): void;

    protected function addAction(string $hook, string $method, int $priority = 10, int $args = 1): void {
        add_action($hook, [$this, $method], $priority, $args);
    }

    protected function addFilter(string $hook, string $method, int $priority = 10, int $args = 1): void {
        add_filter($hook, \Closure::bind([$this, $method], $this), $priority, $args);
    }
} 