<?php

declare(strict_types=1);

/**
 * Logger Interface
 *
 * This interface defines a simple logging contract
 * that can be implemented differently across platforms.
 *
 * @package WPFasty\Core
 */

namespace WPFasty\Core;

interface LoggerInterface
{
    /**
     * Log an error message
     *
     * @param string $message Error message
     * @param array<string, mixed> $context Additional context data
     */
    public function error(string $message, array $context = []): void;
    
    /**
     * Log a warning message
     *
     * @param string $message Warning message
     * @param array<string, mixed> $context Additional context data
     */
    public function warning(string $message, array $context = []): void;
    
    /**
     * Log an info message
     *
     * @param string $message Info message
     * @param array<string, mixed> $context Additional context data
     */
    public function info(string $message, array $context = []): void;
    
    /**
     * Log a debug message
     *
     * @param string $message Debug message
     * @param array<string, mixed> $context Additional context data
     */
    public function debug(string $message, array $context = []): void;
}
