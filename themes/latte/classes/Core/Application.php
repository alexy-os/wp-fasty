<?php

declare(strict_types=1);

namespace WPFasty\Core;

/**
 * Theme bootstrap — loads services and boots tagged hooks.
 */
final class Application
{
    private static ?self $instance = null;

    private function __construct(
        private readonly Container $container,
    ) {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $container = new Container();
            $config = dirname(__DIR__, 2) . '/configs/services.php';
            if (!is_file($config)) {
                throw new \RuntimeException('Missing configs/services.php');
            }
            $register = require $config;
            if (!is_callable($register)) {
                throw new \RuntimeException('services.php must return a callable');
            }
            $register($container);
            $container->bootServices();
            self::$instance = new self($container);
        }

        return self::$instance;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }
}
