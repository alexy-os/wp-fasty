<?php
/**
 * Framework Exception Class
 * Base exception class for all framework exceptions
 */

namespace FastyChild\Core\Exceptions;

class FrameworkException extends \Exception
{
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code (defaults to 0)
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get formatted string representation of the exception
     * 
     * @return string
     */
    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
    
    /**
     * Get safe version of the exception message with sensitive data removed
     * 
     * @return string
     */
    public function getSafeMessage(): string
    {
        // Strip potential sensitive information from message
        return wp_kses(
            $this->message,
            [
                'em' => [],
                'strong' => [],
                'code' => [],
            ]
        );
    }
    
    /**
     * Log exception to WordPress error log
     * 
     * @param string $prefix Log prefix
     * @return void
     */
    public function logException(string $prefix = 'FASTY_ERROR'): void
    {
        error_log("[{$prefix}] " . $this->getMessage() . " in " . $this->getFile() . " on line " . $this->getLine());
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[{$prefix}_TRACE] " . $this->getTraceAsString());
        }
    }
} 