<?php
/**
 * Dependency Injection Container
 * Manages service bindings and resolutions
 */

namespace FastyChild\Core;

class Container {
    /**
     * Registered bindings
     * @var array
     */
    private $bindings = [];
    
    /**
     * Resolved instances
     * @var array
     */
    private $instances = [];
    
    /**
     * Register a binding in the container
     * 
     * @param string $abstract Abstract key
     * @param mixed $concrete Concrete implementation or factory
     * @return void
     */
    public function bind(string $abstract, $concrete): void {
        $this->bindings[$abstract] = $concrete;
    }
    
    /**
     * Register a singleton binding in the container
     * 
     * @param string $abstract Abstract key
     * @param mixed $concrete Concrete implementation or factory
     * @return void
     */
    public function singleton(string $abstract, $concrete): void {
        $this->bindings[$abstract] = function($container) use ($concrete, $abstract) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $concrete instanceof \Closure 
                    ? $concrete($container) 
                    : $concrete;
            }
            return $this->instances[$abstract];
        };
    }
    
    /**
     * Resolve a binding from the container
     * 
     * @param string $abstract Abstract key
     * @return mixed Resolved instance
     * @throws \Exception When binding not found
     */
    public function get(string $abstract) {
        // Check if we already have it in instances
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        
        // Check if binding exists
        if (!isset($this->bindings[$abstract])) {
            throw new \Exception("No binding found for {$abstract}");
        }
        
        $concrete = $this->bindings[$abstract];
        
        // If concrete is a closure, execute it
        if ($concrete instanceof \Closure) {
            $result = $concrete($this);
            
            // Store the result if it's a singleton (closures returned from singleton())
            if (isset($this->instances[$abstract])) {
                return $result;
            }
            
            return $result;
        }
        
        // Plain value binding
        return $concrete;
    }
    
    /**
     * Check if a binding exists in the container
     * 
     * @param string $abstract Abstract key
     * @return bool
     */
    public function has(string $abstract): bool {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
    
    /**
     * Create a new instance with dependencies resolved
     * 
     * @param string $concrete Class name
     * @param array $parameters Constructor parameters
     * @return object
     */
    public function make(string $concrete, array $parameters = []): object {
        if ($this->has($concrete)) {
            return $this->get($concrete);
        }
        
        // Create a new instance if not registered
        return new $concrete(...$parameters);
    }
} 