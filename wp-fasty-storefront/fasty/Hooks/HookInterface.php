<?php
declare(strict_types=1);

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

/**
 * Interface for WordPress hook implementations
 */
interface HookInterface
{
    /**
     * Constructor
     *
     * @param Container $container Service container
     */
    public function __construct(Container $container);
    
    /**
     * Register hooks with WordPress
     *
     * @return void
     */
    public function register(): void;
    
    /**
     * Determine if hooks should be registered
     * 
     * @return bool True if hooks should be registered
     */
    public function canRegister(): bool;
    
    /**
     * Get hook identifier
     * 
     * @return string Unique identifier for this hook implementation
     */
    public function getIdentifier(): string;
    
    /**
     * Get priority for hook execution
     * 
     * @return int Priority (lower numbers run first)
     */
    public function getPriority(): int;
} 