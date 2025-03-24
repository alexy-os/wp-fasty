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
     * Make an instance of the given class with automatic dependency injection
     * 
     * @param string $concrete Class name to instantiate
     * @param array $parameters Additional parameters to pass to constructor
     * @return mixed
     */
    public function make(string $concrete, array $parameters = [])
    {
        $reflection = new \ReflectionClass($concrete);
        
        // Если у класса нет конструктора, просто создаем экземпляр
        if (!$reflection->getConstructor()) {
            return new $concrete;
        }
        
        // Получаем параметры конструктора
        $constructorParams = $reflection->getConstructor()->getParameters();
        $resolvedParams = [];
        
        foreach ($constructorParams as $param) {
            $paramName = $param->getName();
            $paramType = $param->getType();
            
            // Если параметр передан извне, используем его
            if (isset($parameters[$paramName])) {
                $resolvedParams[] = $parameters[$paramName];
                continue;
            }
            
            // Если параметр имеет тип Container, передаем текущий контейнер
            if ($paramType && !$paramType->isBuiltin() && 
                ($paramType->getName() === self::class || is_subclass_of($this, $paramType->getName()))) {
                $resolvedParams[] = $this;
                continue;
            }
            
            // Если параметр имеет класс-тип, пытаемся создать его экземпляр
            if ($paramType && !$paramType->isBuiltin()) {
                $dependencyClass = $paramType->getName();
                $resolvedParams[] = $this->get($dependencyClass) ?? $this->make($dependencyClass);
                continue;
            }
            
            // Если параметр имеет значение по умолчанию
            if ($param->isDefaultValueAvailable()) {
                $resolvedParams[] = $param->getDefaultValue();
                continue;
            }
            
            // Если не удалось разрешить параметр
            throw new \RuntimeException("Cannot resolve parameter '{$paramName}' for class '{$concrete}'");
        }
        
        // Создаем экземпляр с разрешенными параметрами
        return $reflection->newInstanceArgs($resolvedParams);
    }
} 