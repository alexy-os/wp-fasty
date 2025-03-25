<?php
declare(strict_types=1);

/**
 * Dependency Injection Container
 * Manages service bindings and resolutions
 */

namespace FastyChild\Core;

use FastyChild\Core\Exceptions\NotFoundException;
use FastyChild\Core\Exceptions\ContainerException;
use FastyChild\Core\Traits\LoggerTrait;

class Container {
    use LoggerTrait;
    
    /**
     * Registered bindings
     * @var array<string, array{concrete: mixed, shared: bool}>
     */
    private array $bindings = [];
    
    /**
     * Resolved instances
     * @var array<string, mixed>
     */
    private array $instances = [];
    
    /**
     * Lazy loaded services
     * @var array<string, array{factory: \Closure, singleton: bool}>
     */
    private array $lazyServices = [];
    
    /**
     * Services being resolved (for circular dependency detection)
     * @var array<string, bool>
     */
    private array $resolving = [];
    
    /**
     * Register a binding in the container
     * 
     * @param string $abstract Abstract key
     * @param mixed $concrete Concrete implementation or factory
     * @param bool $shared Whether the binding should be shared (singleton)
     * @return self
     */
    public function bind(string $abstract, $concrete, bool $shared = false): self
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
        
        $this->debug(sprintf('Registered binding: %s (shared: %s)', $abstract, $shared ? 'true' : 'false'));
        
        return $this;
    }
    
    /**
     * Register a singleton binding in the container
     * 
     * @param string $abstract Abstract key
     * @param mixed $concrete Concrete implementation or factory
     * @return self
     */
    public function singleton(string $abstract, $concrete): self
    {
        return $this->bind($abstract, $concrete, true);
    }
    
    /**
     * Register a lazy service that will only be instantiated when needed
     * 
     * @param string $abstract Abstract key
     * @param \Closure $factory Factory function to create the service
     * @param bool $singleton Whether to treat as singleton
     * @return self
     */
    public function lazy(string $abstract, \Closure $factory, bool $singleton = true): self
    {
        $this->lazyServices[$abstract] = [
            'factory' => $factory,
            'singleton' => $singleton
        ];
        
        $this->debug(sprintf('Registered lazy service: %s (singleton: %s)', $abstract, $singleton ? 'true' : 'false'));
        
        return $this;
    }
    
    /**
     * Register an existing instance in the container
     * 
     * @param string $abstract Abstract key
     * @param mixed $instance Concrete instance
     * @return self
     */
    public function instance(string $abstract, $instance): self
    {
        $this->instances[$abstract] = $instance;
        
        $this->debug(sprintf('Registered instance: %s', $abstract));
        
        return $this;
    }
    
    /**
     * Resolve a binding from the container
     * 
     * @param string $abstract Abstract key
     * @return mixed Resolved instance
     * @throws NotFoundException When binding not found
     * @throws ContainerException When circular dependency detected
     */
    public function get(string $abstract)
    {
        // Check if we already have it in instances
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Check for circular dependencies
        if (isset($this->resolving[$abstract])) {
            throw new ContainerException(
                'circular_dependency',
                $abstract,
                'Circular dependency detected'
            );
        }
        
        // Mark as being resolved
        $this->resolving[$abstract] = true;
        
        try {
            // Check for lazy loading services
            if (isset($this->lazyServices[$abstract])) {
                $instance = $this->resolveLazyService($abstract);
                unset($this->resolving[$abstract]);
                return $instance;
            }
            
            // Check if binding exists
            if (!isset($this->bindings[$abstract])) {
                // Try to auto-resolve the class if it exists
                if (class_exists($abstract)) {
                    $this->debug(sprintf('Auto-resolving class: %s', $abstract));
                    $instance = $this->make($abstract);
                    unset($this->resolving[$abstract]);
                    return $instance;
                }
                
                throw new NotFoundException(
                    'binding',
                    $abstract,
                    'No binding found in container'
                );
            }
            
            $binding = $this->bindings[$abstract];
            $concrete = $binding['concrete'];
            
            // If concrete is a closure, execute it
            if ($concrete instanceof \Closure) {
                $instance = $concrete($this);
            } elseif (is_string($concrete) && class_exists($concrete)) {
                // If concrete is a class name, instantiate it
                $instance = $this->make($concrete);
            } else {
                // Plain value binding
                $instance = $concrete;
            }
            
            // Store the result if it's a singleton
            if ($binding['shared']) {
                $this->instances[$abstract] = $instance;
            }
            
            unset($this->resolving[$abstract]);
            return $instance;
        } catch (\Throwable $e) {
            unset($this->resolving[$abstract]);
            throw $e;
        }
    }
    
    /**
     * Resolve a lazy-loaded service
     * 
     * @param string $abstract Service identifier
     * @return mixed The resolved service
     */
    private function resolveLazyService(string $abstract)
    {
        $config = $this->lazyServices[$abstract];
        $factory = $config['factory'];
        
        // Create the instance
        $instance = $factory($this);
        
        // Store if it's a singleton
        if ($config['singleton']) {
            $this->instances[$abstract] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Alias for get()
     * 
     * @param string $abstract Abstract key
     * @return mixed Resolved instance
     */
    public function resolve(string $abstract)
    {
        return $this->get($abstract);
    }
    
    /**
     * Check if a binding exists in the container
     * 
     * @param string $abstract Abstract key
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || 
               isset($this->instances[$abstract]) ||
               isset($this->lazyServices[$abstract]) ||
               class_exists($abstract); // Can auto-resolve class
    }
    
    /**
     * Make an instance of the given class with automatic dependency injection
     * 
     * @param string $concrete Class name to instantiate
     * @param array<string, mixed> $parameters Additional parameters to pass to constructor
     * @return mixed
     * @throws NotFoundException When class not found
     * @throws ContainerException When dependencies cannot be resolved
     */
    public function make(string $concrete, array $parameters = [])
    {
        // Validate class exists
        if (!class_exists($concrete)) {
            throw new NotFoundException(
                'class',
                $concrete,
                'Class does not exist'
            );
        }
        
        $reflection = new \ReflectionClass($concrete);
        
        // If class cannot be instantiated (abstract or interface)
        if (!$reflection->isInstantiable()) {
            throw new ContainerException(
                'not_instantiable',
                $concrete,
                'Class is not instantiable (abstract or interface)'
            );
        }
        
        // If class has no constructor, just create an instance
        if (!$reflection->getConstructor()) {
            return new $concrete;
        }
        
        // Get constructor parameters
        $constructorParams = $reflection->getConstructor()->getParameters();
        $resolvedParams = [];
        
        foreach ($constructorParams as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();
            
            // If parameter is provided externally, use it
            if (isset($parameters[$paramName])) {
                $resolvedParams[] = $parameters[$paramName];
                continue;
            }
            
            // If parameter has Container type, pass the current container
            if ($paramType && !$paramType->isBuiltin() && 
                ($paramType->getName() === self::class || is_a($this, $paramType->getName(), true))) {
                $resolvedParams[] = $this;
                continue;
            }
            
            // If parameter has a class type, try to create an instance
            if ($paramType && !$paramType->isBuiltin()) {
                $dependencyClass = $paramType->getName();
                
                try {
                    $resolvedParams[] = $this->has($dependencyClass) 
                        ? $this->get($dependencyClass) 
                        : $this->make($dependencyClass);
                    continue;
                } catch (\Throwable $e) {
                    // If we can't resolve the dependency but there's a default value, use that
                    if ($param->isDefaultValueAvailable()) {
                        $resolvedParams[] = $param->getDefaultValue();
                        continue;
                    }
                    
                    // Otherwise, throw container exception
                    throw new ContainerException(
                        'dependency_resolution',
                        $concrete,
                        sprintf("Cannot resolve parameter '%s' of type '%s': %s", $paramName, $dependencyClass, $e->getMessage()),
                        0,
                        $e
                    );
                }
            }
            
            // If parameter has a default value
            if ($param->isDefaultValueAvailable()) {
                $resolvedParams[] = $param->getDefaultValue();
                continue;
            }
            
            // If parameter is optional and nullable
            if ($paramType && $paramType->allowsNull()) {
                $resolvedParams[] = null;
                continue;
            }
            
            // If we couldn't resolve the parameter
            throw new ContainerException(
                'parameter_resolution',
                $concrete,
                sprintf("Cannot resolve parameter '%s' for class '%s'", $paramName, $concrete)
            );
        }
        
        // Create instance with resolved parameters
        try {
            return $reflection->newInstanceArgs($resolvedParams);
        } catch (\Throwable $e) {
            throw new ContainerException(
                'instantiation',
                $concrete,
                sprintf("Failed to instantiate class: %s", $e->getMessage()),
                0,
                $e
            );
        }
    }
    
    /**
     * Call a method with automatic dependency injection
     * 
     * @param object|string $instance Object instance or class name for static methods
     * @param string $method Method name to call
     * @param array<string, mixed> $parameters Additional parameters to pass to method
     * @return mixed Method result
     * @throws ContainerException When method doesn't exist or parameters cannot be resolved
     */
    public function call($instance, string $method, array $parameters = [])
    {
        // Validate method exists
        if (is_string($instance)) {
            if (!method_exists($instance, $method)) {
                throw new ContainerException(
                    'method',
                    "{$instance}::{$method}",
                    "Static method does not exist"
                );
            }
            $reflection = new \ReflectionMethod($instance, $method);
        } else {
            if (!method_exists($instance, $method)) {
                throw new ContainerException(
                    'method',
                    get_class($instance) . "->{$method}",
                    "Method does not exist"
                );
            }
            $reflection = new \ReflectionMethod($instance, $method);
        }
        
        // Get method parameters
        $methodParams = $reflection->getParameters();
        $resolvedParams = [];
        
        foreach ($methodParams as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();
            
            // If parameter is provided externally, use it
            if (isset($parameters[$paramName])) {
                $resolvedParams[] = $parameters[$paramName];
                continue;
            }
            
            // If parameter has Container type, pass the current container
            if ($paramType && !$paramType->isBuiltin() && 
                ($paramType->getName() === self::class || is_a($this, $paramType->getName(), true))) {
                $resolvedParams[] = $this;
                continue;
            }
            
            // If parameter has a class type, try to create an instance
            if ($paramType && !$paramType->isBuiltin()) {
                $dependencyClass = $paramType->getName();
                
                try {
                    $resolvedParams[] = $this->has($dependencyClass) 
                        ? $this->get($dependencyClass) 
                        : $this->make($dependencyClass);
                    continue;
                } catch (\Throwable $e) {
                    // If we can't resolve the dependency but there's a default value, use that
                    if ($param->isDefaultValueAvailable()) {
                        $resolvedParams[] = $param->getDefaultValue();
                        continue;
                    }
                    
                    // Otherwise, throw container exception
                    throw new ContainerException(
                        'dependency_resolution',
                        is_string($instance) ? "{$instance}::{$method}" : get_class($instance) . "->{$method}",
                        sprintf("Cannot resolve parameter '%s' of type '%s': %s", $paramName, $dependencyClass, $e->getMessage()),
                        0,
                        $e
                    );
                }
            }
            
            // If parameter has a default value
            if ($param->isDefaultValueAvailable()) {
                $resolvedParams[] = $param->getDefaultValue();
                continue;
            }
            
            // If parameter is optional and nullable
            if ($paramType && $paramType->allowsNull()) {
                $resolvedParams[] = null;
                continue;
            }
            
            // If we couldn't resolve the parameter
            throw new ContainerException(
                'parameter_resolution',
                is_string($instance) ? "{$instance}::{$method}" : get_class($instance) . "->{$method}",
                sprintf("Cannot resolve parameter '%s'", $paramName)
            );
        }
        
        // Call the method with resolved parameters
        try {
            return $reflection->invokeArgs(is_string($instance) ? null : $instance, $resolvedParams);
        } catch (\Throwable $e) {
            throw new ContainerException(
                'method_invocation',
                is_string($instance) ? "{$instance}::{$method}" : get_class($instance) . "->{$method}",
                sprintf("Failed to invoke method: %s", $e->getMessage()),
                0,
                $e
            );
        }
    }
    
    /**
     * Get all registered bindings
     * 
     * @return array<string> Array of registered binding keys
     */
    public function getBindings(): array
    {
        return array_keys($this->bindings);
    }
    
    /**
     * Get all resolved instances
     * 
     * @return array<string> Array of resolved instance keys
     */
    public function getInstances(): array
    {
        return array_keys($this->instances);
    }
    
    /**
     * Remove a binding from the container
     * 
     * @param string $abstract Abstract key
     * @return self
     */
    public function unbind(string $abstract): self
    {
        unset($this->bindings[$abstract], $this->instances[$abstract], $this->lazyServices[$abstract]);
        return $this;
    }
    
    /**
     * Clear all bindings and instances
     * 
     * @return self
     */
    public function clear(): self
    {
        $this->bindings = [];
        $this->instances = [];
        $this->lazyServices = [];
        $this->resolving = [];
        return $this;
    }
} 