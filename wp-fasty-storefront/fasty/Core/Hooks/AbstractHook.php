<?php
declare(strict_types=1);

namespace FastyChild\Core\Hooks;

use FastyChild\Core\Container;
use FastyChild\Core\Traits\LoggerTrait;

/**
 * Abstract base class for hook implementations
 */
abstract class AbstractHook implements HookInterface
{
    use LoggerTrait;
    
    /**
     * Service container
     * @var Container
     */
    protected Container $container;
    
    /**
     * Hook identifier
     * @var string
     */
    protected string $identifier;
    
    /**
     * Hook priority
     * @var int
     */
    protected int $priority = 10;
    
    /**
     * Constructor
     *
     * @param Container $container Service container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->identifier = $this->getDefaultIdentifier();
    }
    
    /**
     * Get default identifier based on class name
     *
     * @return string Default identifier
     */
    protected function getDefaultIdentifier(): string
    {
        $className = get_class($this);
        $parts = explode('\\', $className);
        $shortName = end($parts);
        
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
    }
    
    /**
     * Get hook identifier
     * 
     * @return string Unique identifier for this hook implementation
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
    
    /**
     * Set hook identifier
     * 
     * @param string $identifier New identifier
     * @return self
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }
    
    /**
     * Get priority for hook execution
     * 
     * @return int Priority (lower numbers run first)
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    /**
     * Set priority for hook execution
     * 
     * @param int $priority New priority
     * @return self
     */
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }
    
    /**
     * Add an action hook with the current object as the callback
     * 
     * @param string $hook Hook name
     * @param string $method Method name to call
     * @param int $priority Priority (default 10)
     * @param int $acceptedArgs Number of arguments the callback accepts
     * @return self
     */
    protected function addAction(string $hook, string $method, int $priority = 10, int $acceptedArgs = 1): self
    {
        if (method_exists($this, $method)) {
            add_action($hook, [$this, $method], $priority, $acceptedArgs);
            $this->debug(sprintf('Registered action hook: %s -> %s::%s', $hook, get_class($this), $method));
        } else {
            $this->warning(sprintf('Tried to register non-existent method %s::%s for action %s', get_class($this), $method, $hook));
        }
        
        return $this;
    }
    
    /**
     * Add a filter hook with the current object as the callback
     * 
     * @param string $hook Hook name
     * @param string $method Method name to call
     * @param int $priority Priority (default 10)
     * @param int $acceptedArgs Number of arguments the callback accepts
     * @return self
     */
    protected function addFilter(string $hook, string $method, int $priority = 10, int $acceptedArgs = 1): self
    {
        if (method_exists($this, $method)) {
            add_filter($hook, [$this, $method], $priority, $acceptedArgs);
            $this->debug(sprintf('Registered filter hook: %s -> %s::%s', $hook, get_class($this), $method));
        } else {
            $this->warning(sprintf('Tried to register non-existent method %s::%s for filter %s', get_class($this), $method, $hook));
        }
        
        return $this;
    }
    
    /**
     * Remove an action hook
     * 
     * @param string $hook Hook name
     * @param string|callable $callback Callback to remove
     * @param int $priority Priority
     * @return self
     */
    protected function removeAction(string $hook, $callback, int $priority = 10): self
    {
        remove_action($hook, $callback, $priority);
        $this->debug(sprintf('Removed action hook: %s', $hook));
        
        return $this;
    }
    
    /**
     * Remove a filter hook
     * 
     * @param string $hook Hook name
     * @param string|callable $callback Callback to remove
     * @param int $priority Priority
     * @return self
     */
    protected function removeFilter(string $hook, $callback, int $priority = 10): self
    {
        remove_filter($hook, $callback, $priority);
        $this->debug(sprintf('Removed filter hook: %s', $hook));
        
        return $this;
    }
    
    /**
     * Remove all actions for a hook
     * 
     * @param string $hook Hook name
     * @return self
     */
    protected function removeAllActions(string $hook): self
    {
        remove_all_actions($hook);
        $this->debug(sprintf('Removed all actions for hook: %s', $hook));
        
        return $this;
    }
    
    /**
     * Remove all filters for a hook
     * 
     * @param string $hook Hook name
     * @return self
     */
    protected function removeAllFilters(string $hook): self
    {
        remove_all_filters($hook);
        $this->debug(sprintf('Removed all filters for hook: %s', $hook));
        
        return $this;
    }
    
    /**
     * Determine if hooks should be registered
     * Default implementation always registers hooks
     * 
     * @return bool True if hooks should be registered
     */
    public function canRegister(): bool
    {
        return true;
    }
} 