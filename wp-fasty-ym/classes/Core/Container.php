<?php

declare(strict_types=1);

/**
 * Dependency Injection Container for WP FastY Theme
 *
 * This class implements an adapter for Symfony DI Container
 * that maintains compatibility with the original API.
 *
 * @package WPFasty\Core
 */

namespace WPFasty\Core;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use WPFasty\Core\LoggerInterface;

class Container implements ContainerInterface
{
    /**
     * Symfony DI Container instance
     *
     * @var ContainerBuilder
     */
    private $container;

    /**
     * Custom factories storage
     *
     * @var array<string, callable>
     */
    private $factories = [];

    /**
     * Class mapping for services
     *
     * @var array<string, string>
     */
    private $classMap = [];
    
    /**
     * Logger instance
     *
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * Constructor
     *
     * @param LoggerInterface|null $logger Optional logger implementation
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->container = new ContainerBuilder();
        $this->logger = $logger;
    }
    
    /**
     * Set a logger instance
     *
     * @param LoggerInterface $logger Logger implementation
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Bind a service to the container
     *
     * @param string $abstract Name of the service
     * @param callable|object|string $concrete Concrete implementation
     * @param string|null $className The class name of the service (required for Closures)
     */
    public function bind(string $abstract, callable|object|string $concrete, ?string $className = null): void
    {
        if ($concrete instanceof \Closure) {
            // Store the closure in our factories array
            $this->factories[$abstract] = $concrete;
            
            // Create a service definition that will use our factory method
            $definition = new Definition();
            
            // If class name is provided, set it on the definition
            if ($className !== null) {
                $definition->setClass($className);
                $this->classMap[$abstract] = $className;
            }
            
            $definition->setPublic(true);
            // Use special __invoke method as factory
            $definition->setFactory([FactoryHelper::class, 'create']);
            // Pass necessary arguments to the factory method
            $definition->setArguments([$abstract, $this]);
            
            $this->container->setDefinition($abstract, $definition);
        } else {
            $concreteClass = is_object($concrete) ? get_class($concrete) : (string)$concrete;
            
            // If className is provided, override the concrete class
            if ($className !== null) {
                $concreteClass = $className;
            }
            
            $definition = new Definition($concreteClass);
            $definition->setPublic(true);
            
            if (is_object($concrete) && !is_callable($concrete)) {
                // If it's already an instantiated object, set it directly
                $this->container->set($abstract, $concrete);
            } else {
                $this->container->setDefinition($abstract, $definition);
            }
        }
    }

    /**
     * Bind a service to the container as a singleton
     *
     * @param string $abstract Name of the service
     * @param callable $concrete Factory function to create the concrete implementation
     * @param string|null $className The class name of the service (required for Closures)
     */
    public function singleton(string $abstract, callable $concrete, ?string $className = null): void
    {
        if ($concrete instanceof \Closure) {
            // Store the closure in our factories array
            $this->factories[$abstract] = $concrete;
            
            // Create a service definition that will use our factory method
            $definition = new Definition();
            
            // If class name is provided, set it on the definition
            if ($className !== null) {
                $definition->setClass($className);
                $this->classMap[$abstract] = $className;
            }
            
            $definition->setPublic(true);
            // Make it a shared service (singleton)
            $definition->setShared(true);
            // Use special __invoke method as factory
            $definition->setFactory([FactoryHelper::class, 'create']);
            // Pass necessary arguments to the factory method
            $definition->setArguments([$abstract, $this]);
            
            $this->container->setDefinition($abstract, $definition);
        }
    }

    /**
     * Get a factory for abstract name
     *
     * @param string $abstract Service name
     * @return callable|null Factory function or null if not found
     */
    public function getFactory(string $abstract): ?callable
    {
        return $this->factories[$abstract] ?? null;
    }

    /**
     * Get a service from the container
     *
     * @param string $abstract Name of the service
     * @return object|callable|string The resolved service
     * @throws \Exception When no binding is found
     */
    public function get(string $abstract): object|callable|string
    {
        if (!$this->container->has($abstract)) {
            throw new \Exception("No binding found for {$abstract}");
        }

        return $this->container->get($abstract);
    }

    /**
     * Compile the container for optimized performance
     *
     * Note: This is a no-op if symfony/config is not installed,
     * but services will still work correctly
     */
    public function compile(): void
    {
        try {
            $this->container->compile();
        } catch (\Error $e) {
            // If compile fails due to missing dependencies, log the error but continue
            $this->logWarning('Container compilation failed: ' . $e->getMessage());
            $this->logWarning('Container will work but without optimization.');
        }
    }
    
    /**
     * Log a warning message
     *
     * @param string $message Warning message
     * @param array<string, mixed> $context Additional context data
     */
    private function logWarning(string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            $this->logger->warning($message, $context);
        }
    }

    /**
     * Get the class for an abstract service
     *
     * @param string $abstract Service name
     * @return string|null Class name or null if not found
     */
    public function getServiceClass(string $abstract): ?string
    {
        return $this->classMap[$abstract] ?? null;
    }

    /**
     * Get IDs of all registered services
     *
     * @return array<string> Array of service IDs
     */
    public function getServiceIds(): array
    {
        return array_keys($this->container->getDefinitions());
    }

    /**
     * Check if service has a specific tag
     *
     * @param string $serviceId Service ID
     * @param string $tag Tag name
     * @return bool True if service has the specified tag
     */
    public function hasTag(string $serviceId, string $tag): bool
    {
        if (!$this->container->hasDefinition($serviceId)) {
            return false;
        }
        
        $definition = $this->container->getDefinition($serviceId);
        return $definition->hasTag($tag);
    }
    
    /**
     * Add a tag to a service
     *
     * @param string $serviceId Service ID
     * @param string $tag Tag name
     * @param array<string, mixed> $attributes Tag attributes
     */
    public function addTag(string $serviceId, string $tag, array $attributes = []): void
    {
        if (!$this->container->hasDefinition($serviceId)) {
            return;
        }
        
        $definition = $this->container->getDefinition($serviceId);
        $definition->addTag($tag, $attributes);
    }
    
    /**
     * Find all services with a specific tag
     *
     * @param string $tag Tag name
     * @return array<string> Array of service IDs
     */
    public function findTaggedServiceIds(string $tag): array
    {
        return array_keys($this->container->findTaggedServiceIds($tag));
    }

    /**
     * Get the underlying Symfony container
     *
     */
    public function getSymfonyContainer(): ContainerBuilder
    {
        return $this->container;
    }
}
