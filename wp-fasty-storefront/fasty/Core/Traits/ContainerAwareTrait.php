<?php
declare(strict_types=1);

namespace FastyChild\Core\Traits;

use FastyChild\Core\Container;
use FastyChild\Core\Application;
use FastyChild\Core\Exceptions\NotFoundException;

/**
 * Provides dependency injection container access to classes
 */
trait ContainerAwareTrait
{
    /**
     * Service container
     * @var Container
     */
    protected Container $container;
    
    /**
     * Set the container
     *
     * @param Container $container Service container
     * @return self
     */
    public function setContainer(Container $container): self
    {
        $this->container = $container;
        return $this;
    }
    
    /**
     * Get the container
     *
     * @return Container
     */
    protected function getContainer(): Container
    {
        return $this->container;
    }
    
    /**
     * Get service from container
     *
     * @param string $id Service identifier
     * @param bool $required Whether the service is required
     * @return mixed Service instance
     * @throws NotFoundException When service not found and required is true
     */
    protected function getService(string $id, bool $required = true)
    {
        if ($this->container->has($id)) {
            return $this->container->get($id);
        }
        
        if ($required) {
            throw new NotFoundException('service', $id, 'Service not registered in container');
        }
        
        return null;
    }
    
    /**
     * Get application instance
     *
     * @return Application
     */
    protected function getApplication(): Application
    {
        return $this->getService('app');
    }
    
    /**
     * Check if a service exists in the container
     *
     * @param string $id Service identifier
     * @return bool True if service exists
     */
    protected function hasService(string $id): bool
    {
        return $this->container->has($id);
    }
    
    /**
     * Create a new instance with automatic dependency injection
     *
     * @param string $class Class name
     * @param array $parameters Additional parameters
     * @return mixed New instance
     */
    protected function makeInstance(string $class, array $parameters = [])
    {
        return $this->container->make($class, $parameters);
    }
    
    /**
     * Call a method with automatic dependency injection
     *
     * @param object|string $instance Object instance or class name for static methods
     * @param string $method Method name
     * @param array $parameters Additional parameters
     * @return mixed Method result
     */
    protected function call($instance, string $method, array $parameters = [])
    {
        return $this->container->call($instance, $method, $parameters);
    }
} 