<?php
declare(strict_types=1);

/**
 * Abstract base class for service providers
 * Provides common functionality and implements ServiceProvider interface
 */

namespace FastyChild\Core;

use FastyChild\Core\Traits\LoggerTrait;
use FastyChild\Core\Traits\ContainerAwareTrait;

abstract class AbstractServiceProvider implements ServiceProvider
{
    use LoggerTrait;
    use ContainerAwareTrait;
    
    /**
     * Services provided by this provider
     * @var array<string>
     */
    protected array $provides = [];
    
    /**
     * Constructor
     *
     * @param Container $container Service container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Register services in the container
     * 
     * @return void
     */
    abstract public function register(): void;
    
    /**
     * Boot services after all providers are registered
     * Default implementation does nothing
     * 
     * @return void
     */
    public function boot(): void
    {
        // Default implementation does nothing
    }
    
    /**
     * Get the services provided by the provider
     * Used for deferred loading
     * 
     * @return array<string> Array of service identifiers
     */
    public function provides(): array
    {
        return $this->provides;
    }
    
    /**
     * Register a singleton binding in the container
     * 
     * @param string $abstract Abstract key
     * @param mixed $concrete Concrete implementation or factory
     * @return void
     */
    protected function singleton(string $abstract, $concrete): void
    {
        $this->container->singleton($abstract, $concrete);
        $this->provides[] = $abstract;
        $this->debug(sprintf('Registered singleton: %s', $abstract));
    }
    
    /**
     * Register a binding in the container
     * 
     * @param string $abstract Abstract key
     * @param mixed $concrete Concrete implementation or factory
     * @return void
     */
    protected function bind(string $abstract, $concrete): void
    {
        $this->container->bind($abstract, $concrete);
        $this->provides[] = $abstract;
        $this->debug(sprintf('Registered binding: %s', $abstract));
    }
    
    /**
     * Register a lazy service in the container
     * 
     * @param string $abstract Abstract key
     * @param \Closure $factory Factory function to create the service
     * @param bool $singleton Whether to treat as singleton
     * @return void
     */
    protected function lazy(string $abstract, \Closure $factory, bool $singleton = true): void
    {
        $this->container->lazy($abstract, $factory, $singleton);
        $this->provides[] = $abstract;
        $this->debug(sprintf('Registered lazy service: %s', $abstract));
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $abstract Abstract key
     * @return mixed
     */
    protected function get(string $abstract)
    {
        return $this->container->get($abstract);
    }
    
    /**
     * Check if a service exists in the container
     * 
     * @param string $abstract Abstract key
     * @return bool
     */
    protected function has(string $abstract): bool
    {
        return $this->container->has($abstract);
    }
    
    /**
     * Make an instance of a class with automatic dependency injection
     * 
     * @param string $concrete Class name
     * @param array $parameters Additional constructor parameters
     * @return mixed
     */
    protected function make(string $concrete, array $parameters = [])
    {
        return $this->container->make($concrete, $parameters);
    }
    
    /**
     * Call a method with automatic dependency injection
     * 
     * @param object|string $instance Object instance or class name for static methods
     * @param string $method Method name
     * @param array $parameters Additional method parameters
     * @return mixed
     */
    protected function call($instance, string $method, array $parameters = [])
    {
        return $this->container->call($instance, $method, $parameters);
    }
} 