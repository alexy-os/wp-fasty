<?php

declare(strict_types=1);

namespace WPFasty\Core;

/**
 * Main application class
 * This class is the entry point of the application.
 * It initializes the container and boots the services.
 */
class Application
{
    /**
     * Singleton instance
     * @var self|null
     */
    private static $instance = null;
    /**
     * Service container
     * @var ContainerInterface
     */
    private $container;

    /**
     * Private constructor to prevent direct creation
     */
    private function __construct()
    {
        // Initialize container
        $this->container = new Container();

        // Load service configuration
        $this->loadServices();

        // Boot services
        $this->bootServices();
    }

    /**
     * Get the singleton instance
     * @return self Application instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load services from configuration
     */
    private function loadServices(): void
    {
        $servicesFile = dirname(dirname(__DIR__)) . '/configs/services.php';

        if (!file_exists($servicesFile)) {
            throw new \RuntimeException('Services configuration file not found');
        }

        $serviceConfigurator = require $servicesFile;

        if (!is_callable($serviceConfigurator)) {
            throw new \RuntimeException('Services configuration must return a callable');
        }

        $serviceConfigurator($this->container);
    }

    /**
     * Boot all registered services
     */
    private function bootServices(): void
    {
        $this->container->bootServices();
    }

    /**
     * Get the service container
     * @return ContainerInterface The service container
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get a service from the container
     * @param string $serviceId Service ID.
     * @return mixed The service instance
     */
    public function getService(string $serviceId)
    {
        return $this->container->get($serviceId);
    }
}
