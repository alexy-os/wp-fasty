<?php

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

abstract class AbstractHooks implements HookInterface
{
    /**
     * Container instance
     * 
     * @var Container
     */
    protected $container;
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Default implementation always allows registration
     * 
     * @return bool
     */
    public function canRegister(): bool
    {
        return true;
    }
} 