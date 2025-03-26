<?php
declare(strict_types=1);

namespace FastyChild\Core\Traits;

/**
 * Logger Trait
 * Provides common logging functionality
 */
trait LoggerTrait
{
    /**
     * Log a message with the given level
     *
     * @param string $message Message to log
     * @param string $level Log level (DEBUG, INFO, WARNING, ERROR, FATAL)
     * @param array $context Additional context data
     * @return void
     */
    protected function log(string $message, string $level = 'INFO', array $context = []): void
    {
        $level = strtoupper($level);
        $prefix = "[" . FASTY_PREFIX . "{$level}] ";
        
        // Prepare context data if any
        $contextData = '';
        if (!empty($context)) {
            $contextData = ' | Context: ' . json_encode($context);
        }
        
        // Log the message
        $logMessage = $prefix . $message . $contextData;
        error_log($logMessage);
        
        // Allow external handling via hooks if WordPress is loaded
        if (function_exists('do_action')) {
            do_action('fasty_log', $message, $level, $context);
        }
    }
    
    /**
     * Log a debug message
     *
     * @param string $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function debug(string $message, array $context = []): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log($message, 'DEBUG', $context);
        }
    }
    
    /**
     * Log an info message
     *
     * @param string $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function info(string $message, array $context = []): void
    {
        $this->log($message, 'INFO', $context);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function warning(string $message, array $context = []): void
    {
        $this->log($message, 'WARNING', $context);
    }
    
    /**
     * Log an error message
     *
     * @param string $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function error(string $message, array $context = []): void
    {
        $this->log($message, 'ERROR', $context);
    }
    
    /**
     * Log a fatal error message
     *
     * @param string $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function fatal(string $message, array $context = []): void
    {
        $this->log($message, 'FATAL', $context);
    }
} 