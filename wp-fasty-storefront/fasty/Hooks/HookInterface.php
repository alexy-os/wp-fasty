<?php

namespace FastyChild\Hooks;

interface HookInterface
{
    /**
     * Register all hooks for this implementation
     * 
     * @return void
     */
    public function register(): void;
    
    /**
     * Check if this hook implementation can be registered
     * 
     * @return bool
     */
    public function canRegister(): bool;
} 